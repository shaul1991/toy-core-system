<?php

declare(strict_types=1);

namespace App\Bff\Controllers;

use App\Bff\Services\CorePostService;
use App\Http\Controllers\Controller;
use App\Shared\Http\Traits\ApiResponsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * BFF Post 컨트롤러
 *
 * 프론트엔드와 Core Service 사이의 Post 도메인 API를 처리합니다.
 * - JWT 인증 후 Core Service Internal API 호출
 * - 응답 변환 및 에러 핸들링
 * - Rate Limiting 및 권한 확인
 */
final class PostController extends Controller
{
    use ApiResponsable;

    public function __construct(
        private readonly CorePostService $corePostService,
    ) {}

    /**
     * 게시물 목록 조회
     *
     * @OA\Get(
     *     path="/api/posts",
     *     tags={"Posts"},
     *     summary="게시물 목록 조회",
     *     security={{"BearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="게시물 상태 (draft, published, private)",
     *         required=false,
     *
     *         @OA\Schema(type="string", enum={"draft", "published", "private"})
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="페이지 크기",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=15, minimum=1, maximum=100)
     *     ),
     *
     *     @OA\Response(response=200, description="게시물 목록"),
     *     @OA\Response(response=401, description="인증 필요")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        // JWT 미들웨어에서 이미 검증됨
        $userId = auth()->id();

        $response = $this->corePostService->getPosts($userId, [
            'status' => $request->query('status'),
            'per_page' => $request->query('per_page', 15),
        ]);

        return response()->json($response->json(), $response->status());
    }

    /**
     * 발행된 게시물 목록 조회 (공개용)
     *
     * 인증 없이 접근 가능한 공개 API입니다.
     *
     * @OA\Get(
     *     path="/api/posts/published",
     *     tags={"Posts"},
     *     summary="발행된 게시물 목록 조회 (공개)",
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="페이지 크기",
     *         required=false,
     *
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *
     *     @OA\Response(response=200, description="발행된 게시물 목록")
     * )
     */
    public function published(Request $request): JsonResponse
    {
        $response = $this->corePostService->getPublishedPosts([
            'per_page' => $request->query('per_page', 15),
        ]);

        return response()->json($response->json(), $response->status());
    }

    /**
     * 게시물 상세 조회
     *
     * @OA\Get(
     *     path="/api/posts/{id}",
     *     tags={"Posts"},
     *     summary="게시물 상세 조회",
     *     security={{"BearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="게시물 ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(response=200, description="게시물 상세"),
     *     @OA\Response(response=404, description="게시물 없음")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $userId = auth()->id();

        $response = $this->corePostService->getPost($userId, $id);

        return response()->json($response->json(), $response->status());
    }

    /**
     * 게시물 상세 조회 (slug)
     *
     * @OA\Get(
     *     path="/api/posts/slug/{slug}",
     *     tags={"Posts"},
     *     summary="게시물 상세 조회 (slug)",
     *     security={{"BearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         description="게시물 슬러그",
     *         required=true,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(response=200, description="게시물 상세"),
     *     @OA\Response(response=404, description="게시물 없음")
     * )
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $userId = auth()->id();

        $response = $this->corePostService->getPostBySlug($userId, $slug);

        return response()->json($response->json(), $response->status());
    }

    /**
     * 발행된 게시물 상세 조회 (slug, 공개용)
     *
     * @OA\Get(
     *     path="/api/posts/slug/{slug}/published",
     *     tags={"Posts"},
     *     summary="발행된 게시물 상세 조회 (공개)",
     *
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         description="게시물 슬러그",
     *         required=true,
     *
     *         @OA\Schema(type="string")
     *     ),
     *
     *     @OA\Response(response=200, description="게시물 상세"),
     *     @OA\Response(response=404, description="게시물 없음")
     * )
     */
    public function showPublishedBySlug(string $slug): JsonResponse
    {
        $response = $this->corePostService->getPublishedPostBySlug($slug);

        return response()->json($response->json(), $response->status());
    }

