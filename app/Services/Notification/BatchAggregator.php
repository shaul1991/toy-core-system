<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\NotificationQueue;
use Illuminate\Support\Collection;

class BatchAggregator
{
    public function aggregate(Collection $queues): array
    {
        if ($queues->isEmpty()) {
            return [];
        }

        $firstQueue = $queues->first();
        $type = $firstQueue->type;

        $items = $queues->map(fn (NotificationQueue $queue) => $queue->payload)->toArray();

        $summary = [
            'total_count' => $queues->count(),
            'first_created_at' => $queues->min('created_at'),
            'last_created_at' => $queues->max('created_at'),
        ];

        $byType = [];
        foreach ($items as $item) {
            $itemType = $item['type'] ?? 'unknown';
            $byType[$itemType] = ($byType[$itemType] ?? 0) + 1;
        }
        $summary['by_type'] = $byType;

        return match ($type) {
            'activity_digest' => $this->aggregateActivityDigest($items, $summary),
            'order_update' => $this->aggregateOrderUpdates($items, $summary),
            default => $this->aggregateDefault($items, $summary),
        };
    }

    private function aggregateActivityDigest(array $items, array $summary): array
    {
        $activities = [];

        foreach ($items as $item) {
            if (isset($item['activity'])) {
                $activities[] = $item['activity'];
            } elseif (isset($item['activities'])) {
                $activities = array_merge($activities, $item['activities']);
            } else {
                $activities[] = $item;
            }
        }

        return [
            'title' => '활동 요약',
            'subject' => "새로운 활동 {$summary['total_count']}건",
            'message' => "{$summary['total_count']}건의 새로운 활동이 있습니다.",
            'items' => $activities,
            'summary' => $summary,
        ];
    }

    private function aggregateOrderUpdates(array $items, array $summary): array
    {
        $orders = [];

        foreach ($items as $item) {
            $orderId = $item['order_id'] ?? null;
            if ($orderId !== null) {
                if (! isset($orders[$orderId])) {
                    $orders[$orderId] = [
                        'order_id' => $orderId,
                        'updates' => [],
                    ];
                }
                $orders[$orderId]['updates'][] = [
                    'status' => $item['status'] ?? 'unknown',
                    'message' => $item['message'] ?? '',
                ];
            }
        }

        return [
            'title' => '주문 업데이트',
            'subject' => "주문 업데이트 {$summary['total_count']}건",
            'message' => count($orders).'개 주문에 대한 '.$summary['total_count'].'건의 업데이트가 있습니다.',
            'items' => array_values($orders),
            'summary' => $summary,
        ];
    }

    private function aggregateDefault(array $items, array $summary): array
    {
        $messages = [];

        foreach ($items as $item) {
            $message = $item['message'] ?? $item['body'] ?? $item['content'] ?? null;
            if ($message !== null) {
                $messages[] = ['message' => $message];
            } else {
                $messages[] = $item;
            }
        }

        return [
            'title' => '알림 묶음',
            'subject' => "새로운 알림 {$summary['total_count']}건",
            'message' => "{$summary['total_count']}건의 새로운 알림이 있습니다.",
            'items' => $messages,
            'summary' => $summary,
        ];
    }
}
