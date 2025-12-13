<?php

declare(strict_types=1);

namespace App\Domain\Auth\Contracts;

use App\Domain\Auth\DTOs\AuthEventDTO;

/**
 * 인증 이벤트 Repository 인터페이스
 */
interface AuthEventRepositoryInterface
{
    /**
     * ID로 인증 이벤트 조회
     */
    public function find(string $id): ?AuthEventDTO;

    /**
     * 사용자 ID로 인증 이벤트 목록 조회
     *
     * @return array{data: AuthEventDTO[], total: int, page: int, per_page: int}
     */
    public function findByUserId(int $userId, int $page = 1, int $perPage = 15): array;

    /**
     * 필터 조건으로 인증 이벤트 목록 조회
     *
     * @param  array<string, mixed>  $filters  필터 조건
     * @return array{data: AuthEventDTO[], total: int, page: int, per_page: int}
     */
    public function findAll(array $filters = [], int $page = 1, int $perPage = 15): array;

    /**
     * 인증 이벤트 생성
     */
    public function create(AuthEventDTO $dto): AuthEventDTO;

    /**
     * 사용자별 액션 통계 조회
     *
     * @return array<string, int>
     */
    public function countByAction(int $userId): array;

    /**
     * 최근 로그인 이벤트 조회
     *
     * @return AuthEventDTO[]
     */
    public function findRecentLogins(int $userId, int $limit = 10): array;

    /**
     * 특정 기간 내 실패한 이벤트 수 조회
     */
    public function countFailedEvents(int $userId, int $minutes = 60): int;
}
