<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use App\Enums\Notification\DispatchType;
use App\Enums\Notification\NotificationStatus;
use App\Models\NotificationQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_store_immediate_notification(): void
    {
        $response = $this->postJson('/api/notifications', [
            'dispatch_type' => 'immediate',
            'type' => 'welcome',
            'channels' => ['email'],
            'recipient' => [
                'email' => 'test@example.com',
            ],
            'payload' => [
                'message' => 'Welcome!',
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'dispatch_type' => 'immediate',
                    'type' => 'welcome',
                    'status' => 'pending',
                ],
            ]);

        $this->assertDatabaseHas('notification_queues', [
            'dispatch_type' => 'immediate',
            'type' => 'welcome',
            'status' => 'pending',
        ]);
    }

    public function test_store_scheduled_notification(): void
    {
        $scheduledAt = now()->addHour()->toIso8601String();

        $response = $this->postJson('/api/notifications', [
            'dispatch_type' => 'scheduled',
            'scheduled_at' => $scheduledAt,
            'type' => 'promotion',
            'channels' => ['email', 'sms'],
            'recipient' => [
                'email' => 'test@example.com',
                'phone' => '+821012345678',
            ],
            'payload' => [
                'title' => 'Big Sale!',
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'dispatch_type' => 'scheduled',
                    'type' => 'promotion',
                ],
            ]);
    }

    public function test_store_batched_notification(): void
    {
        $response = $this->postJson('/api/notifications', [
            'dispatch_type' => 'batched',
            'batch_key' => 'user:123:daily',
            'batch_window' => 3600,
            'type' => 'activity_digest',
            'channels' => ['email'],
            'recipient' => [
                'email' => 'test@example.com',
            ],
            'payload' => [
                'activity' => ['type' => 'comment'],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'dispatch_type' => 'batched',
                    'batch_key' => 'user:123:daily',
                ],
            ]);
    }

    public function test_store_validation_error_invalid_channel(): void
    {
        $response = $this->postJson('/api/notifications', [
            'dispatch_type' => 'immediate',
            'type' => 'test',
            'channels' => ['invalid_channel'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => [],
        ]);

        $response->assertStatus(400);
    }

    public function test_store_validation_error_missing_scheduled_at(): void
    {
        $response = $this->postJson('/api/notifications', [
            'dispatch_type' => 'scheduled',
            'type' => 'test',
            'channels' => ['email'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => [],
        ]);

        $response->assertStatus(400);
    }

    public function test_store_validation_error_missing_batch_key(): void
    {
        $response = $this->postJson('/api/notifications', [
            'dispatch_type' => 'batched',
            'type' => 'test',
            'channels' => ['email'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => [],
        ]);

        $response->assertStatus(400);
    }

    public function test_index_queue(): void
    {
        NotificationQueue::create([
            'dispatch_type' => DispatchType::IMMEDIATE,
            'type' => 'test1',
            'channels' => ['email'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => [],
            'status' => NotificationStatus::PENDING,
        ]);

        NotificationQueue::create([
            'dispatch_type' => DispatchType::SCHEDULED,
            'scheduled_at' => now()->addHour(),
            'type' => 'test2',
            'channels' => ['email'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => [],
            'status' => NotificationStatus::PENDING,
        ]);

        $response = $this->getJson('/api/notifications/queue');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(2, 'data');
    }

    public function test_index_queue_filter_by_dispatch_type(): void
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
            'dispatch_type' => DispatchType::SCHEDULED,
            'scheduled_at' => now()->addHour(),
            'type' => 'test',
            'channels' => ['email'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => [],
            'status' => NotificationStatus::PENDING,
        ]);

        $response = $this->getJson('/api/notifications/queue?dispatch_type=immediate');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_show_queue(): void
    {
        $queue = NotificationQueue::create([
            'dispatch_type' => DispatchType::IMMEDIATE,
            'type' => 'test',
            'channels' => ['email'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => ['message' => 'Hello'],
            'status' => NotificationStatus::PENDING,
        ]);

        $response = $this->getJson("/api/notifications/queue/{$queue->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $queue->id,
                    'type' => 'test',
                ],
            ]);
    }

    public function test_show_queue_not_found(): void
    {
        $response = $this->getJson('/api/notifications/queue/99999');

        $response->assertStatus(404);
    }

    public function test_cancel_queue(): void
    {
        $queue = NotificationQueue::create([
            'dispatch_type' => DispatchType::IMMEDIATE,
            'type' => 'test',
            'channels' => ['email'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => [],
            'status' => NotificationStatus::PENDING,
        ]);

        $response = $this->deleteJson("/api/notifications/queue/{$queue->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'cancelled',
                ],
            ]);
    }

    public function test_cancel_queue_fails_if_not_pending(): void
    {
        $queue = NotificationQueue::create([
            'dispatch_type' => DispatchType::IMMEDIATE,
            'type' => 'test',
            'channels' => ['email'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => [],
            'status' => NotificationStatus::DISPATCHED,
        ]);

        $response = $this->deleteJson("/api/notifications/queue/{$queue->id}");

        $response->assertStatus(400);
    }

    public function test_retry_queue(): void
    {
        $queue = NotificationQueue::create([
            'dispatch_type' => DispatchType::IMMEDIATE,
            'type' => 'test',
            'channels' => ['email'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => [],
            'status' => NotificationStatus::FAILED,
            'attempts' => 1,
            'last_error' => 'Previous error',
        ]);

        $response = $this->postJson("/api/notifications/queue/{$queue->id}/retry");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'pending',
                ],
            ]);
    }

    public function test_retry_queue_fails_if_not_failed(): void
    {
        $queue = NotificationQueue::create([
            'dispatch_type' => DispatchType::IMMEDIATE,
            'type' => 'test',
            'channels' => ['email'],
            'recipient' => ['email' => 'test@example.com'],
            'payload' => [],
            'status' => NotificationStatus::PENDING,
        ]);

        $response = $this->postJson("/api/notifications/queue/{$queue->id}/retry");

        $response->assertStatus(400);
    }

    public function test_channels_endpoint(): void
    {
        $response = $this->getJson('/api/notifications/channels');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data');
    }
}
