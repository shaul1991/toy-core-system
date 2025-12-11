<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Notification;

use App\Enums\Notification\DispatchType;
use App\Enums\Notification\NotificationStatus;
use App\Models\NotificationQueue;
use App\Services\Notification\BatchAggregator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchAggregatorTest extends TestCase
{
    use RefreshDatabase;

    private BatchAggregator $aggregator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->aggregator = new BatchAggregator;
    }

    public function test_aggregate_empty_collection_returns_empty_array(): void
    {
        $result = $this->aggregator->aggregate(collect());

        $this->assertEmpty($result);
    }

    public function test_aggregate_activity_digest(): void
    {
        $queues = collect([
            $this->createQueue('activity_digest', ['activity' => ['type' => 'comment', 'message' => '댓글1']]),
            $this->createQueue('activity_digest', ['activity' => ['type' => 'like', 'message' => '좋아요1']]),
            $this->createQueue('activity_digest', ['activity' => ['type' => 'comment', 'message' => '댓글2']]),
        ]);

        $result = $this->aggregator->aggregate($queues);

        $this->assertEquals('활동 요약', $result['title']);
        $this->assertCount(3, $result['items']);
        $this->assertEquals(3, $result['summary']['total_count']);
        $this->assertEquals(2, $result['summary']['by_type']['comment']);
        $this->assertEquals(1, $result['summary']['by_type']['like']);
    }

    public function test_aggregate_default_type(): void
    {
        $queues = collect([
            $this->createQueue('custom_notification', ['message' => '메시지1']),
            $this->createQueue('custom_notification', ['message' => '메시지2']),
        ]);

        $result = $this->aggregator->aggregate($queues);

        $this->assertEquals('알림 묶음', $result['title']);
        $this->assertCount(2, $result['items']);
        $this->assertEquals(2, $result['summary']['total_count']);
    }

    public function test_aggregate_order_updates(): void
    {
        $queues = collect([
            $this->createQueue('order_update', ['order_id' => 123, 'status' => 'shipped', 'message' => '배송중']),
            $this->createQueue('order_update', ['order_id' => 123, 'status' => 'delivered', 'message' => '배송완료']),
            $this->createQueue('order_update', ['order_id' => 456, 'status' => 'shipped', 'message' => '배송중']),
        ]);

        $result = $this->aggregator->aggregate($queues);

        $this->assertEquals('주문 업데이트', $result['title']);
        $this->assertCount(2, $result['items']); // 2개의 주문
        $this->assertEquals(3, $result['summary']['total_count']);
    }

    private function createQueue(string $type, array $payload): NotificationQueue
    {
        return NotificationQueue::create([
            'dispatch_type' => DispatchType::BATCHED,
            'batch_key' => 'test_batch',
            'batch_window' => 3600,
            'type' => $type,
            'channels' => ['email'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => $payload,
            'status' => NotificationStatus::PENDING,
        ]);
    }
}
