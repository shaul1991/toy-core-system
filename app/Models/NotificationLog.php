<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Notification\NotificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'queue_id',
        'batch_queue_ids',
        'type',
        'channels',
        'recipient',
        'payload',
        'status',
        'channel_results',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => NotificationStatus::class,
            'sent_at' => 'datetime',
            'channels' => 'array',
            'recipient' => 'array',
            'payload' => 'array',
            'channel_results' => 'array',
            'batch_queue_ids' => 'array',
        ];
    }

    public function queue(): BelongsTo
    {
        return $this->belongsTo(NotificationQueue::class, 'queue_id');
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus(Builder $query, NotificationStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', NotificationStatus::SENT);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', NotificationStatus::FAILED);
    }

    public function scopePartial(Builder $query): Builder
    {
        return $query->where('status', NotificationStatus::PARTIAL);
    }

    public function scopeSentBetween(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('sent_at', [$from, $to]);
    }

    public function isSent(): bool
    {
        return $this->status === NotificationStatus::SENT;
    }

    public function isFailed(): bool
    {
        return $this->status === NotificationStatus::FAILED;
    }

    public function isPartial(): bool
    {
        return $this->status === NotificationStatus::PARTIAL;
    }

    public function isBatchLog(): bool
    {
        return ! empty($this->batch_queue_ids);
    }

    public function getChannelResult(string $channel): ?array
    {
        return $this->channel_results[$channel] ?? null;
    }

    public function getSuccessfulChannels(): array
    {
        return array_keys(
            array_filter(
                $this->channel_results ?? [],
                fn ($result) => ($result['status'] ?? '') === 'sent'
            )
        );
    }

    public function getFailedChannels(): array
    {
        return array_keys(
            array_filter(
                $this->channel_results ?? [],
                fn ($result) => ($result['status'] ?? '') === 'failed'
            )
        );
    }
}
