<?php

declare(strict_types=1);

namespace App\Domain\Post\Services;

use App\Domain\Post\DTOs\CreatePostDTO;
use App\Domain\Post\DTOs\UpdatePostDTO;
use App\Domain\Post\Events\PostCreated;
use App\Domain\Post\Events\PostPublished;
use App\Domain\Post\Events\PostUnpublished;
use App\Models\Post;
use App\Shared\Exceptions\ConflictException;
use App\Shared\Exceptions\ForbiddenException;
use App\Shared\Exceptions\NotFoundException;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

final class PostService
{
    /**
     * 게시물 목록 조회 (페이지네이션)
     */
    public function getPosts(
        ?int $userId = null,
        ?string $status = null,
        int $perPage = 15,
        bool $useCursor = false
    ): LengthAwarePaginator|CursorPaginator {
        $query = Post::query()
            ->with('user:id,name,email,avatar')
            ->orderBy('created_at', 'desc');

        if ($userId) {
            $query->byUser($userId);
        }

        if ($status === 'published') {
            $query->published();
        } elseif ($status === 'draft') {
            $query->draft();
        } elseif ($status) {
            $query->where('status', $status);
        }

        return $useCursor
            ? $query->cursorPaginate($perPage)
            : $query->paginate($perPage);
    }

    /**
     * 발행된 게시물 목록 조회 (공개용)
     */
    public function getPublishedPosts(int $perPage = 15): LengthAwarePaginator
    {
        return Post::query()
            ->published()
            ->with('user:id,name,email,avatar')
            ->orderBy('published_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * 게시물 상세 조회 (ID)
     */
    public function getPostById(int $id): Post
    {
        $post = Post::with('user:id,name,email,avatar')->find($id);

        if (! $post) {
            throw NotFoundException::forResource('Post', $id);
        }

        return $post;
    }

    /**
     * 게시물 상세 조회 (slug)
     */
    public function getPostBySlug(string $slug): Post
    {
        $post = Post::with('user:id,name,email,avatar')
            ->where('slug', $slug)
            ->first();

        if (! $post) {
            throw NotFoundException::forCriteria('Post', ['slug' => $slug]);
        }

        return $post;
    }

    /**
     * 발행된 게시물 상세 조회 (slug, 공개용)
     */
    public function getPublishedPostBySlug(string $slug): Post
    {
        $post = Post::query()
            ->published()
            ->with('user:id,name,email,avatar')
            ->where('slug', $slug)
            ->first();

        if (! $post) {
            throw NotFoundException::forCriteria('Post', ['slug' => $slug]);
        }

        return $post;
    }

    /**
     * 게시물 생성
     *
     * UNIQUE 제약 위반 시 재시도 (slug 중복 방지)
     */
    public function createPost(CreatePostDTO $dto): Post
    {
        $maxRetries = 3;
        $attempt = 0;

        while ($attempt < $maxRetries) {
            try {
                return DB::transaction(function () use ($dto) {
                    $post = Post::create($dto->toArray());

                    Event::dispatch(new PostCreated($post));

                    return $post;
                });
            } catch (QueryException $e) {
                // PostgreSQL UNIQUE 제약 위반 (23505)
                // MySQL UNIQUE 제약 위반 (23000, 1062)
                if (in_array($e->getCode(), ['23505', '23000']) || $e->errorInfo[1] === 1062) {
                    $attempt++;

                    if ($attempt >= $maxRetries) {
                        throw ConflictException::duplicateField('slug', $dto->slug ?? Str::slug($dto->title));
                    }

                    // 재시도 시 slug에 랜덤 접미사 추가
                    if (empty($dto->slug)) {
                        $dto = new CreatePostDTO(
                            userId: $dto->userId,
                            title: $dto->title,
                            content: $dto->content,
                            excerpt: $dto->excerpt,
                            slug: Str::slug($dto->title).'-'.Str::random(6),
                            status: $dto->status,
                        );
                    }

                    // 잠시 대기 후 재시도 (지수 백오프)
                    usleep(100000 * $attempt); // 100ms, 200ms, 300ms

                    continue;
                }

                // 다른 예외는 그대로 throw
                throw $e;
            }
        }

        // 이 코드는 도달하지 않음 (타입 체커 만족용)
        throw ConflictException::duplicateField('slug', $dto->slug ?? Str::slug($dto->title));
    }

    /**
     * 게시물 수정
     */
    public function updatePost(int $postId, int $userId, UpdatePostDTO $dto): Post
    {
        return DB::transaction(function () use ($postId, $userId, $dto) {
            $post = $this->getPostById($postId);

            // 권한 확인 (작성자만 수정 가능)
            if ($post->user_id !== $userId) {
                throw ForbiddenException::forResource('Post', $postId);
            }

            $post->update($dto->toArray());

            return $post->fresh();
        });
    }

    /**
     * 게시물 삭제
     */
    public function deletePost(int $postId, int $userId): void
    {
        DB::transaction(function () use ($postId, $userId) {
            $post = $this->getPostById($postId);

            // 권한 확인 (작성자만 삭제 가능)
            if ($post->user_id !== $userId) {
                throw ForbiddenException::forResource('Post', $postId);
            }

            $post->delete();
        });
    }

    /**
     * 게시물 발행
     */
    public function publishPost(int $postId, int $userId): Post
    {
        return DB::transaction(function () use ($postId, $userId) {
            $post = $this->getPostById($postId);

            // 권한 확인
            if ($post->user_id !== $userId) {
                throw ForbiddenException::forResource('Post', $postId);
            }

            $post->publish();

            Event::dispatch(new PostPublished($post));

            return $post->fresh();
        });
    }

    /**
     * 게시물 발행 취소
     */
    public function unpublishPost(int $postId, int $userId): Post
    {
        return DB::transaction(function () use ($postId, $userId) {
            $post = $this->getPostById($postId);

            // 권한 확인
            if ($post->user_id !== $userId) {
                throw ForbiddenException::forResource('Post', $postId);
            }

            $post->unpublish();

            Event::dispatch(new PostUnpublished($post));

            return $post->fresh();
        });
    }

    /**
     * 사용자 게시물 통계
     *
     * @return array{total: int, published: int, draft: int, private: int}
     */
    public function getUserPostStats(int $userId): array
    {
        return [
            'total' => Post::byUser($userId)->count(),
            'published' => Post::byUser($userId)->where('status', 'published')->count(),
            'draft' => Post::byUser($userId)->where('status', 'draft')->count(),
            'private' => Post::byUser($userId)->where('status', 'private')->count(),
        ];
    }
}