    /**
     * 게시물 생성
     *
     * @OA\Post(
     *     path="/api/posts",
     *     tags={"Posts"},
     *     summary="게시물 생성",
     *     security={{"BearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *             required={"title", "content"},
     *
     *             @OA\Property(property="title", type="string", description="제목", maxLength=255),
     *             @OA\Property(property="content", type="string", description="Markdown 내용"),
     *             @OA\Property(property="excerpt", type="string", description="요약 (선택)", maxLength=500),
     *             @OA\Property(property="slug", type="string", description="슬러그 (선택, 자동 생성)"),
     *             @OA\Property(property="status", type="string", enum={"draft", "published", "private"}, default="draft")
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="게시물 생성 성공"),
     *     @OA\Response(response=400, description="입력 검증 실패")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $userId = auth()->id();

        // BFF 레벨 검증
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string|max:500',
            'slug' => 'nullable|string|max:255',
            'status' => 'sometimes|in:draft,published,private',
        ]);

        $response = $this->corePostService->createPost($userId, $validated);

        return response()->json($response->json(), $response->status());
    }

    /**
     * 게시물 수정
     *
     * @OA\Put(
     *     path="/api/posts/{id}",
     *     tags={"Posts"},
     *     summary="게시물 수정",
     *     security={{"BearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="게시물 ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="title", type="string", description="제목", maxLength=255),
     *             @OA\Property(property="content", type="string", description="Markdown 내용"),
     *             @OA\Property(property="excerpt", type="string", description="요약", maxLength=500),
     *             @OA\Property(property="slug", type="string", description="슬러그"),
     *             @OA\Property(property="status", type="string", enum={"draft", "published", "private"})
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="게시물 수정 성공"),
     *     @OA\Response(response=403, description="권한 없음"),
     *     @OA\Response(response=404, description="게시물 없음")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $userId = auth()->id();

        // BFF 레벨 검증
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'excerpt' => 'nullable|string|max:500',
            'slug' => 'nullable|string|max:255',
            'status' => 'sometimes|in:draft,published,private',
        ]);

        $response = $this->corePostService->updatePost($userId, $id, $validated);

        return response()->json($response->json(), $response->status());
    }

    /**
     * 게시물 삭제
     *
     * @OA\Delete(
     *     path="/api/posts/{id}",
     *     tags={"Posts"},
     *     summary="게시물 삭제",
     *     security={{"BearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="게시물 ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(response=200, description="게시물 삭제 성공"),
     *     @OA\Response(response=403, description="권한 없음"),
     *     @OA\Response(response=404, description="게시물 없음")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $userId = auth()->id();

        $response = $this->corePostService->deletePost($userId, $id);

        return response()->json($response->json(), $response->status());
    }

    /**
     * 게시물 발행
     *
     * @OA\Post(
     *     path="/api/posts/{id}/publish",
     *     tags={"Posts"},
     *     summary="게시물 발행",
     *     security={{"BearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="게시물 ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(response=200, description="발행 성공"),
     *     @OA\Response(response=403, description="권한 없음")
     * )
     */
    public function publish(int $id): JsonResponse
    {
        $userId = auth()->id();

        $response = $this->corePostService->publishPost($userId, $id);

        return response()->json($response->json(), $response->status());
    }

    /**
     * 게시물 발행 취소
     *
     * @OA\Post(
     *     path="/api/posts/{id}/unpublish",
     *     tags={"Posts"},
     *     summary="게시물 발행 취소",
     *     security={{"BearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="게시물 ID",
     *         required=true,
     *
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(response=200, description="발행 취소 성공"),
     *     @OA\Response(response=403, description="권한 없음")
     * )
     */
    public function unpublish(int $id): JsonResponse
    {
        $userId = auth()->id();

        $response = $this->corePostService->unpublishPost($userId, $id);

        return response()->json($response->json(), $response->status());
    }

    /**
     * 사용자 게시물 통계
     *
     * @OA\Get(
     *     path="/api/posts/stats",
     *     tags={"Posts"},
     *     summary="사용자 게시물 통계",
     *     security={{"BearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="통계 조회 성공",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total", type="integer", example=10),
     *                 @OA\Property(property="published", type="integer", example=3),
     *                 @OA\Property(property="draft", type="integer", example=5),
     *                 @OA\Property(property="private", type="integer", example=2)
     *             )
     *         )
     *     )
     * )
     */
    public function stats(): JsonResponse
    {
        $userId = auth()->id();

        $response = $this->corePostService->getPostStats($userId);

        return response()->json($response->json(), $response->status());
    }
}
