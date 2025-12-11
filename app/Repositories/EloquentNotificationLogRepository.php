<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\Notification\NotificationStatus;
use App\Models\NotificationLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class EloquentNotificationLogRepository implements NotificationLogRepositoryInterface
{
    public function findById(int $id): ?NotificationLog
    {
        return NotificationLog::find($id);
    }

    public function findByQueueId(int $queueId): ?NotificationLog
    {
        return NotificationLog::where('queue_id', $queueId)->first();
    }

    public function create(array $data): NotificationLog
    {
        return NotificationLog::create($data);
    }

    public function paginate(
        ?NotificationStatus $status = null,
        ?string $type = null,
        ?Carbon $from = null,
        ?Carbon $to = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = NotificationLog::query()->orderByDesc('sent_at');

        if ($status !== null) {
            $query->byStatus($status);
        }

        if ($type !== null) {
            $query->byType($type);
        }

        if ($from !== null && $to !== null) {
            $query->sentBetween($from, $to);
        } elseif ($from !== null) {
            $query->where('sent_at', '>=', $from);
        } elseif ($to !== null) {
            $query->where('sent_at', '<=', $to);
        }

        return $query->paginate($perPage);
    }

    public function countByStatus(NotificationStatus $status, ?Carbon $from = null, ?Carbon $to = null): int
    {
        $query = NotificationLog::byStatus($status);

        if ($from !== null && $to !== null) {
            $query->sentBetween($from, $to);
        }

        return $query->count();
    }

    public function countByType(string $type, ?Carbon $from = null, ?Carbon $to = null): int
    {
        $query = NotificationLog::byType($type);

        if ($from !== null && $to !== null) {
            $query->sentBetween($from, $to);
        }

        return $query->count();
    }
}
