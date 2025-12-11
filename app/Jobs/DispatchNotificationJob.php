<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Repositories\NotificationQueueRepositoryInterface;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class DispatchNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public readonly int $queueId,
    ) {
        $this->onQueue('notifications');
    }

    public function handle(
        NotificationQueueRepositoryInterface $queueRepository,
        NotificationDispatcher $dispatcher,
    ): void {
        $queue = $queueRepository->findById($this->queueId);

        if ($queue === null) {
            Log::warning('Notification queue not found', ['queue_id' => $this->queueId]);

            return;
        }

        if (! $queue->isPending()) {
            Log::info('Notification queue already processed', [
                'queue_id' => $this->queueId,
                'status' => $queue->status->value,
            ]);

            return;
        }

        $dispatcher->dispatch($queue);
    }

    public function failed(Throwable $exception): void
    {
        $errorMessage = mb_substr($exception->getMessage(), 0, 1000);

        Log::error('Notification job failed', [
            'queue_id' => $this->queueId,
            'exception' => get_class($exception),
            'error' => $errorMessage,
        ]);

        $queueRepository = app(NotificationQueueRepositoryInterface::class);
        $queue = $queueRepository->findById($this->queueId);

        if ($queue !== null && ! $queue->status->isTerminal()) {
            $queueRepository->markAsFailed($queue, $errorMessage);
        }
    }

    public function tags(): array
    {
        return ['notification', 'queue:'.$this->queueId];
    }
}
