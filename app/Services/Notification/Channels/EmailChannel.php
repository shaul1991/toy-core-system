<?php

declare(strict_types=1);

namespace App\Services\Notification\Channels;

use App\Enums\Notification\ChannelType;
use App\Models\NotificationQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailChannel implements NotificationChannelInterface
{
    public function __construct(
        private readonly array $config = [],
    ) {}

    public function getType(): ChannelType
    {
        return ChannelType::EMAIL;
    }

    public function send(NotificationQueue $queue): ChannelResult
    {
        try {
            $recipient = $queue->recipient;
            $payload = $queue->payload;

            if (! $this->validateRecipient($recipient)) {
                return ChannelResult::failure(
                    channel: 'email',
                    error: '유효한 이메일 주소가 없습니다.'
                );
            }

            $to = $recipient['email'];
            $subject = $payload['subject'] ?? $this->getDefaultSubject($queue->type);
            $body = $payload['body'] ?? $payload['message'] ?? '';

            $messageId = $this->sendEmail($to, $subject, $body, $payload, $queue->id);

            return ChannelResult::success(
                channel: 'email',
                messageId: $messageId,
                metadata: ['to' => $to, 'subject' => $subject]
            );
        } catch (Throwable $e) {
            Log::error('Email notification failed', [
                'queue_id' => $queue->id,
                'error' => $e->getMessage(),
            ]);

            return ChannelResult::failure(
                channel: 'email',
                error: $e->getMessage()
            );
        }
    }

    public function sendBatch(array $queues, array $aggregatedPayload): ChannelResult
    {
        try {
            if (empty($queues)) {
                return ChannelResult::failure(
                    channel: 'email',
                    error: '발송할 알림이 없습니다.'
                );
            }

            $firstQueue = $queues[0];
            $recipient = $firstQueue->recipient;

            if (! $this->validateRecipient($recipient)) {
                return ChannelResult::failure(
                    channel: 'email',
                    error: '유효한 이메일 주소가 없습니다.'
                );
            }

            $to = $recipient['email'];
            $subject = $aggregatedPayload['subject'] ?? $this->getDefaultSubject($firstQueue->type).' (묶음)';
            $body = $this->formatBatchBody($aggregatedPayload);
            $queueIds = array_map(fn ($q) => $q->id, $queues);

            $messageId = $this->sendEmail($to, $subject, $body, $aggregatedPayload, $queueIds);

            return ChannelResult::success(
                channel: 'email',
                messageId: $messageId,
                metadata: [
                    'to' => $to,
                    'subject' => $subject,
                    'batch_count' => count($queues),
                ]
            );
        } catch (Throwable $e) {
            Log::error('Email batch notification failed', [
                'queue_ids' => array_map(fn ($q) => $q->id, $queues),
                'error' => $e->getMessage(),
            ]);

            return ChannelResult::failure(
                channel: 'email',
                error: $e->getMessage()
            );
        }
    }

    public function validateRecipient(array $recipient): bool
    {
        return isset($recipient['email'])
            && filter_var($recipient['email'], FILTER_VALIDATE_EMAIL) !== false;
    }

    private function isLogMode(): bool
    {
        return config('notification.force_log_mode', false);
    }

    private function sendEmail(string $to, string $subject, string $body, array $payload, int|array $queueId): string
    {
        $from = $this->config['from'] ?? config('mail.from.address', 'noreply@example.com');
        $fromName = $this->config['from_name'] ?? config('mail.from.name', 'Notification');

        if ($this->isLogMode()) {
            $logContext = [
                'mode' => 'LOG_MODE',
                'channel' => 'email',
                'queue_id' => $queueId,
                'from' => "{$fromName} <{$from}>",
                'to' => $to,
                'subject' => $subject,
                'body' => $body,
                'payload' => $payload,
                'would_send' => true,
            ];

            Log::channel('stack')->info('[NOTIFICATION:EMAIL] 발송 시뮬레이션 (LOG MODE)', $logContext);

            return 'email_log_'.uniqid();
        }

        Mail::raw($body, function ($message) use ($to, $subject, $from, $fromName) {
            $message->to($to)
                ->from($from, $fromName)
                ->subject($subject);
        });

        Log::info('Email notification sent', [
            'queue_id' => $queueId,
            'to' => $to,
            'subject' => $subject,
        ]);

        return 'mail_'.uniqid();
    }

    private function getDefaultSubject(string $type): string
    {
        return match ($type) {
            'welcome' => '환영합니다',
            'order_complete' => '주문이 완료되었습니다',
            'activity_digest' => '활동 요약',
            default => '알림',
        };
    }

    private function formatBatchBody(array $aggregatedPayload): string
    {
        if (isset($aggregatedPayload['body'])) {
            return $aggregatedPayload['body'];
        }

        $lines = [];

        if (isset($aggregatedPayload['summary'])) {
            $lines[] = "총 {$aggregatedPayload['summary']['total_count']}건의 알림";
            $lines[] = '';
        }

        if (isset($aggregatedPayload['items'])) {
            foreach ($aggregatedPayload['items'] as $index => $item) {
                $lines[] = ($index + 1).'. '.($item['message'] ?? json_encode($item));
            }
        }

        return implode("\n", $lines);
    }
}
