<?php

namespace Tests\Feature\Auth;

use App\Domain\Auth\Repositories\RefreshTokenRepositoryInterface;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private RefreshTokenRepositoryInterface $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
        $this->app->instance(RefreshTokenRepositoryInterface::class, $this->mockRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_refresh_endpoint_returns_new_tokens(): void
    {
        $user = User::factory()->create([
            'token_version' => 1,
        ]);

        $oldRefreshToken = 'old-refresh-token';
        $familyId = 'test-family-id';

        // Mock: 기존 토큰 조회
        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with($oldRefreshToken)
            ->andReturn([
                'user_id' => $user->id,
                'family' => $familyId,
            ]);

        // Mock: 기존 토큰 삭제
        $this->mockRepository
            ->shouldReceive('delete')
            ->once()
            ->with($oldRefreshToken);

        // Mock: 새 토큰 저장
        $this->mockRepository
            ->shouldReceive('store')
            ->once();

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $oldRefreshToken,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'access_token',
                    'refresh_token',
                    'token_type',
                    'expires_in',
                    'user' => [
                        'id',
                        'name',
                        'email',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'token_type' => 'bearer',
                    'expires_in' => 3600,
                ],
            ]);
    }

    public function test_refresh_endpoint_returns_401_for_invalid_token(): void
    {
        // Mock: 토큰 조회 실패
        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->andReturn(null);

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => 'invalid-token',
        ]);

        $response->assertStatus(401);
    }

    public function test_refresh_endpoint_requires_refresh_token(): void
    {
        $response = $this->postJson('/api/auth/refresh', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['refresh_token']);
    }

    public function test_me_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    public function test_me_endpoint_returns_user_info(): void
    {
        $user = User::factory()->create([
            'token_version' => 1,
        ]);

        // Mock: 토큰 저장
        $this->mockRepository
            ->shouldReceive('store')
            ->once();

        // 토큰 생성
        $token = auth('api')->login($user);

        // Mock: Blacklist 확인
        $this->mockRepository
            ->shouldReceive('isAccessTokenBlacklisted')
            ->andReturn(false);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'avatar',
                    'email_verified_at',
                    'created_at',
                    'social_accounts',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ]);
    }

    public function test_logout_endpoint_invalidates_tokens(): void
    {
        $user = User::factory()->create([
            'token_version' => 1,
        ]);

        // Mock: 토큰 저장
        $this->mockRepository
            ->shouldReceive('store')
            ->once();

        // 토큰 생성
        $token = auth('api')->login($user);

        // Mock: Blacklist에 추가
        $this->mockRepository
            ->shouldReceive('blacklistAccessToken')
            ->once();

        // Mock: Refresh Token 조회 및 삭제 (없으면 무시)
        $this->mockRepository
            ->shouldReceive('find')
            ->andReturn(null);

        // Mock: Blacklist 확인 (me 호출 시)
        $this->mockRepository
            ->shouldReceive('isAccessTokenBlacklisted')
            ->andReturn(false);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => '로그아웃되었습니다.',
                ],
            ]);
    }

    public function test_logout_all_endpoint_invalidates_all_sessions(): void
    {
        $user = User::factory()->create([
            'token_version' => 1,
        ]);

        // Mock: 토큰 저장
        $this->mockRepository
            ->shouldReceive('store')
            ->once();

        // 토큰 생성
        $token = auth('api')->login($user);

        // Mock: Blacklist 확인
        $this->mockRepository
            ->shouldReceive('isAccessTokenBlacklisted')
            ->andReturn(false);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout-all');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'message' => '모든 디바이스에서 로그아웃되었습니다.',
                ],
            ]);

        // token_version이 증가했는지 확인
        $user->refresh();
        $this->assertEquals(2, $user->token_version);
    }

    public function test_validate_endpoint_returns_valid_for_good_token(): void
    {
        $user = User::factory()->create([
            'token_version' => 1,
        ]);

        // Mock: 토큰 저장
        $this->mockRepository
            ->shouldReceive('store')
            ->once();

        // 토큰 생성
        $token = auth('api')->login($user);

        // Mock: Blacklist 확인
        $this->mockRepository
            ->shouldReceive('isAccessTokenBlacklisted')
            ->andReturn(false);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/validate');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'valid' => true,
                    'user_id' => $user->id,
                ],
            ]);
    }
}
