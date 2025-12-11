<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\Notification\DispatchType;
use App\Enums\Notification\NotificationStatus;
use App\Models\NotificationQueue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface NotificationQueueRepositoryInterface
{
    public function findById(int $id): ?NotificationQueue;

    public function create(array $data): NotificationQueue;

    public function update(NotificationQueue $queue, array $data): NotificationQueue;

    public function delete(NotificationQueue $queue): bool;

    public function getPending(int $limit = 100): Collection;

    public function getScheduledReady(int $limit = 100): Collection;

    public function getBatchedReadyGroups(): Collection;

    public function getByBatchKey(string $batchKey): Collection;

    public function paginate(
        ?DispatchType $dispatchType = null,
        ?NotificationStatus $status = null,
        ?string $type = null,
        int $perPage = 15
    ): LengthAwarePaginator;

    public function markAsProcessing(NotificationQueue $queue): bool;

    public function markAsDispatched(NotificationQueue $queue): bool;

    public function markAsFailed(NotificationQueue $queue, string $error): bool;

    public function markAsCancelled(NotificationQueue $queue): bool;

    public function markMultipleAsDispatched(array $queueIds): int;
}
