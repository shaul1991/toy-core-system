<?php

declare(strict_types=1);

namespace App\Domain\Post\Events;

use App\Models\Post;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PostUnpublished 도메인 이벤트
 *
 * 게시물 발행이 취소되었을 때 발행됩니다.
 *
 * 이벤트 리스너 예시:
 * - InvalidatePublishedPostsCache: 발행된 게시물 목록 캐시 무효화
 * - CreateUnpublishActivityLog: 발행 취소 활동 로그 기록
 * - RemovePostFromSearch: 검색 엔진에서 제거 (Elasticsearch 등)
 */
final class PostUnpublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Post $post
    ) {}
}
