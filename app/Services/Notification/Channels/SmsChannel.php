<?php

declare(strict_types=1);

namespace App\Services\Notification\Channels;

use App\Enums\Notification\ChannelType;
use App\Models\NotificationQueue;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SmsChannel implements NotificationChannelInterface
{
    public function __construct(
        private readonly array $config = [],
    ) {}

    public function getType(): ChannelType
    {
        return ChannelType::SMS;
    }

    public function send(NotificationQueue $queue): ChannelResult
    {
        try {
            $recipient = $queue->recipient;
            $payload = $queue->payload;

            if (! $this->validateRecipient($recipient)) {
                return ChannelResult::failure(
                    channel: 'sms',
                    error: '유효한 전화번호가 없습니다.'
                );
            }

            $phone = $this->normalizePhone($recipient['phone']);
            $message = $payload['message'] ?? $payload['body'] ?? '';

            if (mb_strlen($message) > 90) {
                $message = mb_substr($message, 0, 87).'...';
            }

            $messageId = $this->sendSms($phone, $message, $queue->id);

            return ChannelResult::success(
                channel: 'sms',
                messageId: $messageId,
                metadata: ['phone' => $this->maskPhone($phone)]
            );
        } catch (Throwable $e) {
            Log::error('SMS notification failed', [
                'queue_id' => $queue->id,
                'error' => $e->getMessage(),
            ]);

            return ChannelResult::failure(
                channel: 'sms',
                error: $e->getMessage()
            );
        }
    }

    public function sendBatch(array $queues, array $aggregatedPayload): ChannelResult
    {
        try {
            if (empty($queues)) {
                return ChannelResult::failure(
                    channel: 'sms',
                    error: '발송할 알림이 없습니다.'
                );
            }

            $firstQueue = $queues[0];
            $recipient = $firstQueue->recipient;

            if (! $this->validateRecipient($recipient)) {
                return ChannelResult::failure(
                    channel: 'sms',
                    error: '유효한 전화번호가 없습니다.'
                );
            }

            $phone = $this->normalizePhone($recipient['phone']);
            $count = $aggregatedPayload['summary']['total_count'] ?? count($queues);
            $message = "{$count}건의 새로운 알림이 있습니다.";

            if (isset($aggregatedPayload['message'])) {
                $message = $aggregatedPayload['message'];
            }

            if (mb_strlen($message) > 90) {
                $message = mb_substr($message, 0, 87).'...';
            }

            $queueIds = array_map(fn ($q) => $q->id, $queues);
            $messageId = $this->sendSms($phone, $message, $queueIds);

            return ChannelResult::success(
                channel: 'sms',
                messageId: $messageId,
                metadata: [
                    'phone' => $this->maskPhone($phone),
                    'batch_count' => count($queues),
                ]
            );
        } catch (Throwable $e) {
            Log::error('SMS batch notification failed', [
                'queue_ids' => array_map(fn ($q) => $q->id, $queues),
                'error' => $e->getMessage(),
            ]);

            return ChannelResult::failure(
                channel: 'sms',
                error: $e->getMessage()
            );
        }
    }

    public function validateRecipient(array $recipient): bool
    {
        if (! isset($recipient['phone'])) {
            return false;
        }

        $phone = preg_replace('/[^0-9+]/', '', $recipient['phone']);

        return strlen($phone) >= 10;
    }

    private function isLogMode(): bool
    {
        if (config('notification.force_log_mode', false)) {
            return true;
        }

        $provider = $this->config['provider'] ?? 'log';

        return $provider === 'log';
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($phone, '010')) {
            $phone = '+82'.substr($phone, 1);
        }

        return $phone;
    }

    private function maskPhone(string $phone): string
    {
        if (strlen($phone) < 4) {
            return '****';
        }

        return substr($phone, 0, -4).'****';
    }

    private function sendSms(string $phone, string $message, int|array $queueId): string
    {
        if ($this->isLogMode()) {
            $logContext = [
                'mode' => 'LOG_MODE',
                'channel' => 'sms',
                'queue_id' => $queueId,
                'phone' => $phone,
                'phone_masked' => $this->maskPhone($phone),
                'message' => $message,
                'message_length' => mb_strlen($message),
                'would_send' => true,
            ];

            Log::channel('stack')->info('[NOTIFICATION:SMS] 발송 시뮬레이션 (LOG MODE)', $logContext);

            return 'sms_log_'.uniqid();
        }

        $apiUrl = $this->config['api_url'] ?? '';
        $apiKey = $this->config['api_key'] ?? '';

        if (empty($apiUrl) || empty($apiKey)) {
            Log::warning('SMS provider not configured, using log mode', [
                'queue_id' => $queueId,
            ]);

            return 'sms_log_'.uniqid();
        }

        $timeout = $this->config['timeout'] ?? config('notification.channels.sms.timeout', 10);

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                ])->post($apiUrl, [
                    'to' => $phone,
                    'message' => $message,
                    'from' => $this->config['from'] ?? '',
                ]);

            if (! $response->successful()) {
                throw new RuntimeException(
                    "SMS API 호출 실패: status={$response->status()}, body={$response->body()}"
                );
            }

            Log::info('SMS notification sent', [
                'queue_id' => $queueId,
                'phone' => $this->maskPhone($phone),
            ]);

            return $response->json('message_id', 'sms_'.uniqid());
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                "SMS API 연결 실패: {$e->getMessage()} (timeout={$timeout}s)",
                0,
                $e
            );
        }
    }
}
