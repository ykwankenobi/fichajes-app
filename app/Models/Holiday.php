<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['date', 'name', 'notes'];

    protected $casts = [
        'date' => 'date',
    ];

    public static function isHoliday(CarbonInterface|string $date): bool
    {
        $date = $date instanceof CarbonInterface ? $date->toDateString() : $date;

        return static::query()->whereDate('date', $date)->exists();
    }
}
