<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Repositories\NotificationQueueRepositoryInterface;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessBatchedNotificationsJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public int $uniqueFor = 300;

    public function __construct(
        public readonly string $batchKey,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(
        NotificationQueueRepositoryInterface $queueRepository,
        NotificationDispatcher $dispatcher,
    ): void {
        $queues = $queueRepository->getByBatchKey($this->batchKey);

        if ($queues->isEmpty()) {
            Log::info('No pending notifications for batch', ['batch_key' => $this->batchKey]);

            return;
        }

        Log::info('Processing batched notifications', [
            'batch_key' => $this->batchKey,
            'count' => $queues->count(),
        ]);

        $dispatcher->dispatchBatch($queues);
    }

    public function failed(Throwable $exception): void
    {
        $errorMessage = mb_substr($exception->getMessage(), 0, 1000);

        Log::error('Batched notification job failed', [
            'batch_key' => $this->batchKey,
            'exception' => get_class($exception),
            'error' => $errorMessage,
        ]);

        $queueRepository = app(NotificationQueueRepositoryInterface::class);
        $queues = $queueRepository->getByBatchKey($this->batchKey);

        foreach ($queues as $queue) {
            if (! $queue->status->isTerminal()) {
                $queueRepository->markAsFailed($queue, $errorMessage);
            }
        }
    }

    public function uniqueId(): string
    {
        return $this->batchKey;
    }

    public function tags(): array
    {
        return ['notification', 'batch', 'batch_key:'.$this->batchKey];
    }
}
