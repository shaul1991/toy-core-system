<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Notification\ChannelType;
use App\Enums\Notification\DispatchType;
use App\Enums\Notification\NotificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NotificationQueue extends Model
{
    protected $fillable = [
        'dispatch_type',
        'scheduled_at',
        'batch_key',
        'batch_window',
        'type',
        'channels',
        'recipient',
        'payload',
        'priority',
        'status',
        'attempts',
        'last_error',
        'dispatched_at',
    ];

    protected function casts(): array
    {
        return [
            'dispatch_type' => DispatchType::class,
            'status' => NotificationStatus::class,
            'scheduled_at' => 'datetime',
            'dispatched_at' => 'datetime',
            'channels' => 'array',
            'recipient' => 'array',
            'payload' => 'array',
            'priority' => 'integer',
            'attempts' => 'integer',
            'batch_window' => 'integer',
        ];
    }

    public function log(): HasOne
    {
        return $this->hasOne(NotificationLog::class, 'queue_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', NotificationStatus::PENDING);
    }

    public function scopeScheduledReady(Builder $query): Builder
    {
        return $query
            ->where('dispatch_type', DispatchType::SCHEDULED)
            ->where('status', NotificationStatus::PENDING)
            ->where('scheduled_at', '<=', now());
    }

    public function scopeBatchedReady(Builder $query): Builder
    {
        return $query
            ->where('dispatch_type', DispatchType::BATCHED)
            ->where('status', NotificationStatus::PENDING);
    }

    public function scopeByBatchKey(Builder $query, string $batchKey): Builder
    {
        return $query->where('batch_key', $batchKey);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', NotificationStatus::FAILED);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeOrderByPriority(Builder $query): Builder
    {
        return $query->orderByDesc('priority')->orderBy('created_at');
    }

    public function getChannelTypes(): array
    {
        return ChannelType::fromValues($this->channels);
    }

    public function isPending(): bool
    {
        return $this->status === NotificationStatus::PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === NotificationStatus::PROCESSING;
    }

    public function isDispatched(): bool
    {
        return $this->status === NotificationStatus::DISPATCHED;
    }

    public function isFailed(): bool
    {
        return $this->status === NotificationStatus::FAILED;
    }

    public function isCancelled(): bool
    {
        return $this->status === NotificationStatus::CANCELLED;
    }

    public function canRetry(): bool
    {
        return $this->status->canRetry();
    }

    public function canCancel(): bool
    {
        return $this->status->canCancel();
    }

    public function markAsProcessing(): bool
    {
        return $this->update([
            'status' => NotificationStatus::PROCESSING,
            'attempts' => $this->attempts + 1,
        ]);
    }

    public function markAsDispatched(): bool
    {
        return $this->update([
            'status' => NotificationStatus::DISPATCHED,
            'dispatched_at' => now(),
            'last_error' => null,
        ]);
    }

    public function markAsFailed(string $error): bool
    {
        return $this->update([
            'status' => NotificationStatus::FAILED,
            'last_error' => $error,
        ]);
    }

    public function markAsCancelled(): bool
    {
        return $this->update([
            'status' => NotificationStatus::CANCELLED,
        ]);
    }
}
