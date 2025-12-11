<?php

namespace Tests\Unit\Auth;

use App\Domain\Auth\Observers\UserObserver;
use App\Domain\Auth\Services\UserCacheServiceInterface;
use App\Models\User;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class UserObserverTest extends TestCase
{
    private MockInterface $mockCacheService;

    private UserObserver $observer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockCacheService = Mockery::mock(UserCacheServiceInterface::class);
        $this->observer = new UserObserver($this->mockCacheService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_updated_invalidates_user_cache(): void
    {
        $user = new User;
        $user->id = 123;

        $this->mockCacheService
            ->shouldReceive('invalidate')
            ->once()
            ->with(123);

        $this->observer->updated($user);

        // Mockery expectations verified in tearDown
        $this->addToAssertionCount(1);
    }

    public function test_deleted_invalidates_user_cache(): void
    {
        $user = new User;
        $user->id = 456;

        $this->mockCacheService
            ->shouldReceive('invalidate')
            ->once()
            ->with(456);

        $this->observer->deleted($user);

        // Mockery expectations verified in tearDown
        $this->addToAssertionCount(1);
    }
}
