<?php

declare(strict_types=1);

namespace Tests\Feature\Bff;

use App\Domain\Auth\Services\JwtService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BFF Auth Controller 통합 테스트
 */
class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private JwtService $jwtService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->jwtService = app(JwtService::class);
    }

    /**
     * 토큰 갱신 테스트 - 성공
     */
    public function test_refresh_token_success(): void
    {
        // Given: 유효한 토큰 쌍 생성
        $tokenDto = $this->jwtService->createTokenPair($this->user);

        // When: 토큰 갱신 요청
        $response = $this->postJson('/bff/auth/refresh', [
            'refresh_token' => $tokenDto->refreshToken,
        ]);

        // Then: 새 토큰 쌍 반환
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
                    'user' => [
                        'id' => $this->user->id,
                        'email' => $this->user->email,
                    ],
                ],
            ]);
    }

    /**
     * 토큰 갱신 테스트 - refresh_token 누락
     */
    public function test_refresh_token_missing(): void
    {
        $response = $this->postJson('/bff/auth/refresh', []);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * 토큰 갱신 테스트 - 잘못된 refresh_token
     */
    public function test_refresh_token_invalid(): void
    {
        $response = $this->postJson('/bff/auth/refresh', [
            'refresh_token' => 'invalid-token',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * 토큰 검증 테스트 - 성공
     */
    public function test_validate_token_success(): void
    {
        // Given: 유효한 토큰 생성
        $tokenDto = $this->jwtService->createTokenPair($this->user);

        // When: 토큰 검증 요청
        $response = $this->postJson('/bff/auth/validate', [], [
            'Authorization' => "Bearer {$tokenDto->accessToken}",
        ]);

        // Then: 검증 성공
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'valid' => true,
                    'user' => [
                        'id' => $this->user->id,
                        'email' => $this->user->email,
                    ],
                ],
            ]);
    }

    /**
     * 토큰 검증 테스트 - 토큰 없음
     */
    public function test_validate_token_missing(): void
    {
        $response = $this->postJson('/bff/auth/validate');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * 현재 사용자 정보 조회 테스트 - 성공
     */
    public function test_me_success(): void
    {
        // Given: 유효한 토큰 생성
        $tokenDto = $this->jwtService->createTokenPair($this->user);

        // When: 사용자 정보 요청
        $response = $this->getJson('/bff/auth/me', [
            'Authorization' => "Bearer {$tokenDto->accessToken}",
        ]);

        // Then: 사용자 정보 반환
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
                    'updated_at',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ],
            ]);
    }

    /**
     * 현재 사용자 정보 조회 테스트 - 인증 없음
     */
    public function test_me_unauthorized(): void
    {
        $response = $this->getJson('/bff/auth/me');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * 로그아웃 테스트 - 성공
     */
    public function test_logout_success(): void
    {
        // Given: 유효한 토큰 생성
        $tokenDto = $this->jwtService->createTokenPair($this->user);

        // When: 로그아웃 요청
        $response = $this->postJson('/bff/auth/logout', [
            'refresh_token' => $tokenDto->refreshToken,
        ], [
            'Authorization' => "Bearer {$tokenDto->accessToken}",
        ]);

        // Then: 로그아웃 성공
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '로그아웃되었습니다.',
            ]);
    }

    /**
     * 전체 세션 로그아웃 테스트 - 성공
     */
    public function test_logout_all_success(): void
    {
        // Given: 유효한 토큰 생성
        $tokenDto = $this->jwtService->createTokenPair($this->user);

        // When: 전체 로그아웃 요청
        $response = $this->postJson('/bff/auth/logout-all', [], [
            'Authorization' => "Bearer {$tokenDto->accessToken}",
        ]);

        // Then: 전체 로그아웃 성공
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '모든 세션에서 로그아웃되었습니다.',
            ]);

        // And: 기존 토큰으로 접근 불가
        $this->getJson('/bff/auth/me', [
            'Authorization' => "Bearer {$tokenDto->accessToken}",
        ])->assertStatus(401);
    }

    /**
     * 소셜 로그인 리다이렉트 테스트
     */
    public function test_social_redirect(): void
    {
        $response = $this->get('/bff/auth/github/redirect');

        // OAuth URL로 리다이렉트
        $response->assertStatus(302);
        $this->assertStringContainsString('github.com', $response->headers->get('Location') ?? '');
    }

    /**
     * 지원하지 않는 소셜 제공자 테스트
     */
    public function test_unsupported_provider(): void
    {
        $response = $this->get('/bff/auth/facebook/redirect');

        $response->assertStatus(404);
    }
}
