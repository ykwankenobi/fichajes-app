<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserVacationBalance extends Model
{
    protected $fillable = [
        'user_id',
        'year',
        'total_days',
    ];

    protected $casts = [
        'year' => 'integer',
        'total_days' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
