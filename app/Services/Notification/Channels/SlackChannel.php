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

class SlackChannel implements NotificationChannelInterface
{
    public function __construct(
        private readonly array $config = [],
    ) {}

    public function getType(): ChannelType
    {
        return ChannelType::SLACK;
    }

    public function send(NotificationQueue $queue): ChannelResult
    {
        try {
            $recipient = $queue->recipient;
            $payload = $queue->payload;

            if (! $this->validateRecipient($recipient)) {
                return ChannelResult::failure(
                    channel: 'slack',
                    error: '유효한 Slack webhook URL이 없습니다.'
                );
            }

            $webhookUrl = $recipient['slack_webhook'];
            $message = $this->formatMessage($queue->type, $payload);

            $this->sendToSlack($webhookUrl, $message, $queue->id);

            return ChannelResult::success(
                channel: 'slack',
                messageId: 'slack_'.uniqid(),
                metadata: ['type' => $queue->type]
            );
        } catch (Throwable $e) {
            Log::error('Slack notification failed', [
                'queue_id' => $queue->id,
                'error' => $e->getMessage(),
            ]);

            return ChannelResult::failure(
                channel: 'slack',
                error: $e->getMessage()
            );
        }
    }

    public function sendBatch(array $queues, array $aggregatedPayload): ChannelResult
    {
        try {
            if (empty($queues)) {
                return ChannelResult::failure(
                    channel: 'slack',
                    error: '발송할 알림이 없습니다.'
                );
            }

            $firstQueue = $queues[0];
            $recipient = $firstQueue->recipient;

            if (! $this->validateRecipient($recipient)) {
                return ChannelResult::failure(
                    channel: 'slack',
                    error: '유효한 Slack webhook URL이 없습니다.'
                );
            }

            $webhookUrl = $recipient['slack_webhook'];
            $message = $this->formatBatchMessage($firstQueue->type, $aggregatedPayload, count($queues));
            $queueIds = array_map(fn ($q) => $q->id, $queues);

            $this->sendToSlack($webhookUrl, $message, $queueIds);

            return ChannelResult::success(
                channel: 'slack',
                messageId: 'slack_'.uniqid(),
                metadata: [
                    'type' => $firstQueue->type,
                    'batch_count' => count($queues),
                ]
            );
        } catch (Throwable $e) {
            Log::error('Slack batch notification failed', [
                'queue_ids' => array_map(fn ($q) => $q->id, $queues),
                'error' => $e->getMessage(),
            ]);

            return ChannelResult::failure(
                channel: 'slack',
                error: $e->getMessage()
            );
        }
    }

    public function validateRecipient(array $recipient): bool
    {
        if (! isset($recipient['slack_webhook'])) {
            return false;
        }

        return str_starts_with($recipient['slack_webhook'], 'https://hooks.slack.com/');
    }

    private function isLogMode(): bool
    {
        if (config('notification.force_log_mode', false)) {
            return true;
        }

        $provider = $this->config['provider'] ?? 'webhook';

        return $provider === 'log';
    }

    private function formatMessage(string $type, array $payload): array
    {
        $text = $payload['message'] ?? $payload['body'] ?? '';
        $title = $payload['title'] ?? $this->getDefaultTitle($type);

        return [
            'text' => $title,
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => $title,
                    ],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => $text,
                    ],
                ],
            ],
        ];
    }

    private function formatBatchMessage(string $type, array $aggregatedPayload, int $count): array
    {
        $title = ($aggregatedPayload['title'] ?? $this->getDefaultTitle($type))." ({$count}건)";
        $blocks = [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => $title,
                ],
            ],
        ];

        if (isset($aggregatedPayload['summary'])) {
            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "총 *{$aggregatedPayload['summary']['total_count']}건*의 알림",
                ],
            ];
        }

        if (isset($aggregatedPayload['items'])) {
            $itemTexts = [];
            foreach (array_slice($aggregatedPayload['items'], 0, 10) as $item) {
                $itemTexts[] = '• '.($item['message'] ?? $item['title'] ?? $item['subject'] ?? '(항목)');
            }

            if (count($aggregatedPayload['items']) > 10) {
                $remaining = count($aggregatedPayload['items']) - 10;
                $itemTexts[] = "... 외 {$remaining}건";
            }

            $blocks[] = [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => implode("\n", $itemTexts),
                ],
            ];
        }

        return [
            'text' => $title,
            'blocks' => $blocks,
        ];
    }

    private function getDefaultTitle(string $type): string
    {
        return match ($type) {
            'welcome' => '환영합니다',
            'order_complete' => '주문 완료',
            'activity_digest' => '활동 요약',
            'error_alert' => '오류 알림',
            default => '알림',
        };
    }

    private function sendToSlack(string $webhookUrl, array $message, int|array $queueId): void
    {
        if ($this->isLogMode()) {
            $logContext = [
                'mode' => 'LOG_MODE',
                'channel' => 'slack',
                'queue_id' => $queueId,
                'webhook_url' => $this->maskWebhookUrl($webhookUrl),
                'message' => $message,
                'would_send' => true,
            ];

            Log::channel('stack')->info('[NOTIFICATION:SLACK] 발송 시뮬레이션 (LOG MODE)', $logContext);

            return;
        }

        $timeout = $this->config['timeout'] ?? config('notification.channels.slack.timeout', 5);

        try {
            $response = Http::timeout($timeout)->post($webhookUrl, $message);

            if (! $response->successful()) {
                throw new RuntimeException(
                    "Slack webhook 호출 실패: status={$response->status()}, body={$response->body()}"
                );
            }

            Log::info('Slack notification sent', [
                'queue_id' => $queueId,
                'webhook_url' => $this->maskWebhookUrl($webhookUrl),
            ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                "Slack webhook 연결 실패: {$e->getMessage()} (timeout={$timeout}s)",
                0,
                $e
            );
        }
    }

    private function maskWebhookUrl(string $webhookUrl): string
    {
        // Slack webhook URL을 마스킹하여 민감 정보 보호
        // 예: .../services/T00.../B00.../XXXX...
        if (preg_match('#^(https://hooks\.slack\.com/services/)([^/]+)/([^/]+)/(.+)$#', $webhookUrl, $matches)) {
            $t = substr($matches[2], 0, 3).'...';
            $b = substr($matches[3], 0, 3).'...';
            $x = substr($matches[4], 0, 4).'...';

            return $matches[1].$t.'/'.$b.'/'.$x;
        }

        return substr($webhookUrl, 0, 40).'...';
    }
}
