<?php

declare(strict_types=1);

namespace App\Domain\UserActivity\Contracts;

use App\Domain\UserActivity\DTOs\UserActivityDTO;

interface UserActivityRepositoryInterface
{
    /**
     * Find a user activity by ID
     */
    public function find(string $id): ?UserActivityDTO;

    /**
     * Get all activities for a user
     *
     * @return array{data: array<UserActivityDTO>, total: int, page: int, per_page: int}
     */
    public function findByUserId(int $userId, int $page = 1, int $perPage = 15): array;

    /**
     * Get all activities with pagination
     *
     * @param  array<string, mixed>  $filters
     * @return array{data: array<UserActivityDTO>, total: int, page: int, per_page: int}
     */
    public function findAll(array $filters = [], int $page = 1, int $perPage = 15): array;

    /**
     * Create a new user activity
     */
    public function create(UserActivityDTO $dto): UserActivityDTO;

    /**
     * Update an existing user activity
     */
    public function update(string $id, UserActivityDTO $dto): ?UserActivityDTO;

    /**
     * Delete a user activity
     */
    public function delete(string $id): bool;

    /**
     * Delete all activities for a user
     */
    public function deleteByUserId(int $userId): int;

    /**
     * Count activities by action type
     *
     * @return array<string, int>
     */
    public function countByAction(int $userId): array;
}
