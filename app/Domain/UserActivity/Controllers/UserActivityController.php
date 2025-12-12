<?php

declare(strict_types=1);

namespace App\Domain\UserActivity\Controllers;

use App\Domain\UserActivity\Services\UserActivityService;
use App\Http\Controllers\Controller;
use App\Shared\Http\Traits\ApiResponsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UserActivityController extends Controller
{
    use ApiResponsable;

    public function __construct(
        private readonly UserActivityService $service,
    ) {}

    /**
     * Get all activities with filters and pagination
     *
     * GET /api/user-activity
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'user_id',
            'action',
            'target_type',
            'target_id',
            'action_like',
            'from_date',
            'to_date',
        ]);

        $page = (int) $request->query('page', 1);
        $perPage = min((int) $request->query('per_page', 15), 100);

        $result = $this->service->getActivities($filters, $page, $perPage);

        return $this->paginatedArrayResponse(
            data: array_map(fn ($dto) => $dto->toArray(), $result['data']),
            total: $result['total'],
            page: $result['page'],
            perPage: $result['per_page'],
        );
    }

    /**
     * Create a new activity
     *
     * POST /api/user-activity
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'action' => 'required|string|max:255',
            'target_type' => 'nullable|string|max:255',
            'target_id' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
            'ip_address' => 'nullable|ip',
            'user_agent' => 'nullable|string|max:1000',
        ]);

        // Auto-fill IP and User-Agent if not provided
        $validated['ip_address'] ??= $request->ip();
        $validated['user_agent'] ??= $request->userAgent();

        $activity = $this->service->createActivity($validated);

        return $this->createdResponse($activity->toArray());
    }

    /**
     * Get a single activity
     *
     * GET /api/user-activity/{id}
     */
    public function show(string $id): JsonResponse
    {
        $activity = $this->service->getActivity($id);

        return $this->successResponse($activity->toArray());
    }

    /**
     * Update an activity
     *
     * PUT /api/user-activity/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'sometimes|integer',
            'action' => 'sometimes|string|max:255',
            'target_type' => 'nullable|string|max:255',
            'target_id' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
            'ip_address' => 'nullable|ip',
            'user_agent' => 'nullable|string|max:1000',
        ]);

        $activity = $this->service->updateActivity($id, $validated);

        return $this->successResponse($activity->toArray());
    }

    /**
     * Delete an activity
     *
     * DELETE /api/user-activity/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $this->service->deleteActivity($id);

        return $this->deletedResponse();
    }

    /**
     * Get activities for a specific user
     *
     * GET /api/user-activity/user/{userId}
     */
    public function byUser(Request $request, int $userId): JsonResponse
    {
        $page = (int) $request->query('page', 1);
        $perPage = min((int) $request->query('per_page', 15), 100);

        $result = $this->service->getActivitiesByUser($userId, $page, $perPage);

        return $this->paginatedArrayResponse(
            data: array_map(fn ($dto) => $dto->toArray(), $result['data']),
            total: $result['total'],
            page: $result['page'],
            perPage: $result['per_page'],
        );
    }

    /**
     * Get activity statistics for a user
     *
     * GET /api/user-activity/user/{userId}/stats
     */
    public function stats(int $userId): JsonResponse
    {
        $stats = $this->service->getActivityStats($userId);

        return $this->successResponse([
            'user_id' => $userId,
            'activity_counts' => $stats,
            'total' => array_sum($stats),
        ]);
    }

    /**
     * Delete all activities for a user
     *
     * DELETE /api/user-activity/user/{userId}
     */
    public function destroyByUser(int $userId): JsonResponse
    {
        $count = $this->service->deleteUserActivities($userId);

        return $this->successResponse([
            'deleted_count' => $count,
            'message' => "Deleted {$count} activities for user {$userId}",
        ]);
    }
}
