<?php

declare(strict_types=1);

namespace App\Domain\Post\Events;

use App\Models\Post;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PostPublished 도메인 이벤트
 *
 * 게시물이 발행되었을 때 발행됩니다.
 *
 * 이벤트 리스너 예시:
 * - SendPublishNotification: 구독자에게 새 게시물 알림
 * - InvalidatePublishedPostsCache: 발행된 게시물 목록 캐시 무효화
 * - CreatePublishActivityLog: 발행 활동 로그 기록
 * - IndexPostToSearch: 검색 엔진에 색인 (Elasticsearch 등)
 */
final class PostPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Post $post
    ) {}
}
