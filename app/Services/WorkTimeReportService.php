<?php

namespace App\Services;

use App\Models\WorkTimeRecord;
use App\Models\WorkTimeRecordCorrection;
use App\Models\Holiday;
use App\Models\AbsenceRequest;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WorkTimeReportService
{
    public function getDailySummary(
        int $userId,
        bool $isAdmin,
        ?string $day = null,
        ?int $selectedUserId = null
    ): Collection {
        [$startDate, $endDate] = $this->getDayRange($day);

        return $this->getSummaryForPeriod($userId, $isAdmin, $startDate, $endDate, $selectedUserId, 'day');
    }

    public function getWeeklySummary(
        int $userId,
        bool $isAdmin,
        ?string $week = null,
        ?int $selectedUserId = null
    ): Collection {
        [$startDate, $endDate] = $this->getWeekRange($week);

        return $this->getSummaryForPeriod($userId, $isAdmin, $startDate, $endDate, $selectedUserId, 'week');
    }

    public function getMonthlySummary(
        int $userId,
        bool $isAdmin,
        ?string $month = null,
        ?int $selectedUserId = null
    ): Collection {
        [$startDate, $endDate] = $this->getMonthRange($month);

        return $this->getSummaryForPeriod($userId, $isAdmin, $startDate, $endDate, $selectedUserId, 'month');
    }

    public function getWeeklyDailySummary(
        int $userId,
        bool $isAdmin,
        ?string $week = null,
        ?int $selectedUserId = null
    ): Collection {
        [$startDate, $endDate] = $this->getWeekRange($week);

        return $this->getDailySummaryForPeriod($userId, $isAdmin, $startDate, $endDate, $selectedUserId);
    }

    public function getMonthlyDailySummary(
        int $userId,
        bool $isAdmin,
        ?string $month = null,
        ?int $selectedUserId = null
    ): Collection {
        [$startDate, $endDate] = $this->getMonthRange($month);

        return $this->getDailySummaryForPeriod($userId, $isAdmin, $startDate, $endDate, $selectedUserId);
    }

    public function getDailyApprovedCorrections(
        int $userId,
        bool $isAdmin,
        ?string $day = null,
        ?int $selectedUserId = null
    ): Collection {
        [$startDate, $endDate] = $this->getDayRange($day);

        return $this->getApprovedCorrectionsForPeriod($userId, $isAdmin, $startDate, $endDate, $selectedUserId);
    }

    public function getWeeklyApprovedCorrections(
        int $userId,
        bool $isAdmin,
        ?string $week = null,
        ?int $selectedUserId = null
    ): Collection {
        [$startDate, $endDate] = $this->getWeekRange($week);

        return $this->getApprovedCorrectionsForPeriod($userId, $isAdmin, $startDate, $endDate, $selectedUserId);
    }

    public function getMonthlyApprovedCorrections(
        int $userId,
        bool $isAdmin,
        ?string $month = null,
        ?int $selectedUserId = null
    ): Collection {
        [$startDate, $endDate] = $this->getMonthRange($month);

        return $this->getApprovedCorrectionsForPeriod($userId, $isAdmin, $startDate, $endDate, $selectedUserId);
    }

    protected function getRecordsForPeriod(
        int $userId,
        bool $isAdmin,
        Carbon $startDate,
        Carbon $endDate,
        ?int $selectedUserId = null
    ): Collection {
        $query = WorkTimeRecord::query()
            ->with(['user', 'latestApprovedCorrection'])
            ->whereHas('user', function ($query): void {
                $query->where('is_admin', false);
            })
            ->where(function ($query) use ($startDate, $endDate): void {
                $query
                    ->whereBetween('started_at', [$startDate, $endDate])
                    ->orWhereHas('approvedCorrections', function ($correctionQuery) use ($startDate, $endDate): void {
                        $correctionQuery->whereBetween('corrected_started_at', [$startDate, $endDate]);
                    });
            });

        if ($isAdmin && $selectedUserId) {
            $query->where('user_id', $selectedUserId);
        }

        if (! $isAdmin) {
            $query->where('user_id', $userId);
        }

        return $query
            ->get()
            ->map(fn (WorkTimeRecord $record): array => $this->normalizeRecord($record))
            ->filter(fn (array $record): bool => $record['started_at']->betweenIncluded($startDate, $endDate))
            ->values();
    }

    protected function getApprovedCorrectionsForPeriod(
        int $userId,
        bool $isAdmin,
        Carbon $startDate,
        Carbon $endDate,
        ?int $selectedUserId = null
    ): Collection {
        $query = WorkTimeRecordCorrection::query()
            ->with(['workTimeRecord.user', 'reviewer'])
            ->where('status', WorkTimeRecordCorrection::STATUS_APPROVED)
            ->where(function ($query) use ($startDate, $endDate): void {
                $query
                    ->whereBetween('corrected_started_at', [$startDate, $endDate])
                    ->orWhereBetween('original_started_at', [$startDate, $endDate]);
            })
            ->whereHas('workTimeRecord.user', function ($query): void {
                $query->where('is_admin', false);
            });

        if ($isAdmin && $selectedUserId) {
            $query->whereHas('workTimeRecord', function ($recordQuery) use ($selectedUserId): void {
                $recordQuery->where('user_id', $selectedUserId);
            });
        }

        if (! $isAdmin) {
            $query->whereHas('workTimeRecord', function ($recordQuery) use ($userId): void {
                $recordQuery->where('user_id', $userId);
            });
        }

        return $query
            ->orderBy('corrected_started_at')
            ->get()
            ->map(function (WorkTimeRecordCorrection $correction): array {
                return [
                    'usuario' => $correction->workTimeRecord?->user?->name ?? '-',
                    'original_inicio' => $correction->original_started_at,
                    'original_fin' => $correction->original_ended_at,
                    'corregido_inicio' => $correction->corrected_started_at,
                    'corregido_fin' => $correction->corrected_ended_at,
                    'motivo' => $correction->reason,
                    'revisado_por' => $correction->reviewer?->name ?? '-',
                    'fecha_revision' => $correction->reviewed_at,
                ];
            })
            ->values();
    }

    protected function getSummaryForPeriod(
        int $userId,
        bool $isAdmin,
        Carbon $startDate,
        Carbon $endDate,
        ?int $selectedUserId,
        string $periodType
    ): Collection {
        $records = $this->getRecordsForPeriod($userId, $isAdmin, $startDate, $endDate, $selectedUserId);

        return $records
            ->groupBy('user_id')
            ->map(function (Collection $userRecords) use ($periodType, $startDate, $endDate): array {
                $user = $userRecords->first()['user'];

                $workedMinutes = $userRecords->sum('worked_minutes');
                $justifiedMinutes = $userRecords->sum('justified_exit_minutes');
                $unjustifiedMinutes = $userRecords->sum('unjustified_exit_minutes');
                $computableMinutes = $workedMinutes + $justifiedMinutes;
                $entrada = $userRecords->min('started_at');
                $salida = $userRecords->filter(fn (array $record): bool => $record['ended_at'] !== null)->max('ended_at');

                $expectedMinutes = $this->expectedMinutes($user, $startDate, $endDate);

                $differenceMinutes = $computableMinutes - $expectedMinutes;

                return [
                    'usuario' => $user->name,
                    'entrada' => $entrada?->format('H:i') ?? '-',
                    'salida' => $salida?->format('H:i') ?? '-',
                    'trabajadas' => $this->formatMinutes($workedMinutes),
                    'justificadas' => $this->formatMinutes($justifiedMinutes),
                    'injustificadas' => $this->formatMinutes($unjustifiedMinutes),
                    'computables' => $this->formatMinutes($computableMinutes),
                    'esperadas' => $this->formatMinutes($expectedMinutes),
                    'diferencia' => $this->formatMinutes($differenceMinutes),
                ];
            })
            ->sortBy('usuario')
            ->values();
    }

    protected function expectedMinutes($user, Carbon $startDate, Carbon $endDate): int
    {
        $workingDays = collect($user->working_days ?: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']);
        $weeklyMinutes = (int) round(($user->horas_semanales ?? 0) * 60);
        $configuredDays = max(1, $workingDays->count());
        $dailyMinutes = $weeklyMinutes / $configuredDays;
        $expectedDays = 0;

        for ($date = $startDate->copy()->startOfDay(); $date->lte($endDate); $date->addDay()) {
            if (! $workingDays->contains(strtolower($date->format('l')))) {
                continue;
            }
            if (Holiday::whereDate('date', $date)->exists()) {
                continue;
            }
            if (AbsenceRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->whereDate('starts_at', '<=', $date)
                ->whereDate('ends_at', '>=', $date)
                ->exists()) {
                continue;
            }
            $expectedDays++;
        }

        return (int) round($expectedDays * $dailyMinutes);
    }

    protected function getDailySummaryForPeriod(
        int $userId,
        bool $isAdmin,
        Carbon $startDate,
        Carbon $endDate,
        ?int $selectedUserId
    ): Collection {
        $records = $this->getRecordsForPeriod($userId, $isAdmin, $startDate, $endDate, $selectedUserId);

        return $records
            ->groupBy(fn (array $record): string => $record['user_id'] . '|' . $record['started_at']->toDateString())
            ->map(function (Collection $dayRecords): array {
                $firstRecord = $dayRecords->first();
                $user = $firstRecord['user'];

                $workedMinutes = $dayRecords->sum('worked_minutes');
                $justifiedMinutes = $dayRecords->sum('justified_exit_minutes');
                $unjustifiedMinutes = $dayRecords->sum('unjustified_exit_minutes');
                $computableMinutes = $workedMinutes + $justifiedMinutes;
                $entrada = $dayRecords->min('started_at');
                $salida = $dayRecords->filter(fn (array $record): bool => $record['ended_at'] !== null)->max('ended_at');

                return [
                    'usuario' => $user->name,
                    'fecha' => $firstRecord['started_at']->toDateString(),
                    'entrada' => $entrada?->format('H:i') ?? '-',
                    'salida' => $salida?->format('H:i') ?? '-',
                    'trabajadas' => $this->formatMinutes($workedMinutes),
                    'justificadas' => $this->formatMinutes($justifiedMinutes),
                    'injustificadas' => $this->formatMinutes($unjustifiedMinutes),
                    'computables' => $this->formatMinutes($computableMinutes),
                ];
            })
            ->sortBy([
                ['usuario', 'asc'],
                ['fecha', 'asc'],
            ])
            ->values();
    }

    protected function normalizeRecord(WorkTimeRecord $record): array
    {
        $correction = $record->latestApprovedCorrection;

        $startedAt = $correction?->corrected_started_at ?? $record->started_at;
        $endedAt = $correction?->corrected_ended_at ?? $record->ended_at;

        $durationMinutes = $this->calculateDurationMinutes($startedAt, $endedAt);

        $workedMinutes = 0;
        $justifiedExitMinutes = 0;
        $unjustifiedExitMinutes = 0;

        match ($record->record_type) {
            WorkTimeRecord::TYPE_WORK => $workedMinutes = $durationMinutes,
            WorkTimeRecord::TYPE_JUSTIFIED_EXIT => $justifiedExitMinutes = $durationMinutes,
            WorkTimeRecord::TYPE_UNJUSTIFIED_EXIT => $unjustifiedExitMinutes = $durationMinutes,
            default => $workedMinutes = (int) $record->worked_minutes,
        };

        return [
            'id' => $record->id,
            'user_id' => $record->user_id,
            'user' => $record->user,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'worked_minutes' => $workedMinutes,
            'justified_exit_minutes' => $justifiedExitMinutes,
            'unjustified_exit_minutes' => $unjustifiedExitMinutes,
            'has_approved_correction' => $correction !== null,
        ];
    }

    protected function calculateDurationMinutes(?Carbon $startedAt, ?Carbon $endedAt): int
    {
        if (! $startedAt || ! $endedAt) {
            return 0;
        }

        return max(0, (int) $startedAt->diffInMinutes($endedAt));
    }

    protected function getDayRange(?string $day): array
    {
        $day = filled($day)
            ? $day
            : now()->format('Y-m-d');

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)) {
            $day = now()->format('Y-m-d');
        }

        $startDate = Carbon::createFromFormat('Y-m-d', $day)
            ->startOfDay();

        return [
            $startDate,
            $startDate->copy()->endOfDay(),
        ];
    }

    protected function getWeekRange(?string $week): array
    {
        $week = filled($week)
            ? $week
            : now()->format('o-\WW');

        if (! preg_match('/^\d{4}-W\d{2}$/', $week)) {
            $week = now()->format('o-\WW');
        }

        $startDate = Carbon::now()
            ->setISODate(
                (int) substr($week, 0, 4),
                (int) substr($week, 6, 2)
            )
            ->startOfWeek();

        return [
            $startDate,
            $startDate->copy()->endOfWeek(),
        ];
    }

    protected function getMonthRange(?string $month): array
    {
        $month = filled($month)
            ? $month
            : now()->format('Y-m');

        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        $startDate = Carbon::createFromFormat('Y-m-d', $month . '-01')
            ->startOfMonth();

        return [
            $startDate,
            $startDate->copy()->endOfMonth(),
        ];
    }

    protected function formatMinutes(int $minutes): string
    {
        $sign = $minutes < 0 ? '-' : '';

        $minutes = abs($minutes);

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        if ($remainingMinutes === 0) {
            return "{$sign}{$hours}h";
        }

        return "{$sign}{$hours}h {$remainingMinutes}m";
    }
}
