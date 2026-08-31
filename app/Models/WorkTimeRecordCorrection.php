<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkTimeRecordCorrection extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'work_time_record_id',
        'requested_by',
        'reviewed_by',
        'original_started_at',
        'original_ended_at',
        'corrected_started_at',
        'corrected_ended_at',
        'status',
        'reason',
        'review_notes',
        'reviewed_at',
    ];

    protected $casts = [
        'original_started_at' => 'datetime',
        'original_ended_at' => 'datetime',
        'corrected_started_at' => 'datetime',
        'corrected_ended_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function workTimeRecord(): BelongsTo
    {
        return $this->belongsTo(WorkTimeRecord::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
