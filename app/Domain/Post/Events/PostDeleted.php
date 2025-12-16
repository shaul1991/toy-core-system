<?php

declare(strict_types=1);

namespace App\Domain\Post\Events;

use App\Models\Post;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PostDeleted 도메인 이벤트
 *
 * 게시물이 삭제되었을 때 발행됩니다.
 *
 * 이벤트 리스너 예시:
 * - CreatePostDeletionLog: 삭제 로그 기록
 * - InvalidatePostCache: 캐시 무효화
 * - CleanupRelatedData: 관련 데이터 정리 (이미지 파일 등)
 */
final class PostDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Post $post
    ) {}
}
