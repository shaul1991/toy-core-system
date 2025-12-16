<?php

declare(strict_types=1);

namespace App\Bff\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * BFF용 Core Post Service 클라이언트
 *
 * Core Service의 Post 도메인 Internal API를 호출합니다.
 * 실제 마이크로서비스 환경에서는 별도 서버의 Internal API를 호출합니다.
 */
final class CorePostService
{
    private string $baseUrl;

    public function __construct()
    {
        // Internal API Base URL
        // - 로컬: http://localhost:8000/internal
        // - 프로덕션: http://core-service.internal:8080/internal
        $this->baseUrl = config('app.internal_api_url', url('/internal'));
    }

    /**
     * 게시물 목록 조회
     *
     * @param  int  $userId  사용자 ID (JWT에서 추출)
     * @param  array{user_id?: int, status?: string, per_page?: int}  $params  쿼리 파라미터
     */
    public function getPosts(int $userId, array $params = []): Response
    {
        return Http::withHeaders([
            'X-User-Id' => $userId,
        ])->get("{$this->baseUrl}/posts", $params);
    }

    /**
     * 발행된 게시물 목록 조회 (공개용)
     *
     * @param  array{per_page?: int}  $params  쿼리 파라미터
     */
    public function getPublishedPosts(array $params = []): Response
    {
        return Http::get("{$this->baseUrl}/posts/published", $params);
    }

    /**
     * 게시물 상세 조회
     */
    public function getPost(int $userId, int $postId): Response
    {
        return Http::withHeaders([
            'X-User-Id' => $userId,
        ])->get("{$this->baseUrl}/posts/{$postId}");
    }

    /**
     * 게시물 상세 조회 (slug)
     */
    public function getPostBySlug(int $userId, string $slug): Response
    {
        return Http::withHeaders([
            'X-User-Id' => $userId,
        ])->get("{$this->baseUrl}/posts/slug/{$slug}");
    }

    /**
     * 발행된 게시물 상세 조회 (slug, 공개용)
     */
    public function getPublishedPostBySlug(string $slug): Response
    {
        return Http::get("{$this->baseUrl}/posts/slug/{$slug}/published");
    }

    /**
     * 게시물 생성
     *
     * @param  int  $userId  사용자 ID (JWT에서 추출)
     * @param  array{title: string, content: string, excerpt?: string, slug?: string, status?: string}  $data  게시물 데이터
     */
    public function createPost(int $userId, array $data): Response
    {
        return Http::withHeaders([
            'X-User-Id' => $userId,
        ])->post("{$this->baseUrl}/posts", $data);
    }

    /**
     * 게시물 수정
     *
     * @param  int  $userId  사용자 ID (JWT에서 추출)
     * @param  int  $postId  게시물 ID
     * @param  array{title?: string, content?: string, excerpt?: string, slug?: string, status?: string}  $data  수정할 데이터
     */
    public function updatePost(int $userId, int $postId, array $data): Response
    {
        return Http::withHeaders([
            'X-User-Id' => $userId,
        ])->put("{$this->baseUrl}/posts/{$postId}", $data);
    }

    /**
     * 게시물 삭제
     */
    public function deletePost(int $userId, int $postId): Response
    {
        return Http::withHeaders([
            'X-User-Id' => $userId,
        ])->delete("{$this->baseUrl}/posts/{$postId}");
    }

    /**
     * 게시물 발행
     */
    public function publishPost(int $userId, int $postId): Response
    {
        return Http::withHeaders([
            'X-User-Id' => $userId,
        ])->post("{$this->baseUrl}/posts/{$postId}/publish");
    }

    /**
     * 게시물 발행 취소
     */
    public function unpublishPost(int $userId, int $postId): Response
    {
        return Http::withHeaders([
            'X-User-Id' => $userId,
        ])->post("{$this->baseUrl}/posts/{$postId}/unpublish");
    }

    /**
     * 사용자 게시물 통계
     */
    public function getPostStats(int $userId): Response
    {
        return Http::withHeaders([
            'X-User-Id' => $userId,
        ])->get("{$this->baseUrl}/posts/stats");
    }
}
