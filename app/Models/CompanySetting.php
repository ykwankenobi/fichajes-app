<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
        'commercial_name',
        'legal_name',
        'tax_id',
        'address',
        'postal_code',
        'city',
        'province',
        'country',
        'email',
        'phone',
        'mail_from_name',
        'mail_from_address',
        'mail_reply_to',
        'password_reset_subject',
        'absence_request_subject',
        'absence_approved_subject',
        'absence_rejected_subject',
        'work_time_incident_subject',
        'holiday_municipality_ine',
        'working_days',
        'vacation_counting_method',
        'annual_vacation_days',
    ];

    protected $casts = [
        'working_days' => 'array',
        'annual_vacation_days' => 'integer',
    ];

    public static function current(): self
    {
        return self::query()->firstOrCreate(
            ['id' => 1],
            [
                'commercial_name' => config('app.name', 'Control Horario'),
                'country' => 'España',
                'vacation_counting_method' => 'working',
                'annual_vacation_days' => 22,
            ]
        );
    }

    public function displayName(): string
    {
        return $this->commercial_name
            ?: $this->legal_name
            ?: config('app.name', 'Control Horario');
    }

    public function mailFromName(): string
    {
        return $this->mail_from_name ?: 'Registro Horario '.$this->displayName();
    }

    public function mailFromAddress(): string
    {
        return $this->mail_from_address ?: (string) config('mail.from.address');
    }

    public function mailReplyTo(): ?string
    {
        return $this->mail_reply_to ?: null;
    }

    public function fullAddress(): ?string
    {
        $parts = array_filter([
            $this->address,
            trim(($this->postal_code ?? '').' '.($this->city ?? '')),
            $this->province,
            $this->country,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }
}
