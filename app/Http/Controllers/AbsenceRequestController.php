<?php

namespace App\Http\Controllers;

use App\Models\AbsenceRequest;
use App\Models\User;
use App\Notifications\AbsenceRequestCreatedNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AbsenceRequestController extends Controller
{
    public function index()
    {
        $absenceRequests = Auth::user()
            ->absenceRequests()
            ->latest()
            ->paginate(10);

        Auth::user()
            ->absenceRequests()
            ->whereIn('status', ['approved', 'rejected'])
            ->whereNotNull('reviewed_at')
            ->whereNull('review_notification_read_at')
            ->update([
                'review_notification_read_at' => now(),
            ]);

        return view('absence-requests.index', [
            'absenceRequests' => $absenceRequests,
        ]);
    }

    public function create()
    {
        return view('absence-requests.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => [
                'required',
                Rule::in([
                    'vacation',
                    'medical_leave',
                    'leave_of_absence',
                    'personal_leave',
                    'other',
                ]),
            ],
            'starts_at' => [
                'required',
                'date',
            ],
            'ends_at' => [
                'required',
                'date',
                'after_or_equal:starts_at',
            ],
            'reason' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $startDate = Carbon::parse($validated['starts_at']);
        $endDate = Carbon::parse($validated['ends_at']);

        if ($validated['type'] === 'vacation') {
            $requestedDays = $request->user()->vacationDaysBetween($startDate, $endDate);

            $availableDays = $request->user()
                ->vacationDaysAvailableForYear($startDate->year);

            if ($requestedDays > $availableDays) {
                return back()
                    ->withErrors([
                        'starts_at' => 'No tienes suficientes días de vacaciones disponibles.',
                    ])
                    ->withInput();
            }
        }

        $absenceRequest = AbsenceRequest::create([
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);

        User::query()
            ->where('is_admin', true)
            ->get()
            ->each(fn (User $admin): mixed => $admin->notify(
                new AbsenceRequestCreatedNotification($absenceRequest)
            ));

        return redirect()
            ->route('absence-requests.index')
            ->with('success', 'Solicitud enviada correctamente.');
    }
}
