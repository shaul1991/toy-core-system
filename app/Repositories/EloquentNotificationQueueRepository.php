<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\Notification\DispatchType;
use App\Enums\Notification\NotificationStatus;
use App\Models\NotificationQueue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EloquentNotificationQueueRepository implements NotificationQueueRepositoryInterface
{
    public function findById(int $id): ?NotificationQueue
    {
        return NotificationQueue::find($id);
    }

    public function create(array $data): NotificationQueue
    {
        return NotificationQueue::create($data);
    }

    public function update(NotificationQueue $queue, array $data): NotificationQueue
    {
        $queue->update($data);
        $queue->refresh();

        return $queue;
    }

    public function delete(NotificationQueue $queue): bool
    {
        return $queue->delete();
    }

    public function getPending(int $limit = 100): Collection
    {
        return NotificationQueue::pending()
            ->where('dispatch_type', DispatchType::IMMEDIATE)
            ->orderByPriority()
            ->limit($limit)
            ->get();
    }

    public function getScheduledReady(int $limit = 100): Collection
    {
        return NotificationQueue::scheduledReady()
            ->orderByPriority()
            ->limit($limit)
            ->get();
    }

    public function getBatchedReadyGroups(): Collection
    {
        $now = now();

        return NotificationQueue::query()
            ->where('dispatch_type', DispatchType::BATCHED)
            ->where('status', NotificationStatus::PENDING)
            ->whereNotNull('batch_key')
            ->selectRaw('batch_key, MIN(batch_window) as batch_window, MIN(created_at) as first_created_at, COUNT(*) as count')
            ->groupBy('batch_key')
            ->get()
            ->filter(function ($group) use ($now) {
                $firstCreatedAt = Carbon::parse($group->first_created_at);
                $readyAt = $firstCreatedAt->addSeconds((int) $group->batch_window);

                return $readyAt->lte($now);
            })
            ->values();
    }

    public function getByBatchKey(string $batchKey): Collection
    {
        return NotificationQueue::pending()
            ->byBatchKey($batchKey)
            ->orderByPriority()
            ->get();
    }

    public function paginate(
        ?DispatchType $dispatchType = null,
        ?NotificationStatus $status = null,
        ?string $type = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = NotificationQueue::query()->orderByDesc('created_at');

        if ($dispatchType !== null) {
            $query->where('dispatch_type', $dispatchType);
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($type !== null) {
            $query->byType($type);
        }

        return $query->paginate($perPage);
    }

    public function markAsProcessing(NotificationQueue $queue): bool
    {
        return $queue->markAsProcessing();
    }

    public function markAsDispatched(NotificationQueue $queue): bool
    {
        return $queue->markAsDispatched();
    }

    public function markAsFailed(NotificationQueue $queue, string $error): bool
    {
        return $queue->markAsFailed($error);
    }

    public function markAsCancelled(NotificationQueue $queue): bool
    {
        return $queue->markAsCancelled();
    }

    public function markMultipleAsDispatched(array $queueIds): int
    {
        return NotificationQueue::whereIn('id', $queueIds)
            ->update([
                'status' => NotificationStatus::DISPATCHED,
                'dispatched_at' => now(),
                'last_error' => null,
            ]);
    }
}
