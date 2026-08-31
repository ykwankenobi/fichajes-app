<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbsenceRequest extends Model
{
    protected $fillable = [
		'user_id',
		'type',
		'starts_at',
		'ends_at',
		'status',
		'reason',
		'reviewed_by',
		'reviewed_at',
		'review_notification_read_at',
		'admin_notes',
	];

    protected function casts(): array
	{
		return [
			'starts_at' => 'date',
			'ends_at' => 'date',
			'reviewed_at' => 'datetime',
			'review_notification_read_at' => 'datetime',
		];
	}

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}