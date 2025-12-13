<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTOs;

use Laravel\Socialite\Contracts\User as SocialiteUser;

final readonly class SocialUserDTO
{
    public function __construct(
        public string $provider,
        public string $providerUserId,
        public ?string $email,
        public ?string $name,
        public ?string $avatar,
        public ?string $token,
        public ?string $refreshToken,
        public ?int $expiresIn,
    ) {}

    /**
     * Socialite User 객체로부터 DTO 생성
     */
    public static function fromSocialiteUser(string $provider, SocialiteUser $user): self
    {
        return new self(
            provider: $provider,
            providerUserId: (string) $user->getId(),
            email: $user->getEmail(),
            name: $user->getName() ?? $user->getNickname(),
            avatar: $user->getAvatar(),
            token: $user->token,
            refreshToken: $user->refreshToken ?? null,
            expiresIn: $user->expiresIn ?? null,
        );
    }
}
