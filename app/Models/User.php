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
			->sum(function ($absence) {
				$days = 0;
				for ($date = $absence->starts_at->copy(); $date->lte($absence->ends_at); $date->addDay()) {
					if (! Holiday::isHoliday($date)) {
						$days++;
					}
				}

				return $days;
			});
	}

	public function vacationDaysAvailableForYear(int $year): int
	{
		$balance = $this->vacationBalances()
			->where('year', $year)
			->first();

		$totalDays = $balance?->total_days ?? 22;

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
