<?php

namespace Tests\Unit\Auth;

use App\Domain\Auth\DTOs\TokenDTO;
use App\Domain\Auth\Exceptions\TokenException;
use App\Domain\Auth\Repositories\RefreshTokenRepositoryInterface;
use App\Domain\Auth\Services\JwtService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPOpenSourceSaver\JWTAuth\JWTAuth;
use Tests\TestCase;

class JwtServiceTest extends TestCase
{
    use RefreshDatabase;

    private JwtService $jwtService;

    private RefreshTokenRepositoryInterface $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
        $this->jwtService = new JwtService(
            app(JWTAuth::class),
            $this->mockRepository
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_token_pair_returns_valid_tokens(): void
    {
        $user = User::factory()->create();

        // Mock: Refresh Token 저장 (token_version 포함)
        $this->mockRepository
            ->shouldReceive('store')
            ->once()
            ->withArgs(function ($tokenId, $userId, $familyId, $ttl, $tokenVersion) use ($user) {
                return is_string($tokenId)
                    && $userId === $user->id
                    && is_string($familyId)
                    && $ttl === 604800
                    && $tokenVersion === $user->token_version;
            });

        $result = $this->jwtService->createTokenPair($user);

        $this->assertInstanceOf(TokenDTO::class, $result);
        $this->assertNotEmpty($result->accessToken);
        $this->assertNotEmpty($result->refreshToken);
        $this->assertEquals('bearer', $result->tokenType);
        $this->assertEquals(3600, $result->expiresIn);
        $this->assertEquals($user->id, $result->user->id);
    }

    public function test_refresh_token_pair_rotates_tokens(): void
    {
        $user = User::factory()->create();

        $oldRefreshToken = 'old-refresh-token-id';
        $familyId = 'test-family-id';

        // Mock: 기존 토큰 조회 (token_version 포함)
        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with($oldRefreshToken)
            ->andReturn([
                'user_id' => $user->id,
                'family' => $familyId,
                'token_version' => $user->token_version,
            ]);

        // Mock: 기존 토큰 삭제
        $this->mockRepository
            ->shouldReceive('delete')
            ->once()
            ->with($oldRefreshToken);

        // Mock: 새 토큰 저장 (token_version 포함)
        $this->mockRepository
            ->shouldReceive('store')
            ->once()
            ->withArgs(function ($tokenId, $userId, $newFamilyId, $ttl, $tokenVersion) use ($user, $familyId) {
                return is_string($tokenId)
                    && $userId === $user->id
                    && $newFamilyId === $familyId // 동일한 Family
                    && $ttl === 604800
                    && $tokenVersion === $user->token_version;
            });

        $result = $this->jwtService->refreshTokenPair($oldRefreshToken);

        $this->assertInstanceOf(TokenDTO::class, $result);
        $this->assertNotEmpty($result->accessToken);
        $this->assertNotEquals($oldRefreshToken, $result->refreshToken);
    }

    public function test_refresh_token_pair_throws_exception_when_token_not_found(): void
    {
        $invalidRefreshToken = 'invalid-token';

        // Mock: 토큰 조회 실패
        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with($invalidRefreshToken)
            ->andReturn(null);

        $this->expectException(TokenException::class);

        $this->jwtService->refreshTokenPair($invalidRefreshToken);
    }

    public function test_logout_blacklists_access_token(): void
    {
        $user = User::factory()->create();

        // 먼저 토큰 생성
        $this->mockRepository
            ->shouldReceive('store')
            ->once();

        $tokenDTO = $this->jwtService->createTokenPair($user);

        // Mock: Blacklist에 추가
        $this->mockRepository
            ->shouldReceive('blacklistAccessToken')
            ->once()
            ->withArgs(function ($jti, $ttl) {
                return is_string($jti) && $ttl > 0;
            });

        // Mock: Refresh Token 조회 및 삭제
        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->andReturn([
                'user_id' => $user->id,
                'family' => 'test-family',
                'token_version' => $user->token_version,
            ]);

        $this->mockRepository
            ->shouldReceive('delete')
            ->once();

        $this->jwtService->logout($tokenDTO->accessToken, $tokenDTO->refreshToken);

        // 예외가 발생하지 않으면 성공
        $this->assertTrue(true);
    }

    public function test_validate_access_token_returns_user(): void
    {
        $user = User::factory()->create();

        // 토큰 생성
        $this->mockRepository
            ->shouldReceive('store')
            ->once();

        $tokenDTO = $this->jwtService->createTokenPair($user);

        // Mock: Blacklist 확인
        $this->mockRepository
            ->shouldReceive('isAccessTokenBlacklisted')
            ->once()
            ->andReturn(false);

        $validatedUser = $this->jwtService->validateAccessToken($tokenDTO->accessToken);

        $this->assertEquals($user->id, $validatedUser->id);
    }

    public function test_validate_access_token_throws_exception_when_blacklisted(): void
    {
        $user = User::factory()->create();

        // 토큰 생성
        $this->mockRepository
            ->shouldReceive('store')
            ->once();

        $tokenDTO = $this->jwtService->createTokenPair($user);

        // Mock: Blacklist에 있음
        $this->mockRepository
            ->shouldReceive('isAccessTokenBlacklisted')
            ->once()
            ->andReturn(true);

        $this->expectException(TokenException::class);

        $this->jwtService->validateAccessToken($tokenDTO->accessToken);
    }

    public function test_validate_access_token_throws_exception_when_token_version_mismatch(): void
    {
        $user = User::factory()->create();
        $initialTokenVersion = $user->token_version;

        // 토큰 생성
        $this->mockRepository
            ->shouldReceive('store')
            ->once();

        $tokenDTO = $this->jwtService->createTokenPair($user);

        // 사용자의 token_version 증가 (모든 토큰 무효화)
        $user->invalidateAllTokens();

        // Mock: Blacklist 확인
        $this->mockRepository
            ->shouldReceive('isAccessTokenBlacklisted')
            ->once()
            ->andReturn(false);

        $this->expectException(TokenException::class);

        $this->jwtService->validateAccessToken($tokenDTO->accessToken);
    }

    public function test_logout_all_invalidates_all_tokens(): void
    {
        $user = User::factory()->create();
        $initialTokenVersion = $user->token_version;

        $this->jwtService->logoutAll($user);

        $user->refresh();
        $this->assertEquals($initialTokenVersion + 1, $user->token_version);
    }

    public function test_refresh_token_pair_throws_exception_after_logout_all(): void
    {
        $user = User::factory()->create();
        $initialTokenVersion = $user->token_version;

        $refreshToken = 'old-refresh-token-id';
        $familyId = 'test-family-id';

        // Mock: 기존 토큰 조회 - logoutAll 이전에 발급된 토큰 (낮은 token_version)
        $this->mockRepository
            ->shouldReceive('find')
            ->once()
            ->with($refreshToken)
            ->andReturn([
                'user_id' => $user->id,
                'family' => $familyId,
                'token_version' => $initialTokenVersion, // 이전 버전
            ]);

        // logoutAll 호출로 token_version 증가
        $user->invalidateAllTokens();
        $user->refresh();

        // Mock: 토큰 삭제 및 Family 무효화 (token_version 불일치 시)
        $this->mockRepository
            ->shouldReceive('delete')
            ->once()
            ->with($refreshToken);

        $this->mockRepository
            ->shouldReceive('invalidateFamily')
            ->once()
            ->with($familyId);

        $this->expectException(TokenException::class);

        $this->jwtService->refreshTokenPair($refreshToken);
    }
}
