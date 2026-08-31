<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WorkTimeRecord extends Model
{
    public const TYPE_WORK = 'work';

    public const TYPE_JUSTIFIED_EXIT = 'justified_exit';

    public const TYPE_UNJUSTIFIED_EXIT = 'unjustified_exit';

    protected $fillable = [
        'user_id',
        'record_type',
        'started_at',
        'ended_at',
        'end_type',
        'worked_minutes',
        'justified_exit_minutes',
        'unjustified_exit_minutes',
        'notes',
        'closed_automatically',
        'requires_review',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'closed_automatically' => 'boolean',
        'requires_review' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(WorkTimeRecordCorrection::class);
    }

    public function approvedCorrections(): HasMany
    {
        return $this->hasMany(WorkTimeRecordCorrection::class)
            ->where('status', WorkTimeRecordCorrection::STATUS_APPROVED);
    }

    public function latestCorrection(): HasOne
    {
        return $this->hasOne(WorkTimeRecordCorrection::class)
            ->latestOfMany();
    }

    public function latestApprovedCorrection()
    {
        return $this->hasOne(WorkTimeRecordCorrection::class)
            ->where('status', WorkTimeRecordCorrection::STATUS_APPROVED)
            ->latestOfMany();
    }

    public function scopeDailySummary($query)
    {
        return $query->selectRaw('
                user_id,
                DATE(started_at) as work_date,

                SUM(worked_minutes) as total_worked_minutes,
                SUM(justified_exit_minutes) as total_justified_minutes,
                SUM(unjustified_exit_minutes) as total_unjustified_minutes,

                (
                    SUM(worked_minutes)
                    + SUM(justified_exit_minutes)
                ) as computable_minutes
            ')
            ->groupBy('user_id', 'work_date')
            ->havingRaw('
                SUM(worked_minutes)
                + SUM(justified_exit_minutes)
                + SUM(unjustified_exit_minutes) > 0
            ');
    }

    public function scopeWeeklySummary($query)
    {
        return $query->selectRaw('
                user_id,

                SUM(worked_minutes) as total_worked_minutes,
                SUM(justified_exit_minutes) as total_justified_minutes,
                SUM(unjustified_exit_minutes) as total_unjustified_minutes,

                (
                    SUM(worked_minutes)
                    + SUM(justified_exit_minutes)
                ) as computable_minutes
            ')
            ->groupBy('user_id')
            ->havingRaw('
                SUM(worked_minutes)
                + SUM(justified_exit_minutes)
                + SUM(unjustified_exit_minutes) > 0
            ');
    }
}
