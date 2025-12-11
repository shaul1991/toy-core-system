<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Notification\DispatchType;
use App\Enums\Notification\NotificationStatus;
use App\Http\Requests\Notification\StoreNotificationRequest;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    public function store(StoreNotificationRequest $request): JsonResponse
    {
        $scheduledAt = $request->input('scheduled_at')
            ? Carbon::parse($request->input('scheduled_at'))
            : null;

        $queue = $this->notificationService->queue(
            dispatchType: $request->getDispatchType(),
            type: $request->input('type'),
            channels: $request->getChannels(),
            recipient: $request->input('recipient'),
            payload: $request->input('payload'),
            scheduledAt: $scheduledAt,
            batchKey: $request->input('batch_key'),
            batchWindow: $request->input('batch_window'),
            priority: $request->input('priority', 0),
        );

        return $this->createdResponse($this->formatQueue($queue));
    }

    public function indexQueue(Request $request): JsonResponse
    {
        $dispatchType = $request->input('dispatch_type')
            ? DispatchType::tryFrom($request->input('dispatch_type'))
            : null;

        $status = $request->input('status')
            ? NotificationStatus::tryFrom($request->input('status'))
            : null;

        $queues = $this->notificationService->paginateQueue(
            dispatchType: $dispatchType,
            status: $status,
            type: $request->input('type'),
            perPage: $request->integer('per_page', 15),
        );

        return $this->paginatedResponse(
            $queues,
            fn ($queue) => $this->formatQueue($queue)
        );
    }

    public function showQueue(int $id): JsonResponse
    {
        $queue = $this->notificationService->getQueueOrFail($id);

        return $this->successResponse($this->formatQueue($queue));
    }

    public function cancelQueue(int $id): JsonResponse
    {
        $queue = $this->notificationService->cancel($id);

        return $this->successResponse($this->formatQueue($queue));
    }

    public function retryQueue(int $id): JsonResponse
    {
        $queue = $this->notificationService->retry($id);

        return $this->successResponse($this->formatQueue($queue));
    }

    public function indexLogs(Request $request): JsonResponse
    {
        $status = $request->input('status')
            ? NotificationStatus::tryFrom($request->input('status'))
            : null;

        $from = $request->input('from') ? Carbon::parse($request->input('from')) : null;
        $to = $request->input('to') ? Carbon::parse($request->input('to')) : null;

        $logs = $this->notificationService->paginateLogs(
            status: $status,
            type: $request->input('type'),
            from: $from,
            to: $to,
            perPage: $request->integer('per_page', 15),
        );

        return $this->paginatedResponse(
            $logs,
            fn ($log) => $this->formatLog($log)
        );
    }

    public function showLog(int $id): JsonResponse
    {
        $log = $this->notificationService->getLog($id);

        if ($log === null) {
            return $this->notFoundResponse('알림 로그를 찾을 수 없습니다.');
        }

        return $this->successResponse($this->formatLog($log));
    }

    public function channels(): JsonResponse
    {
        $channels = $this->notificationService->getAvailableChannels();

        return $this->successResponse(
            array_map(fn ($channel) => [
                'value' => $channel->value,
                'label' => $channel->label(),
                'required_fields' => $channel->requiredRecipientFields(),
            ], $channels)
        );
    }

    private function formatQueue($queue): array
    {
        return [
            'id' => $queue->id,
            'dispatch_type' => $queue->dispatch_type->value,
            'type' => $queue->type,
            'channels' => $queue->channels,
            'recipient' => $queue->recipient,
            'payload' => $queue->payload,
            'status' => $queue->status->value,
            'priority' => $queue->priority,
            'scheduled_at' => $queue->scheduled_at?->toIso8601String(),
            'batch_key' => $queue->batch_key,
            'batch_window' => $queue->batch_window,
            'attempts' => $queue->attempts,
            'last_error' => $queue->last_error,
            'dispatched_at' => $queue->dispatched_at?->toIso8601String(),
            'created_at' => $queue->created_at->toIso8601String(),
            'updated_at' => $queue->updated_at->toIso8601String(),
        ];
    }

    private function formatLog($log): array
    {
        return [
            'id' => $log->id,
            'queue_id' => $log->queue_id,
            'batch_queue_ids' => $log->batch_queue_ids,
            'type' => $log->type,
            'channels' => $log->channels,
            'recipient' => $log->recipient,
            'payload' => $log->payload,
            'status' => $log->status->value,
            'channel_results' => $log->channel_results,
            'sent_at' => $log->sent_at?->toIso8601String(),
            'created_at' => $log->created_at->toIso8601String(),
        ];
    }
}
