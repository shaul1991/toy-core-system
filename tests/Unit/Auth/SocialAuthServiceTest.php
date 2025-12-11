<?php

namespace Tests\Unit\Auth;

use App\Domain\Auth\DTOs\TokenDTO;
use App\Domain\Auth\Repositories\RefreshTokenRepositoryInterface;
use App\Domain\Auth\Services\SocialAuthService;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class SocialAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private SocialAuthService $socialAuthService;

    private RefreshTokenRepositoryInterface $mockRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockRepository = Mockery::mock(RefreshTokenRepositoryInterface::class);
        $this->app->instance(RefreshTokenRepositoryInterface::class, $this->mockRepository);

        $this->socialAuthService = $this->app->make(SocialAuthService::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_is_provider_supported_returns_true_for_valid_providers(): void
    {
        $this->assertTrue($this->socialAuthService->isProviderSupported('github'));
        $this->assertTrue($this->socialAuthService->isProviderSupported('naver'));
        $this->assertTrue($this->socialAuthService->isProviderSupported('kakao'));
    }

    public function test_is_provider_supported_returns_false_for_invalid_provider(): void
    {
        $this->assertFalse($this->socialAuthService->isProviderSupported('facebook'));
        $this->assertFalse($this->socialAuthService->isProviderSupported('google'));
        $this->assertFalse($this->socialAuthService->isProviderSupported('invalid'));
    }

    public function test_handle_callback_creates_new_user_when_not_exists(): void
    {
        // Mock Socialite
        $mockSocialiteUser = $this->createMockSocialiteUser([
            'id' => '12345',
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'avatar' => 'https://example.com/avatar.jpg',
        ]);

        Socialite::shouldReceive('driver')
            ->with('github')
            ->andReturnSelf();
        Socialite::shouldReceive('stateless')
            ->andReturnSelf();
        Socialite::shouldReceive('user')
            ->andReturn($mockSocialiteUser);

        // Mock: Refresh Token 저장
        $this->mockRepository
            ->shouldReceive('store')
            ->once();

        $result = $this->socialAuthService->handleCallback('github');

        $this->assertInstanceOf(TokenDTO::class, $result);
        $this->assertNotEmpty($result->accessToken);
        $this->assertNotEmpty($result->refreshToken);

        // 사용자가 생성되었는지 확인
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
        ]);

        // 소셜 계정이 연동되었는지 확인
        $this->assertDatabaseHas('social_accounts', [
            'provider' => 'github',
            'provider_user_id' => '12345',
        ]);
    }

    public function test_handle_callback_generates_temporary_email_when_null(): void
    {
        // Mock Socialite - 이메일이 null인 경우 (예: Kakao)
        $mockSocialiteUser = $this->createMockSocialiteUser([
            'id' => '88888',
            'email' => null,
            'name' => 'Kakao User',
            'avatar' => 'https://example.com/kakao-avatar.jpg',
        ]);

        Socialite::shouldReceive('driver')
            ->with('kakao')
            ->andReturnSelf();
        Socialite::shouldReceive('stateless')
            ->andReturnSelf();
        Socialite::shouldReceive('user')
            ->andReturn($mockSocialiteUser);

        // Mock: Refresh Token 저장
        $this->mockRepository
            ->shouldReceive('store')
            ->once();

        $result = $this->socialAuthService->handleCallback('kakao');

        $this->assertInstanceOf(TokenDTO::class, $result);

        // 임시 이메일이 생성되었는지 확인
        $this->assertDatabaseHas('users', [
            'email' => 'kakao_88888@noemail.local',
            'name' => 'Kakao User',
        ]);

        // 소셜 계정이 연동되었는지 확인 (provider_email은 null)
        $this->assertDatabaseHas('social_accounts', [
            'provider' => 'kakao',
            'provider_user_id' => '88888',
            'provider_email' => null,
        ]);
    }

    public function test_handle_callback_links_to_existing_user_by_email(): void
    {
        // 기존 사용자 생성
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        // Mock Socialite
        $mockSocialiteUser = $this->createMockSocialiteUser([
            'id' => '67890',
            'email' => 'existing@example.com',
            'name' => 'Social Name',
        ]);

        Socialite::shouldReceive('driver')
            ->with('naver')
            ->andReturnSelf();
        Socialite::shouldReceive('stateless')
            ->andReturnSelf();
        Socialite::shouldReceive('user')
            ->andReturn($mockSocialiteUser);

        // Mock: Refresh Token 저장
        $this->mockRepository
            ->shouldReceive('store')
            ->once();

        $result = $this->socialAuthService->handleCallback('naver');

        $this->assertInstanceOf(TokenDTO::class, $result);
        $this->assertEquals($existingUser->id, $result->user->id);

        // 소셜 계정이 기존 사용자에 연동되었는지 확인
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $existingUser->id,
            'provider' => 'naver',
            'provider_user_id' => '67890',
        ]);
    }

    public function test_handle_callback_logs_in_existing_social_account(): void
    {
        // 기존 사용자 및 소셜 계정 생성
        $existingUser = User::factory()->create([
            'email' => 'user@example.com',
        ]);

        SocialAccount::create([
            'user_id' => $existingUser->id,
            'provider' => 'kakao',
            'provider_user_id' => '11111',
            'provider_email' => 'user@kakao.com',
        ]);

        // Mock Socialite
        $mockSocialiteUser = $this->createMockSocialiteUser([
            'id' => '11111',
            'email' => 'user@kakao.com',
            'name' => 'Kakao User',
        ]);

        Socialite::shouldReceive('driver')
            ->with('kakao')
            ->andReturnSelf();
        Socialite::shouldReceive('stateless')
            ->andReturnSelf();
        Socialite::shouldReceive('user')
            ->andReturn($mockSocialiteUser);

        // Mock: Refresh Token 저장
        $this->mockRepository
            ->shouldReceive('store')
            ->once();

        $result = $this->socialAuthService->handleCallback('kakao');

        $this->assertEquals($existingUser->id, $result->user->id);

        // 새 소셜 계정이 생성되지 않았는지 확인
        $this->assertEquals(1, SocialAccount::where('user_id', $existingUser->id)->count());
    }

    public function test_link_account_links_social_to_current_user(): void
    {
        $user = User::factory()->create();

        // Mock Socialite
        $mockSocialiteUser = $this->createMockSocialiteUser([
            'id' => '99999',
            'email' => 'social@github.com',
            'name' => 'GitHub User',
        ]);

        Socialite::shouldReceive('driver')
            ->with('github')
            ->andReturnSelf();
        Socialite::shouldReceive('stateless')
            ->andReturnSelf();
        Socialite::shouldReceive('user')
            ->andReturn($mockSocialiteUser);

        $socialAccount = $this->socialAuthService->linkAccount($user, 'github');

        $this->assertEquals($user->id, $socialAccount->user_id);
        $this->assertEquals('github', $socialAccount->provider);
        $this->assertEquals('99999', $socialAccount->provider_user_id);
    }

    public function test_link_account_throws_exception_when_already_linked_to_other_user(): void
    {
        // 다른 사용자에게 이미 연동된 소셜 계정
        $otherUser = User::factory()->create();
        SocialAccount::create([
            'user_id' => $otherUser->id,
            'provider' => 'github',
            'provider_user_id' => '77777',
        ]);

        $currentUser = User::factory()->create();

        // Mock Socialite
        $mockSocialiteUser = $this->createMockSocialiteUser([
            'id' => '77777', // 다른 사용자에게 이미 연동된 ID
            'email' => 'same@github.com',
        ]);

        Socialite::shouldReceive('driver')
            ->with('github')
            ->andReturnSelf();
        Socialite::shouldReceive('stateless')
            ->andReturnSelf();
        Socialite::shouldReceive('user')
            ->andReturn($mockSocialiteUser);

        $this->expectException(\App\Shared\Exceptions\ConflictException::class);

        $this->socialAuthService->linkAccount($currentUser, 'github');
    }

    public function test_unlink_account_removes_social_account(): void
    {
        $user = User::factory()->create([
            'password' => 'hashedpassword', // 비밀번호가 있으므로 연동 해제 가능
        ]);

        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_user_id' => '12345',
        ]);

        $this->socialAuthService->unlinkAccount($user, 'github');

        $this->assertDatabaseMissing('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'github',
        ]);
    }

    public function test_unlink_account_throws_exception_when_last_login_method(): void
    {
        $user = User::factory()->create([
            'password' => null, // 비밀번호 없음
        ]);

        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_user_id' => '12345',
        ]);

        $this->expectException(\App\Shared\Exceptions\BadRequestException::class);

        $this->socialAuthService->unlinkAccount($user, 'github');
    }

    public function test_get_linked_accounts_returns_all_social_accounts(): void
    {
        $user = User::factory()->create();

        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'github',
            'provider_user_id' => '111',
            'provider_email' => 'user@github.com',
        ]);

        SocialAccount::create([
            'user_id' => $user->id,
            'provider' => 'kakao',
            'provider_user_id' => '222',
            'provider_email' => 'user@kakao.com',
        ]);

        $accounts = $this->socialAuthService->getLinkedAccounts($user);

        $this->assertCount(2, $accounts);
        $this->assertEquals('github', $accounts[0]['provider']);
        $this->assertEquals('kakao', $accounts[1]['provider']);
    }

    public function test_unsupported_provider_throws_exception(): void
    {
        $this->expectException(\App\Shared\Exceptions\BadRequestException::class);

        $this->socialAuthService->getRedirectUrl('facebook');
    }

    /**
     * Mock Socialite User 생성
     */
    private function createMockSocialiteUser(array $data): SocialiteUser
    {
        $mock = Mockery::mock(SocialiteUser::class);
        $mock->shouldReceive('getId')->andReturn($data['id']);
        $mock->shouldReceive('getEmail')->andReturn($data['email'] ?? null);
        $mock->shouldReceive('getName')->andReturn($data['name'] ?? null);
        $mock->shouldReceive('getNickname')->andReturn($data['nickname'] ?? null);
        $mock->shouldReceive('getAvatar')->andReturn($data['avatar'] ?? null);

        $mock->token = $data['token'] ?? 'mock-access-token';
        $mock->refreshToken = $data['refreshToken'] ?? null;
        $mock->expiresIn = $data['expiresIn'] ?? null;

        return $mock;
    }
}
