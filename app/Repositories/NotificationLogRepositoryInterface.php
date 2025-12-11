<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\Notification\NotificationStatus;
use App\Models\NotificationLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

interface NotificationLogRepositoryInterface
{
    public function findById(int $id): ?NotificationLog;

    public function findByQueueId(int $queueId): ?NotificationLog;

    public function create(array $data): NotificationLog;

    public function paginate(
        ?NotificationStatus $status = null,
        ?string $type = null,
        ?Carbon $from = null,
        ?Carbon $to = null,
        int $perPage = 15
    ): LengthAwarePaginator;

    public function countByStatus(NotificationStatus $status, ?Carbon $from = null, ?Carbon $to = null): int;

    public function countByType(string $type, ?Carbon $from = null, ?Carbon $to = null): int;
}
