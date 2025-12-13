<?php

declare(strict_types=1);

namespace App\Domain\Auth\Services;

use App\Domain\Auth\Contracts\AuthEventRepositoryInterface;
use App\Domain\Auth\DTOs\AuthEventDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 인증 이벤트 로깅 서비스
 *
 * 인증 관련 이벤트를 MongoDB에 기록합니다.
 */
final class AuthEventService
{
    public function __construct(
        private readonly AuthEventRepositoryInterface $repository,
    ) {}

    /**
     * 로그인 이벤트 기록
     */
    public function logLogin(
        int $userId,
        string $provider,
        ?Request $request = null,
        ?array $metadata = null,
    ): AuthEventDTO {
        return $this->log(
            action: AuthEventDTO::ACTION_LOGIN,
            result: AuthEventDTO::RESULT_SUCCESS,
            userId: $userId,
            provider: $provider,
            request: $request,
            metadata: $metadata,
        );
    }

    /**
     * 로그아웃 이벤트 기록
     */
    public function logLogout(
        int $userId,
        ?Request $request = null,
        ?array $metadata = null,
    ): AuthEventDTO {
        return $this->log(
            action: AuthEventDTO::ACTION_LOGOUT,
            result: AuthEventDTO::RESULT_SUCCESS,
            userId: $userId,
            request: $request,
            metadata: $metadata,
        );
    }

    /**
     * 전체 로그아웃 이벤트 기록
     */
    public function logLogoutAll(
        int $userId,
        ?Request $request = null,
        ?array $metadata = null,
    ): AuthEventDTO {
        return $this->log(
            action: AuthEventDTO::ACTION_LOGOUT_ALL,
            result: AuthEventDTO::RESULT_SUCCESS,
            userId: $userId,
            request: $request,
            metadata: $metadata,
        );
    }

    /**
     * 토큰 갱신 이벤트 기록
     */
    public function logTokenRefresh(
        int $userId,
        ?Request $request = null,
        ?array $metadata = null,
    ): AuthEventDTO {
        return $this->log(
            action: AuthEventDTO::ACTION_TOKEN_REFRESH,
            result: AuthEventDTO::RESULT_SUCCESS,
            userId: $userId,
            request: $request,
            metadata: $metadata,
        );
    }

    /**
     * 토큰 갱신 실패 이벤트 기록
     */
    public function logTokenRefreshFailed(
        ?int $userId,
        string $errorCode,
        string $errorMessage,
        ?Request $request = null,
        ?array $metadata = null,
    ): AuthEventDTO {
        return $this->log(
            action: AuthEventDTO::ACTION_TOKEN_REFRESH_FAILED,
            result: AuthEventDTO::RESULT_FAILURE,
            userId: $userId,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            request: $request,
            metadata: $metadata,
        );
    }

    /**
     * 토큰 재사용 감지 이벤트 기록
     */
    public function logTokenReuseDetected(
        int $userId,
        string $familyId,
        ?Request $request = null,
        ?array $metadata = null,
    ): AuthEventDTO {
        $eventMetadata = array_merge($metadata ?? [], [
            'family_id' => $familyId,
            'security_event' => true,
        ]);

        // 보안 이벤트이므로 경고 로그도 남김
        Log::warning('Token reuse detected', [
            'user_id' => $userId,
            'family_id' => $familyId,
            'ip_address' => $request?->ip(),
        ]);

        return $this->log(
            action: AuthEventDTO::ACTION_TOKEN_REUSE_DETECTED,
            result: AuthEventDTO::RESULT_FAILURE,
            userId: $userId,
            errorCode: 'TOKEN_REUSE_DETECTED',
            errorMessage: '토큰 재사용이 감지되었습니다.',
            request: $request,
            metadata: $eventMetadata,
        );
    }

    /**
     * 소셜 계정 연동 이벤트 기록
     */
    public function logSocialLink(
        int $userId,
        string $provider,
        ?Request $request = null,
        ?array $metadata = null,
    ): AuthEventDTO {
        return $this->log(
            action: AuthEventDTO::ACTION_SOCIAL_LINK,
            result: AuthEventDTO::RESULT_SUCCESS,
            userId: $userId,
            provider: $provider,
            request: $request,
            metadata: $metadata,
        );
    }

    /**
     * 소셜 계정 연동 해제 이벤트 기록
     */
    public function logSocialUnlink(
        int $userId,
        string $provider,
        ?Request $request = null,
        ?array $metadata = null,
    ): AuthEventDTO {
        return $this->log(
            action: AuthEventDTO::ACTION_SOCIAL_UNLINK,
            result: AuthEventDTO::RESULT_SUCCESS,
            userId: $userId,
            provider: $provider,
            request: $request,
            metadata: $metadata,
        );
    }

    /**
     * 사용자의 인증 이벤트 목록 조회
     *
     * @return array{data: AuthEventDTO[], total: int, page: int, per_page: int}
     */
    public function getEventsByUserId(int $userId, int $page = 1, int $perPage = 15): array
    {
        return $this->repository->findByUserId($userId, $page, $perPage);
    }

    /**
     * 인증 이벤트 목록 조회 (필터)
     *
     * @param  array<string, mixed>  $filters
     * @return array{data: AuthEventDTO[], total: int, page: int, per_page: int}
     */
    public function getEvents(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        return $this->repository->findAll($filters, $page, $perPage);
    }

    /**
     * 최근 로그인 이벤트 조회
     *
     * @return AuthEventDTO[]
     */
    public function getRecentLogins(int $userId, int $limit = 10): array
    {
        return $this->repository->findRecentLogins($userId, $limit);
    }

    /**
     * 공통 로깅 메서드
     */
    private function log(
        string $action,
        string $result,
        ?int $userId = null,
        ?string $provider = null,
        ?string $errorCode = null,
        ?string $errorMessage = null,
        ?Request $request = null,
        ?array $metadata = null,
    ): AuthEventDTO {
        $dto = new AuthEventDTO(
            id: null,
            userId: $userId,
            action: $action,
            result: $result,
            provider: $provider,
            errorCode: $errorCode,
            errorMessage: $errorMessage,
            metadata: $metadata,
            ipAddress: $request?->ip(),
            userAgent: $request?->userAgent(),
        );

        return $this->repository->create($dto);
    }
}
