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
    ];

    public static function current(): self
    {
        return self::query()->firstOrCreate(
            ['id' => 1],
            [
                'commercial_name' => config('app.name', 'Control Horario'),
                'country' => 'España',
            ]
        );
    }

    public function displayName(): string
    {
        return $this->commercial_name
            ?: $this->legal_name
            ?: config('app.name', 'Control Horario');
    }

    public function fullAddress(): ?string
    {
        $parts = array_filter([
            $this->address,
            trim(($this->postal_code ?? '') . ' ' . ($this->city ?? '')),
            $this->province,
            $this->country,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }
}
