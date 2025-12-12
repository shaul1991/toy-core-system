<?php

declare(strict_types=1);

namespace App\Domain\UserActivity\Services;

use App\Domain\UserActivity\Contracts\UserActivityRepositoryInterface;
use App\Domain\UserActivity\DTOs\UserActivityDTO;
use App\Shared\Exceptions\NotFoundException;

final class UserActivityService
{
    public function __construct(
        private readonly UserActivityRepositoryInterface $repository,
    ) {}

    /**
     * Get a single activity by ID
     *
     * @throws NotFoundException
     */
    public function getActivity(string $id): UserActivityDTO
    {
        $activity = $this->repository->find($id);

        if (! $activity) {
            throw NotFoundException::forResource('UserActivity', $id);
        }

        return $activity;
    }

    /**
     * Get activities for a specific user
     *
     * @return array{data: array<UserActivityDTO>, total: int, page: int, per_page: int}
     */
    public function getActivitiesByUser(int $userId, int $page = 1, int $perPage = 15): array
    {
        return $this->repository->findByUserId($userId, $page, $perPage);
    }

    /**
     * Get all activities with filters
     *
     * @param  array<string, mixed>  $filters
     * @return array{data: array<UserActivityDTO>, total: int, page: int, per_page: int}
     */
    public function getActivities(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        return $this->repository->findAll($filters, $page, $perPage);
    }

    /**
     * Create a new activity
     *
     * @param  array<string, mixed>  $data
     */
    public function createActivity(array $data): UserActivityDTO
    {
        $dto = UserActivityDTO::fromRequest($data);

        return $this->repository->create($dto);
    }

    /**
     * Update an existing activity
     *
     * @param  array<string, mixed>  $data
     *
     * @throws NotFoundException
     */
    public function updateActivity(string $id, array $data): UserActivityDTO
    {
        $existing = $this->repository->find($id);

        if (! $existing) {
            throw NotFoundException::forResource('UserActivity', $id);
        }

        $dto = new UserActivityDTO(
            id: $id,
            userId: $data['user_id'] ?? $existing->userId,
            action: $data['action'] ?? $existing->action,
            targetType: $data['target_type'] ?? $existing->targetType,
            targetId: $data['target_id'] ?? $existing->targetId,
            metadata: $data['metadata'] ?? $existing->metadata,
            ipAddress: $data['ip_address'] ?? $existing->ipAddress,
            userAgent: $data['user_agent'] ?? $existing->userAgent,
            createdAt: $existing->createdAt,
        );

        $updated = $this->repository->update($id, $dto);

        if (! $updated) {
            throw NotFoundException::forResource('UserActivity', $id);
        }

        return $updated;
    }

    /**
     * Delete an activity
     *
     * @throws NotFoundException
     */
    public function deleteActivity(string $id): void
    {
        $deleted = $this->repository->delete($id);

        if (! $deleted) {
            throw NotFoundException::forResource('UserActivity', $id);
        }
    }

    /**
     * Delete all activities for a user
     */
    public function deleteUserActivities(int $userId): int
    {
        return $this->repository->deleteByUserId($userId);
    }

    /**
     * Get activity statistics for a user
     *
     * @return array<string, int>
     */
    public function getActivityStats(int $userId): array
    {
        return $this->repository->countByAction($userId);
    }

    /**
     * Log a user activity (convenience method)
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public function log(
        int $userId,
        string $action,
        ?string $targetType = null,
        ?string $targetId = null,
        ?array $metadata = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): UserActivityDTO {
        return $this->createActivity([
            'user_id' => $userId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'metadata' => $metadata,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }
}
