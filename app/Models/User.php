<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\AbsenceRequest;
use App\Models\WorkTimeRecord;
use App\Models\UserVacationBalance;
use App\Notifications\ResetPasswordNotification;
use Carbon\Carbon;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'pin_hash',
        'dni',
        'activo',
        'horas_semanales',
        'puesto',
        'horario',
        'horario_franjas',
        'working_days',
        'observaciones',
        'fecha_alta',
        'fecha_baja',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'is_admin' => 'boolean',
            'fecha_alta' => 'date',
            'fecha_baja' => 'date',
            'horario_franjas' => 'array',
            'working_days' => 'array',
        ];
    }

    public function workTimeRecords(): HasMany
    {
        return $this->hasMany(WorkTimeRecord::class);
    }

	public function activeWorkTimeRecord(): HasOne
	{
		return $this->hasOne(WorkTimeRecord::class)
			->whereNull('ended_at')
			->latestOfMany();
	}

	public function activeWorkingRecord(): HasOne
	{
		return $this->hasOne(WorkTimeRecord::class)
			->where('record_type', WorkTimeRecord::TYPE_WORK)
			->whereNull('ended_at')
			->latestOfMany();
	}

	public function absenceRequests(): HasMany
	{
		return $this->hasMany(AbsenceRequest::class);
	}

	public function reviewedAbsenceRequests(): HasMany
	{
		return $this->hasMany(AbsenceRequest::class, 'reviewed_by');
	}

	public function approvedVacationDaysForYear(int $year): int
	{
		return $this->absenceRequests()
			->where('type', 'vacation')
			->where('status', 'approved')
			->whereYear('starts_at', $year)
			->get()
			->sum(fn ($absence): int => $this->vacationDaysBetween($absence->starts_at, $absence->ends_at));
	}

	public function vacationDaysBetween(Carbon $startDate, Carbon $endDate): int
	{
		$settings = CompanySetting::current();

		if ($settings->vacation_counting_method === 'calendar') {
			return (int) $startDate->diffInDays($endDate) + 1;
		}

		$workingDays = $this->working_days
			?: $settings->working_days
			?: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
		$days = 0;

		for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
			if (in_array(strtolower($date->englishDayOfWeek), $workingDays, true) && ! Holiday::isHoliday($date)) {
				$days++;
			}
		}

		return $days;
	}

	public function vacationDaysAvailableForYear(int $year): int
	{
		$balance = $this->vacationBalances()
			->where('year', $year)
			->first();

		$totalDays = $balance?->total_days
			?? CompanySetting::current()->annual_vacation_days
			?? 22;

		return max(
			0,
			$totalDays - $this->approvedVacationDaysForYear($year)
		);
	}

	public function vacationBalances(): HasMany
	{
		return $this->hasMany(UserVacationBalance::class);
	}

    public function canAccessPanel(Panel $panel): bool
	{
		return (bool) $this->is_admin;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
