<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\Notification\ChannelType;
use App\Enums\Notification\NotificationStatus;
use App\Models\NotificationLog;
use App\Models\NotificationQueue;
use App\Repositories\NotificationLogRepositoryInterface;
use App\Repositories\NotificationQueueRepositoryInterface;
use App\Services\Notification\Channels\ChannelResult;
use App\Services\Notification\Channels\NotificationChannelInterface;
use App\Shared\Exceptions\NotificationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NotificationDispatcher
{
    /** @var array<string, NotificationChannelInterface> */
    private array $channels = [];

    public function __construct(
        private readonly NotificationQueueRepositoryInterface $queueRepository,
        private readonly NotificationLogRepositoryInterface $logRepository,
        private readonly BatchAggregator $batchAggregator,
    ) {}

    public function registerChannel(NotificationChannelInterface $channel): void
    {
        $this->channels[$channel->getType()->value] = $channel;
    }

    public function dispatch(NotificationQueue $queue): NotificationLog
    {
        $this->queueRepository->markAsProcessing($queue);

        try {
            $results = $this->sendToChannels($queue);
            $status = $this->determineStatus($results);

            $log = $this->createLog($queue, $results, $status);

            if ($status === NotificationStatus::FAILED) {
                $errors = $this->extractErrors($results);
                $this->queueRepository->markAsFailed($queue, implode('; ', $errors));
            } else {
                $this->queueRepository->markAsDispatched($queue);
            }

            return $log;
        } catch (\Throwable $e) {
            Log::error('Notification dispatch failed', [
                'queue_id' => $queue->id,
                'error' => $e->getMessage(),
            ]);

            $this->queueRepository->markAsFailed($queue, $e->getMessage());

            throw NotificationException::dispatchFailed($queue->id, $e->getMessage());
        }
    }

    public function dispatchBatch(Collection $queues): NotificationLog
    {
        if ($queues->isEmpty()) {
            throw new \InvalidArgumentException('빈 배치는 발송할 수 없습니다.');
        }

        $queueIds = $queues->pluck('id')->toArray();

        foreach ($queues as $queue) {
            $this->queueRepository->markAsProcessing($queue);
        }

        try {
            $firstQueue = $queues->first();
            $aggregatedPayload = $this->batchAggregator->aggregate($queues);

            $results = $this->sendBatchToChannels($queues->toArray(), $firstQueue, $aggregatedPayload);
            $status = $this->determineStatus($results);

            $log = $this->createBatchLog($queues, $aggregatedPayload, $results, $status);

            if ($status === NotificationStatus::FAILED) {
                $errors = $this->extractErrors($results);
                foreach ($queues as $queue) {
                    $this->queueRepository->markAsFailed($queue, implode('; ', $errors));
                }
            } else {
                $this->queueRepository->markMultipleAsDispatched($queueIds);
            }

            return $log;
        } catch (\Throwable $e) {
            Log::error('Notification batch dispatch failed', [
                'queue_ids' => $queueIds,
                'error' => $e->getMessage(),
            ]);

            foreach ($queues as $queue) {
                $this->queueRepository->markAsFailed($queue, $e->getMessage());
            }

            throw NotificationException::dispatchFailed($queues->first()->id, $e->getMessage());
        }
    }

    /**
     * @return array<string, ChannelResult>
     */
    private function sendToChannels(NotificationQueue $queue): array
    {
        $results = [];

        foreach ($queue->channels as $channelName) {
            $channel = $this->channels[$channelName] ?? null;

            if ($channel === null) {
                $results[$channelName] = ChannelResult::failure(
                    $channelName,
                    "채널 '{$channelName}'이 등록되지 않았습니다."
                );

                continue;
            }

            if (! $channel->validateRecipient($queue->recipient)) {
                $channelType = ChannelType::tryFrom($channelName);
                $requiredFields = $channelType?->requiredRecipientFields() ?? [];
                $results[$channelName] = ChannelResult::failure(
                    $channelName,
                    '수신자 정보가 유효하지 않습니다. 필수 필드: '.implode(', ', $requiredFields)
                );

                continue;
            }

            $results[$channelName] = $channel->send($queue);
        }

        return $results;
    }

    /**
     * @return array<string, ChannelResult>
     */
    private function sendBatchToChannels(array $queues, NotificationQueue $firstQueue, array $aggregatedPayload): array
    {
        $results = [];

        foreach ($firstQueue->channels as $channelName) {
            $channel = $this->channels[$channelName] ?? null;

            if ($channel === null) {
                $results[$channelName] = ChannelResult::failure(
                    $channelName,
                    "채널 '{$channelName}'이 등록되지 않았습니다."
                );

                continue;
            }

            if (! $channel->validateRecipient($firstQueue->recipient)) {
                $results[$channelName] = ChannelResult::failure(
                    $channelName,
                    '수신자 정보가 유효하지 않습니다.'
                );

                continue;
            }

            $results[$channelName] = $channel->sendBatch($queues, $aggregatedPayload);
        }

        return $results;
    }

    /**
     * @param  array<string, ChannelResult>  $results
     */
    private function determineStatus(array $results): NotificationStatus
    {
        if (empty($results)) {
            return NotificationStatus::FAILED;
        }

        $successCount = 0;
        $failCount = 0;

        foreach ($results as $result) {
            if ($result->isSuccess()) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        if ($successCount === count($results)) {
            return NotificationStatus::SENT;
        }

        if ($failCount === count($results)) {
            return NotificationStatus::FAILED;
        }

        return NotificationStatus::PARTIAL;
    }

    /**
     * @param  array<string, ChannelResult>  $results
     */
    private function createLog(NotificationQueue $queue, array $results, NotificationStatus $status): NotificationLog
    {
        $channelResults = [];
        foreach ($results as $channelName => $result) {
            $channelResults[$channelName] = $result->toArray();
        }

        return $this->logRepository->create([
            'queue_id' => $queue->id,
            'type' => $queue->type,
            'channels' => $queue->channels,
            'recipient' => $queue->recipient,
            'payload' => $queue->payload,
            'status' => $status,
            'channel_results' => $channelResults,
            'sent_at' => now(),
        ]);
    }

    /**
     * @param  array<string, ChannelResult>  $results
     */
    private function createBatchLog(Collection $queues, array $aggregatedPayload, array $results, NotificationStatus $status): NotificationLog
    {
        $firstQueue = $queues->first();
        $channelResults = [];

        foreach ($results as $channelName => $result) {
            $channelResults[$channelName] = $result->toArray();
        }

        return $this->logRepository->create([
            'queue_id' => null,
            'batch_queue_ids' => $queues->pluck('id')->toArray(),
            'type' => $firstQueue->type,
            'channels' => $firstQueue->channels,
            'recipient' => $firstQueue->recipient,
            'payload' => $aggregatedPayload,
            'status' => $status,
            'channel_results' => $channelResults,
            'sent_at' => now(),
        ]);
    }

    /**
     * @param  array<string, ChannelResult>  $results
     */
    private function extractErrors(array $results): array
    {
        $errors = [];

        foreach ($results as $channelName => $result) {
            if ($result->isFailure() && $result->error !== null) {
                $errors[] = "{$channelName}: {$result->error}";
            }
        }

        return $errors;
    }
}
