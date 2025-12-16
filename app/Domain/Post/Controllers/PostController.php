<?php

declare(strict_types=1);

namespace App\Domain\Post\Controllers;

use App\Domain\Post\DTOs\CreatePostDTO;
use App\Domain\Post\DTOs\UpdatePostDTO;
use App\Domain\Post\Services\PostService;
use App\Http\Controllers\Controller;
use App\Shared\Http\Traits\ApiResponsable;
use App\Shared\Http\Traits\HasAuthenticatedUserId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    use ApiResponsable;
    use HasAuthenticatedUserId;

    public function __construct(
        private readonly PostService $postService,
    ) {}

    /**
     * 게시물 목록 조회
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'sometimes|integer',
            'status' => 'sometimes|in:draft,published,private',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'use_cursor' => 'sometimes|boolean',
        ]);

        $posts = $this->postService->getPosts(
            userId: $request->integer('user_id'),
            status: $request->string('status')->toString() ?: null,
            perPage: $request->integer('per_page', 15),
            useCursor: $request->boolean('use_cursor', false)
        );

        return $this->paginatedResponse($posts);
    }

    /**
     * 발행된 게시물 목록 조회 (공개용)
     */
    public function published(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $posts = $this->postService->getPublishedPosts(
            perPage: $request->integer('per_page', 15)
        );

        return $this->paginatedResponse($posts);
    }

    /**
     * 게시물 상세 조회 (ID)
     */
    public function show(int $id): JsonResponse
    {
        $post = $this->postService->getPostById($id);

        return $this->successResponse($post);
    }

    /**
     * 게시물 상세 조회 (slug)
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $post = $this->postService->getPostBySlug($slug);

        return $this->successResponse($post);
    }

    /**
     * 발행된 게시물 상세 조회 (slug, 공개용)
     */
    public function showPublishedBySlug(string $slug): JsonResponse
    {
        $post = $this->postService->getPublishedPostBySlug($slug);

        return $this->successResponse($post);
    }

    /**
     * 게시물 생성
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'slug' => 'nullable|string|max:255|unique:posts,slug',
            'status' => 'sometimes|in:draft,published,private',
        ]);

        $userId = $this->getAuthenticatedUserId();
        $dto = CreatePostDTO::fromRequest($request, $userId);
        $post = $this->postService->createPost($dto);

        return $this->createdResponse($post);
    }

    /**
     * 게시물 수정
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'excerpt' => 'nullable|string|max:500',
            'slug' => 'nullable|string|max:255|unique:posts,slug,'.$id,
            'status' => 'sometimes|in:draft,published,private',
        ]);

        $userId = $this->getAuthenticatedUserId();
        $dto = UpdatePostDTO::fromRequest($request);
        $post = $this->postService->updatePost($id, $userId, $dto);

        return $this->successResponse($post);
    }

    /**
     * 게시물 삭제
     */
    public function destroy(int $id): JsonResponse
    {
        $userId = $this->getAuthenticatedUserId();
        $this->postService->deletePost($id, $userId);

        return $this->deletedResponse();
    }

    /**
     * 게시물 발행
     */
    public function publish(int $id): JsonResponse
    {
        $userId = $this->getAuthenticatedUserId();
        $post = $this->postService->publishPost($id, $userId);

        return $this->successResponse($post);
    }

    /**
     * 게시물 발행 취소
     */
    public function unpublish(int $id): JsonResponse
    {
        $userId = $this->getAuthenticatedUserId();
        $post = $this->postService->unpublishPost($id, $userId);

        return $this->successResponse($post);
    }

    /**
     * 사용자 게시물 통계
     */
    public function stats(): JsonResponse
    {
        $userId = $this->getAuthenticatedUserId();
        $stats = $this->postService->getUserPostStats($userId);

        return $this->successResponse($stats);
    }
}
