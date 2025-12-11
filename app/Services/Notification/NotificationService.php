<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\Notification\ChannelType;
use App\Enums\Notification\DispatchType;
use App\Enums\Notification\NotificationStatus;
use App\Jobs\DispatchNotificationJob;
use App\Models\NotificationLog;
use App\Models\NotificationQueue;
use App\Repositories\NotificationLogRepositoryInterface;
use App\Repositories\NotificationQueueRepositoryInterface;
use App\Shared\Exceptions\NotificationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class NotificationService
{
    public function __construct(
        private readonly NotificationQueueRepositoryInterface $queueRepository,
        private readonly NotificationLogRepositoryInterface $logRepository,
        private readonly NotificationDispatcher $dispatcher,
    ) {}

    public function queue(
        DispatchType $dispatchType,
        string $type,
        array $channels,
        array $recipient,
        array $payload,
        ?Carbon $scheduledAt = null,
        ?string $batchKey = null,
        ?int $batchWindow = null,
        int $priority = 0,
    ): NotificationQueue {
        $this->validateChannels($channels);
        $this->validateRecipient($channels, $recipient);
        $this->validateDispatchType($dispatchType, $scheduledAt, $batchKey);

        $queue = $this->queueRepository->create([
            'dispatch_type' => $dispatchType,
            'type' => $type,
            'channels' => $channels,
            'recipient' => $recipient,
            'payload' => $payload,
            'scheduled_at' => $scheduledAt,
            'batch_key' => $batchKey,
            'batch_window' => $batchWindow ?? config('notification.batch.default_window', 3600),
            'priority' => $priority,
            'status' => NotificationStatus::PENDING,
        ]);

        if ($dispatchType === DispatchType::IMMEDIATE) {
            DispatchNotificationJob::dispatch($queue->id);
        }

        return $queue;
    }

    public function cancel(int $queueId): NotificationQueue
    {
        $queue = $this->queueRepository->findById($queueId);

        if ($queue === null) {
            throw NotificationException::queueNotFound($queueId);
        }

        if (! $queue->canCancel()) {
            throw NotificationException::cannotCancel($queueId, $queue->status->value);
        }

        $this->queueRepository->markAsCancelled($queue);
        $queue->refresh();

        return $queue;
    }

    public function retry(int $queueId): NotificationQueue
    {
        $queue = $this->queueRepository->findById($queueId);

        if ($queue === null) {
            throw NotificationException::queueNotFound($queueId);
        }

        if (! $queue->canRetry()) {
            throw NotificationException::cannotRetry($queueId, $queue->status->value);
        }

        $maxRetries = config('notification.max_retries', 3);
        if ($queue->attempts >= $maxRetries) {
            throw NotificationException::maxRetriesExceeded($queueId, $queue->attempts);
        }

        $this->queueRepository->update($queue, [
            'status' => NotificationStatus::PENDING,
            'last_error' => null,
        ]);

        DispatchNotificationJob::dispatch($queue->id);

        $queue->refresh();

        return $queue;
    }

    public function getQueue(int $queueId): ?NotificationQueue
    {
        return $this->queueRepository->findById($queueId);
    }

    public function getQueueOrFail(int $queueId): NotificationQueue
    {
        $queue = $this->queueRepository->findById($queueId);

        if ($queue === null) {
            throw NotificationException::queueNotFound($queueId);
        }

        return $queue;
    }

    public function paginateQueue(
        ?DispatchType $dispatchType = null,
        ?NotificationStatus $status = null,
        ?string $type = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->queueRepository->paginate($dispatchType, $status, $type, $perPage);
    }

    public function getLog(int $logId): ?NotificationLog
    {
        return $this->logRepository->findById($logId);
    }

    public function getLogOrFail(int $logId): NotificationLog
    {
        $log = $this->logRepository->findById($logId);

        if ($log === null) {
            throw NotificationException::logNotFound($logId);
        }

        return $log;
    }

    public function paginateLogs(
        ?NotificationStatus $status = null,
        ?string $type = null,
        ?Carbon $from = null,
        ?Carbon $to = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->logRepository->paginate($status, $type, $from, $to, $perPage);
    }

    public function getAvailableChannels(): array
    {
        return ChannelType::cases();
    }

    private function validateChannels(array $channels): void
    {
        if (empty($channels)) {
            throw NotificationException::invalidChannel('(empty)');
        }

        foreach ($channels as $channel) {
            // Handle both ChannelType enum and string
            if ($channel instanceof ChannelType) {
                continue;
            }

            if (ChannelType::tryFrom($channel) === null) {
                throw NotificationException::invalidChannel($channel);
            }
        }
    }

    private function validateRecipient(array $channels, array $recipient): void
    {
        foreach ($channels as $channel) {
            // Handle both ChannelType enum and string
            $channelType = $channel instanceof ChannelType
                ? $channel
                : ChannelType::from($channel);
            $requiredFields = $channelType->requiredRecipientFields();

            foreach ($requiredFields as $field) {
                if (empty($recipient[$field])) {
                    throw NotificationException::missingRecipient($channelType->value, $requiredFields);
                }
            }
        }
    }

    private function validateDispatchType(DispatchType $dispatchType, ?Carbon $scheduledAt, ?string $batchKey): void
    {
        if ($dispatchType === DispatchType::SCHEDULED) {
            if ($scheduledAt === null) {
                throw NotificationException::scheduledAtRequired();
            }

            if ($scheduledAt->isPast()) {
                throw NotificationException::scheduledAtMustBeFuture();
            }
        }

        if ($dispatchType === DispatchType::BATCHED && empty($batchKey)) {
            throw NotificationException::batchKeyRequired();
        }
    }
}
