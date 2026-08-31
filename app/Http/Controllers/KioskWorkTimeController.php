<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkTimeRecord;
use App\Notifications\WorkTimeIncidentCreatedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class KioskWorkTimeController extends Controller
{
    private const TOKEN_TTL_MINUTES = 5;

    public function index(): View
    {
        return view('kiosk.work-time', [
            'users' => $this->employeesQuery()->get(),
            'selectedUser' => null,
            'activeRecord' => null,
            'token' => null,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query): void {
                    $query->where('activo', true)
                        ->where('is_admin', false);
                }),
            ],
            'pin' => ['required', 'digits_between:4,12'],
        ]);

        $user = $this->employeesQuery()
            ->whereKey($validated['user_id'])
            ->firstOrFail();

        $this->ensureIsNotRateLimited($request, $user);

        if (! $user->pin_hash || ! Hash::check($validated['pin'], $user->pin_hash)) {
            RateLimiter::hit($this->throttleKey($request, $user), 60);

            throw ValidationException::withMessages([
                'pin' => 'El PIN introducido no es correcto.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request, $user));

        $token = Str::random(64);

        Cache::put(
            $this->cacheKey($token),
            $user->id,
            now()->addMinutes(self::TOKEN_TTL_MINUTES)
        );

        return redirect()->route('kiosk.show', $token);
    }

    public function show(string $token): View|RedirectResponse
    {
        $user = $this->userForToken($token);

        if (! $user) {
            return redirect()
                ->route('kiosk.index')
                ->with('error', 'La validación ha caducado. Introduce el PIN de nuevo.');
        }

        return view('kiosk.work-time', [
            'users' => $this->employeesQuery()->get(),
            'selectedUser' => $user,
            'activeRecord' => $user->activeWorkTimeRecord()->first(),
            'token' => $token,
        ]);
    }

    public function clockIn(Request $request, string $token): RedirectResponse
    {
        $user = $this->consumeToken($token);

        if (! $user) {
            return $this->expiredTokenResponse();
        }

        $incidentRecord = null;

        $response = DB::transaction(function () use ($user, &$incidentRecord): ?RedirectResponse {
            User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $activeRecord = $user->activeWorkTimeRecord()->first();

            if ($activeRecord?->record_type === WorkTimeRecord::TYPE_WORK) {
                return $this->kioskError('Ya tienes una jornada iniciada.');
            }

            if ($activeRecord && in_array($activeRecord->record_type, [
                WorkTimeRecord::TYPE_JUSTIFIED_EXIT,
                WorkTimeRecord::TYPE_UNJUSTIFIED_EXIT,
            ], true)) {
                $endedAt = now();
                $minutes = (int) $activeRecord->started_at->diffInMinutes($endedAt);
                $isUnjustifiedExit = $activeRecord->record_type === WorkTimeRecord::TYPE_UNJUSTIFIED_EXIT;

                $activeRecord->update([
                    'ended_at' => $endedAt,
                    'justified_exit_minutes' => $activeRecord->record_type === WorkTimeRecord::TYPE_JUSTIFIED_EXIT ? $minutes : 0,
                    'unjustified_exit_minutes' => $isUnjustifiedExit ? $minutes : 0,
                    'requires_review' => $isUnjustifiedExit,
                ]);

                if ($isUnjustifiedExit && $minutes > 0) {
                    $incidentRecord = $activeRecord->fresh();
                }
            }

            $user->workTimeRecords()->create([
                'record_type' => WorkTimeRecord::TYPE_WORK,
                'started_at' => now(),
            ]);

            return null;
        });

        if ($response instanceof RedirectResponse) {
            return $response;
        }

        if ($incidentRecord instanceof WorkTimeRecord) {
            $this->notifyAdminsAboutIncident($incidentRecord);
        }

        return $this->kioskSuccess('Entrada registrada correctamente.');
    }

    public function clockOut(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'end_type' => ['required', 'in:end_shift,justified_exit,unjustified_exit'],
        ]);

        $user = $this->consumeToken($token);

        if (! $user) {
            return $this->expiredTokenResponse();
        }

        $response = DB::transaction(function () use ($user, $validated): ?RedirectResponse {
            User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $record = $user->activeWorkTimeRecord()->first();

            if (! $record || $record->record_type !== WorkTimeRecord::TYPE_WORK) {
                return $this->kioskError('No tienes una jornada activa.');
            }

            $endedAt = now();
            $workedMinutes = (int) $record->started_at->diffInMinutes($endedAt);

            $record->update([
                'ended_at' => $endedAt,
                'end_type' => $validated['end_type'],
                'worked_minutes' => $workedMinutes,
                'justified_exit_minutes' => 0,
                'unjustified_exit_minutes' => 0,
            ]);

            if ($validated['end_type'] !== 'end_shift') {
                $user->workTimeRecords()->create([
                    'record_type' => $validated['end_type'],
                    'started_at' => $endedAt,
                ]);
            }

            return null;
        });

        if ($response instanceof RedirectResponse) {
            return $response;
        }

        return $this->kioskSuccess('Salida registrada correctamente.');
    }

    public function finishExit(Request $request, string $token): RedirectResponse
    {
        $user = $this->consumeToken($token);

        if (! $user) {
            return $this->expiredTokenResponse();
        }

        $incidentRecord = null;

        $response = DB::transaction(function () use ($user, &$incidentRecord): ?RedirectResponse {
            User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $record = $user->activeWorkTimeRecord()->first();

            if (! $record || ! in_array($record->record_type, [
                WorkTimeRecord::TYPE_JUSTIFIED_EXIT,
                WorkTimeRecord::TYPE_UNJUSTIFIED_EXIT,
            ], true)) {
                return $this->kioskError('No tienes ninguna salida activa.');
            }

            $endedAt = now();
            $minutes = (int) $record->started_at->diffInMinutes($endedAt);
            $isUnjustifiedExit = $record->record_type === WorkTimeRecord::TYPE_UNJUSTIFIED_EXIT;

            $record->update([
                'ended_at' => $endedAt,
                'end_type' => 'end_shift',
                'justified_exit_minutes' => $record->record_type === WorkTimeRecord::TYPE_JUSTIFIED_EXIT ? $minutes : 0,
                'unjustified_exit_minutes' => $isUnjustifiedExit ? $minutes : 0,
                'requires_review' => $isUnjustifiedExit,
            ]);

            if ($isUnjustifiedExit && $minutes > 0) {
                $incidentRecord = $record->fresh();
            }

            return null;
        });

        if ($response instanceof RedirectResponse) {
            return $response;
        }

        if ($incidentRecord instanceof WorkTimeRecord) {
            $this->notifyAdminsAboutIncident($incidentRecord);
        }

        return $this->kioskSuccess('Jornada finalizada correctamente.');
    }

    private function employeesQuery()
    {
        return User::query()
            ->where('is_admin', false)
            ->where('activo', true)
            ->orderBy('name');
    }

    private function userForToken(string $token): ?User
    {
        $userId = Cache::get($this->cacheKey($token));

        if (! $userId) {
            return null;
        }

        return $this->employeesQuery()
            ->whereKey($userId)
            ->first();
    }

    private function consumeToken(string $token): ?User
    {
        $userId = Cache::pull($this->cacheKey($token));

        if (! $userId) {
            return null;
        }

        return $this->employeesQuery()
            ->whereKey($userId)
            ->first();
    }

    private function cacheKey(string $token): string
    {
        return 'kiosk-work-time:' . $token;
    }

    private function ensureIsNotRateLimited(Request $request, User $user): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request, $user), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request, $user));

        throw ValidationException::withMessages([
            'pin' => 'Demasiados intentos. Vuelve a probar en ' . $seconds . ' segundos.',
        ]);
    }

    private function throttleKey(Request $request, User $user): string
    {
        return 'kiosk-pin:' . $user->id . '|' . $request->ip();
    }

    private function expiredTokenResponse(): RedirectResponse
    {
        return $this->kioskError('La validación ha caducado. Introduce el PIN de nuevo.');
    }

    private function kioskSuccess(string $message): RedirectResponse
    {
        return redirect()
            ->route('kiosk.index')
            ->with('success', $message);
    }

    private function kioskError(string $message): RedirectResponse
    {
        return redirect()
            ->route('kiosk.index')
            ->with('error', $message);
    }

    private function notifyAdminsAboutIncident(WorkTimeRecord $workTimeRecord): void
    {
        User::query()
            ->where('is_admin', true)
            ->get()
            ->each(fn (User $admin): mixed => $admin->notify(
                new WorkTimeIncidentCreatedNotification($workTimeRecord)
            ));
    }
}