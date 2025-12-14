<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\DTOs\SocialUserDTO;
use App\Domain\Auth\DTOs\TokenDTO;
use App\Domain\Auth\Exceptions\SocialAuthException;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;

final class SocialAuthService
{
    private const SUPPORTED_PROVIDERS = ['github', 'naver', 'kakao'];

    public function __construct(
        private readonly JwtService $jwtService,
    ) {}

    /**
     * 지원하는 소셜 제공자인지 확인
     */
    public function isProviderSupported(string $provider): bool
    {
        return in_array($provider, self::SUPPORTED_PROVIDERS, true);
    }

    /**
     * 소셜 로그인 리다이렉트 URL 생성
     */
    public function getRedirectUrl(string $provider): string
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->stateless()->redirect()->getTargetUrl();
    }

    /**
     * 소셜 계정 연동용 리다이렉트 URL 생성 (state 파라미터 포함)
     */
    public function getRedirectUrlWithState(string $provider, string $state): string
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)
            ->stateless()
            ->with(['state' => $state])
            ->redirect()
            ->getTargetUrl();
    }

    /**
     * 소셜 로그인 콜백 처리 (비로그인 상태)
     */
    public function handleCallback(string $provider): TokenDTO
    {
        $this->validateProvider($provider);

        $socialUser = $this->getSocialUser($provider);
        $socialUserDTO = SocialUserDTO::fromSocialiteUser($provider, $socialUser);

        return DB::transaction(function () use ($socialUserDTO) {
            // 1. 이미 연동된 소셜 계정이 있는지 확인
            $existingSocialAccount = SocialAccount::where('provider', $socialUserDTO->provider)
                ->where('provider_user_id', $socialUserDTO->providerUserId)
                ->first();

            if ($existingSocialAccount) {
                // 기존 소셜 계정이 있으면 해당 사용자로 로그인
                $user = $existingSocialAccount->user;
                $this->updateSocialAccountTokens($existingSocialAccount, $socialUserDTO);

                return $this->jwtService->createTokenPair($user);
            }

            // 2. 이메일로 기존 사용자 조회
            $user = null;
            if ($socialUserDTO->email) {
                $user = User::where('email', $socialUserDTO->email)->first();
            }

            // 3. 사용자가 없으면 새로 생성
            if (! $user) {
                $user = $this->createUserFromSocial($socialUserDTO);
            }

            // 4. 소셜 계정 연동
            $this->createSocialAccount($user, $socialUserDTO);

            return $this->jwtService->createTokenPair($user);
        });
    }

    /**
     * 소셜 계정 연동 (로그인 상태)
     */
    public function linkAccount(User $user, string $provider): SocialAccount
    {
        $this->validateProvider($provider);

        $socialUser = $this->getSocialUser($provider);
        $socialUserDTO = SocialUserDTO::fromSocialiteUser($provider, $socialUser);

        return DB::transaction(function () use ($user, $socialUserDTO) {
            // 1. 다른 계정에 이미 연동되어 있는지 확인
            $existingSocialAccount = SocialAccount::where('provider', $socialUserDTO->provider)
                ->where('provider_user_id', $socialUserDTO->providerUserId)
                ->first();

            if ($existingSocialAccount && $existingSocialAccount->user_id !== $user->id) {
                throw SocialAuthException::accountAlreadyLinked($socialUserDTO->provider);
            }

            // 2. 현재 계정에 같은 제공자가 이미 연동되어 있으면 업데이트
            $currentAccount = $user->socialAccounts()
                ->where('provider', $socialUserDTO->provider)
                ->first();

            if ($currentAccount) {
                $this->updateSocialAccountTokens($currentAccount, $socialUserDTO);
                $currentAccount->update([
                    'provider_user_id' => $socialUserDTO->providerUserId,
                    'provider_email' => $socialUserDTO->email,
                ]);

                return $currentAccount;
            }

            // 3. 새 소셜 계정 연동
            return $this->createSocialAccount($user, $socialUserDTO);
        });
    }

    /**
     * 소셜 계정 연동 해제
     */
    public function unlinkAccount(User $user, string $provider): void
    {
        $this->validateProvider($provider);

        // 마지막 로그인 수단인지 확인
        $socialAccountCount = $user->socialAccounts()->count();
        $hasPassword = ! empty($user->password);

        if ($socialAccountCount <= 1 && ! $hasPassword) {
            throw SocialAuthException::cannotUnlinkLastProvider();
        }

        $socialAccount = $user->socialAccounts()
            ->where('provider', $provider)
            ->first();

        if (! $socialAccount) {
            throw SocialAuthException::accountNotLinked($provider);
        }

        $socialAccount->delete();
    }

    /**
     * 연동된 소셜 계정 목록 조회
     *
     * @return array<array{provider: string, provider_email: ?string, linked_at: string}>
     */
    public function getLinkedAccounts(User $user): array
    {
        return $user->socialAccounts->map(fn (SocialAccount $account) => [
            'provider' => $account->provider,
            'provider_email' => $account->provider_email,
            'linked_at' => $account->created_at->toIso8601String(),
        ])->all();
    }

    /**
     * 제공자 유효성 검증
     */
    private function validateProvider(string $provider): void
    {
        if (! $this->isProviderSupported($provider)) {
            throw SocialAuthException::unsupportedProvider($provider);
        }
    }

    /**
     * Socialite를 통해 소셜 사용자 정보 조회
     */
    private function getSocialUser(string $provider): \Laravel\Socialite\Contracts\User
    {
        try {
            return Socialite::driver($provider)->stateless()->user();
        } catch (\Exception $e) {
            throw SocialAuthException::authenticationFailed($provider);
        }
    }

    /**
     * 소셜 정보로 새 사용자 생성
     *
     * 일부 소셜 제공자(예: Kakao)는 이메일을 필수로 제공하지 않을 수 있습니다.
     * 이 경우 임시 이메일({provider}_{providerUserId}@noemail.local)을 생성합니다.
     *
     * token_version은 User 모델의 boot() 메서드에서 자동으로 1로 설정됩니다.
     */
    private function createUserFromSocial(SocialUserDTO $dto): User
    {
        $email = $dto->email ?? "{$dto->provider}_{$dto->providerUserId}@noemail.local";

        return User::create([
            'name' => $dto->name ?? 'User',
            'email' => $email,
            'avatar' => $dto->avatar,
            'password' => null, // 소셜 전용 계정
        ]);
    }

    /**
     * 소셜 계정 생성
     */
    private function createSocialAccount(User $user, SocialUserDTO $dto): SocialAccount
    {
        return $user->socialAccounts()->create([
            'provider' => $dto->provider,
            'provider_user_id' => $dto->providerUserId,
            'provider_email' => $dto->email,
            'provider_token' => $dto->token,
            'provider_refresh_token' => $dto->refreshToken,
            'token_expires_at' => $dto->expiresIn
                ? now()->addSeconds($dto->expiresIn)
                : null,
        ]);
    }

    /**
     * 소셜 계정 토큰 업데이트
     */
    private function updateSocialAccountTokens(SocialAccount $account, SocialUserDTO $dto): void
    {
        $account->update([
            'provider_token' => $dto->token,
            'provider_refresh_token' => $dto->refreshToken,
            'token_expires_at' => $dto->expiresIn
                ? now()->addSeconds($dto->expiresIn)
                : null,
        ]);
    }
}
