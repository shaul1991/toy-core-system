<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\Notification\ChannelType;
use App\Enums\Notification\DispatchType;
use App\Enums\Notification\NotificationStatus;
use App\Models\NotificationQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_immediate_notification(): void
    {
        $queue = NotificationQueue::create([
            'dispatch_type' => DispatchType::IMMEDIATE,
            'type' => 'welcome',
            'channels' => ['email'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => ['message' => 'Hello'],
            'status' => NotificationStatus::PENDING,
        ]);

        $this->assertDatabaseHas('notification_queues', [
            'id' => $queue->id,
            'dispatch_type' => 'immediate',
            'type' => 'welcome',
            'status' => 'pending',
        ]);
    }

    public function test_can_create_scheduled_notification(): void
    {
        $scheduledAt = now()->addHour();

        $queue = NotificationQueue::create([
            'dispatch_type' => DispatchType::SCHEDULED,
            'scheduled_at' => $scheduledAt,
            'type' => 'promotion',
            'channels' => ['email', 'sms'],
            'recipient' => [
                'email' => 'test@example.com',
                'phone' => '+821012345678',
            ],
            'payload' => ['title' => 'Promo'],
            'status' => NotificationStatus::PENDING,
        ]);

        $this->assertEquals(DispatchType::SCHEDULED, $queue->dispatch_type);
        $this->assertNotNull($queue->scheduled_at);
    }

    public function test_can_create_batched_notification(): void
    {
        $queue = NotificationQueue::create([
            'dispatch_type' => DispatchType::BATCHED,
            'batch_key' => 'user:123:activity',
            'batch_window' => 3600,
            'type' => 'activity_digest',
            'channels' => ['email'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => ['activity' => ['type' => 'comment']],
            'status' => NotificationStatus::PENDING,
        ]);

        $this->assertEquals('user:123:activity', $queue->batch_key);
        $this->assertEquals(3600, $queue->batch_window);
    }

    public function test_casts_channels_to_array(): void
    {
        $queue = NotificationQueue::create([
            'dispatch_type' => DispatchType::IMMEDIATE,
            'type' => 'test',
            'channels' => ['email', 'sms', 'slack'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => [],
            'status' => NotificationStatus::PENDING,
        ]);

        $this->assertIsArray($queue->channels);
        $this->assertCount(3, $queue->channels);
        $this->assertContains('email', $queue->channels);
    }

    public function test_get_channel_types_returns_enum_array(): void
    {
        $queue = NotificationQueue::create([
            'dispatch_type' => DispatchType::IMMEDIATE,
            'type' => 'test',
            'channels' => ['email', 'sms'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => [],
            'status' => NotificationStatus::PENDING,
        ]);

        $channelTypes = $queue->getChannelTypes();

        $this->assertCount(2, $channelTypes);
        $this->assertContainsOnlyInstancesOf(ChannelType::class, $channelTypes);
    }

    public function test_status_checks(): void
    {
        $queue = NotificationQueue::create([
            'dispatch_type' => DispatchType::IMMEDIATE,
            'type' => 'test',
            'channels' => ['email'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => [],
            'status' => NotificationStatus::PENDING,
        ]);

        $this->assertTrue($queue->isPending());
        $this->assertFalse($queue->isProcessing());
        $this->assertTrue($queue->canCancel());
        $this->assertFalse($queue->canRetry());
    }

    public function test_mark_as_processing(): void
    {
        $queue = NotificationQueue::create([
            'dispatch_type' => DispatchType::IMMEDIATE,
            'type' => 'test',
            'channels' => ['email'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => [],
            'status' => NotificationStatus::PENDING,
            'attempts' => 0,
        ]);

        $queue->markAsProcessing();

        $this->assertEquals(NotificationStatus::PROCESSING, $queue->status);
        $this->assertEquals(1, $queue->attempts);
    }

    public function test_mark_as_dispatched(): void
    {
        $queue = NotificationQueue::create([
            'dispatch_type' => DispatchType::IMMEDIATE,
            'type' => 'test',
            'channels' => ['email'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => [],
            'status' => NotificationStatus::PROCESSING,
        ]);

        $queue->markAsDispatched();

        $this->assertEquals(NotificationStatus::DISPATCHED, $queue->status);
        $this->assertNotNull($queue->dispatched_at);
    }

    public function test_mark_as_failed(): void
    {
        $queue = NotificationQueue::create([
            'dispatch_type' => DispatchType::IMMEDIATE,
            'type' => 'test',
            'channels' => ['email'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => [],
            'status' => NotificationStatus::PROCESSING,
        ]);

        $queue->markAsFailed('Connection timeout');

        $this->assertEquals(NotificationStatus::FAILED, $queue->status);
        $this->assertEquals('Connection timeout', $queue->last_error);
    }

    public function test_scope_pending(): void
    {
        NotificationQueue::create([
            'dispatch_type' => DispatchType::IMMEDIATE,
            'type' => 'test',
            'channels' => ['email'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => [],
            'status' => NotificationStatus::PENDING,
        ]);

        NotificationQueue::create([
            'dispatch_type' => DispatchType::IMMEDIATE,
            'type' => 'test',
            'channels' => ['email'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => [],
            'status' => NotificationStatus::DISPATCHED,
        ]);

        $pending = NotificationQueue::pending()->get();

        $this->assertCount(1, $pending);
    }

    public function test_scope_scheduled_ready(): void
    {
        // 발송 시간이 지난 예약 알림
        NotificationQueue::create([
            'dispatch_type' => DispatchType::SCHEDULED,
            'scheduled_at' => now()->subMinute(),
            'type' => 'test',
            'channels' => ['email'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => [],
            'status' => NotificationStatus::PENDING,
        ]);

        // 아직 발송 시간이 안된 예약 알림
        NotificationQueue::create([
            'dispatch_type' => DispatchType::SCHEDULED,
            'scheduled_at' => now()->addHour(),
            'type' => 'test',
            'channels' => ['email'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => [],
            'status' => NotificationStatus::PENDING,
        ]);

        $ready = NotificationQueue::scheduledReady()->get();

        $this->assertCount(1, $ready);
    }
}
