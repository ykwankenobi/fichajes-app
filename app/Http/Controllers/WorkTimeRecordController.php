<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkTimeRecord;
use App\Notifications\WorkTimeIncidentCreatedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WorkTimeRecordController extends Controller
{
    public function index(Request $request): View
    {
        return view('work-time.index', [
            'activeRecord' => $request->user()->activeWorkTimeRecord,
        ]);
    }

    public function clockIn(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->is_admin) {
            return back()->with('error', 'Los administradores no pueden fichar.');
        }

        $incidentRecord = null;
        $scheduleWarning = null;

        $response = DB::transaction(function () use ($user, &$incidentRecord, &$scheduleWarning): ?RedirectResponse {
            User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $activeRecord = $user->activeWorkTimeRecord()->first();

            if ($activeRecord?->record_type === WorkTimeRecord::TYPE_WORK) {
                return back()->with('error', 'Ya tienes una jornada iniciada.');
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

            $startedAt = now();
            $scheduleWarning = $user->scheduleIncidentNote($startedAt, 'entrada');
            $record = $user->workTimeRecords()->create([
                'record_type' => WorkTimeRecord::TYPE_WORK,
                'started_at' => $startedAt,
                'requires_review' => $scheduleWarning !== null,
                'notes' => $scheduleWarning,
            ]);

            if ($scheduleWarning !== null) {
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

        return back()
            ->with('success', 'Entrada registrada correctamente.')
            ->when($scheduleWarning !== null, fn (RedirectResponse $response) => $response->with('warning', 'Has fichado fuera de tu horario previsto. Administración revisará la incidencia.'));
    }

    public function clockOut(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'end_type' => [
                'required',
                'in:end_shift,justified_exit,unjustified_exit',
            ],
        ]);

        $user = $request->user();

        if ($user->is_admin) {
            return back()->with('error', 'Los administradores no pueden fichar.');
        }

        $incidentRecord = null;
        $scheduleWarning = null;

        $response = DB::transaction(function () use ($user, $validated, &$incidentRecord, &$scheduleWarning): ?RedirectResponse {
            User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $record = $user->activeWorkTimeRecord()->first();

            if (! $record || $record->record_type !== WorkTimeRecord::TYPE_WORK) {
                return back()->with('error', 'No tienes una jornada activa.');
            }

            $endedAt = now();

            $workedMinutes = (int) $record->started_at->diffInMinutes($endedAt);
            $scheduleWarning = $user->scheduleIncidentNote($endedAt, 'salida');
            $wasAlreadyUnderReview = $record->requires_review;

            $record->update([
                'ended_at' => $endedAt,
                'end_type' => $validated['end_type'],
                'worked_minutes' => $workedMinutes,
                'justified_exit_minutes' => 0,
                'unjustified_exit_minutes' => 0,
                'requires_review' => $wasAlreadyUnderReview || $scheduleWarning !== null,
                'notes' => $scheduleWarning === null
                    ? $record->notes
                    : trim(implode("\n", array_filter([$record->notes, $scheduleWarning]))),
            ]);

            if ($scheduleWarning !== null && ! $wasAlreadyUnderReview) {
                $incidentRecord = $record->fresh();
            }

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

        if ($incidentRecord instanceof WorkTimeRecord) {
            $this->notifyAdminsAboutIncident($incidentRecord);
        }

        return back()
            ->with('success', 'Salida registrada correctamente.')
            ->when($scheduleWarning !== null, fn (RedirectResponse $response) => $response->with('warning', 'Has fichado fuera de tu horario previsto. Administración revisará la incidencia.'));
    }

    public function finishExit(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->is_admin) {
            return back()->with('error', 'Los administradores no pueden fichar.');
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
                return back()->with('error', 'No tienes ninguna salida activa.');
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

        return back()->with('success', 'Jornada finalizada correctamente.');
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
