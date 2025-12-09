<?php

declare(strict_types=1);

namespace Tests\Feature\Timer;

use App\Models\Timer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TimerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_timer(): void
    {
        $response = $this->putJson('/api/timers/event-countdown', [
            'target_at' => '2025-12-31T23:59:59+09:00',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'key' => 'event-countdown',
                ],
            ]);

        $this->assertDatabaseHas('timers', [
            'key' => 'event-countdown',
        ]);
    }

    public function test_can_update_existing_timer(): void
    {
        Timer::create([
            'key' => 'update-timer',
            'target_at' => '2025-01-01 00:00:00',
        ]);

        $response = $this->putJson('/api/timers/update-timer', [
            'target_at' => '2025-12-31T23:59:59+09:00',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'key' => 'update-timer',
                ],
            ]);

        $timer = Timer::where('key', 'update-timer')->first();
        $this->assertEquals(2025, $timer->target_at->year);
        $this->assertEquals(12, $timer->target_at->month);
        $this->assertEquals(31, $timer->target_at->day);
    }

    public function test_can_restore_and_update_soft_deleted_timer(): void
    {
        $timer = Timer::create([
            'key' => 'restore-timer',
            'target_at' => '2025-01-01 00:00:00',
        ]);
        $timer->delete();

        $response = $this->putJson('/api/timers/restore-timer', [
            'target_at' => '2025-12-31T23:59:59+09:00',
        ]);

        $response->assertStatus(200);

        $restoredTimer = Timer::where('key', 'restore-timer')->first();
        $this->assertNotNull($restoredTimer);
        $this->assertNull($restoredTimer->deleted_at);
    }

    public function test_create_timer_requires_target_at(): void
    {
        $response = $this->putJson('/api/timers/no-target', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_at']);
    }

    public function test_create_timer_requires_valid_date(): void
    {
        $response = $this->putJson('/api/timers/invalid-date', [
            'target_at' => 'not-a-date',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_at']);
    }

    public function test_can_get_timer_with_remaining_seconds(): void
    {
        Carbon::setTestNow('2025-01-01 00:00:00');

        Timer::create([
            'key' => 'get-timer',
            'target_at' => '2025-01-01 01:00:00', // 1 hour later
        ]);

        $response = $this->getJson('/api/timers/get-timer');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'key' => 'get-timer',
                    'remaining_seconds' => -3600, // Negative = time remaining
                ],
            ]);

        Carbon::setTestNow();
    }

    public function test_remaining_seconds_is_positive_when_timer_expired(): void
    {
        Carbon::setTestNow('2025-01-01 02:00:00');

        Timer::create([
            'key' => 'expired-timer',
            'target_at' => '2025-01-01 01:00:00', // 1 hour ago
        ]);

        $response = $this->getJson('/api/timers/expired-timer');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'key' => 'expired-timer',
                    'remaining_seconds' => 3600, // Positive = time elapsed
                ],
            ]);

        Carbon::setTestNow();
    }

    public function test_get_timer_returns_404_when_not_found(): void
    {
        $response = $this->getJson('/api/timers/nonexistent');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                ],
            ]);
    }

    public function test_can_delete_timer(): void
    {
        Timer::create([
            'key' => 'delete-timer',
            'target_at' => '2025-12-31 23:59:59',
        ]);

        $response = $this->deleteJson('/api/timers/delete-timer');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'key' => 'delete-timer',
                ],
            ]);

        $this->assertSoftDeleted('timers', [
            'key' => 'delete-timer',
        ]);
    }

    public function test_delete_timer_returns_404_when_not_found(): void
    {
        $response = $this->deleteJson('/api/timers/nonexistent');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                ],
            ]);
    }

    public function test_deleted_timer_is_not_accessible_via_get(): void
    {
        $timer = Timer::create([
            'key' => 'soft-deleted-timer',
            'target_at' => '2025-12-31 23:59:59',
        ]);
        $timer->delete();

        $response = $this->getJson('/api/timers/soft-deleted-timer');

        $response->assertStatus(404);
    }

    public function test_response_includes_target_at_in_iso8601_format(): void
    {
        $response = $this->putJson('/api/timers/iso-timer', [
            'target_at' => '2025-12-31T23:59:59+00:00',
        ]);

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('target_at', $data);
        // ISO8601 format check
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/',
            $data['target_at']
        );
    }
}
