<?php

declare(strict_types=1);

namespace App\Domain\Post\Events;

use App\Models\Post;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PostUpdated 도메인 이벤트
 *
 * 게시물이 수정되었을 때 발행됩니다.
 *
 * 이벤트 리스너 예시:
 * - UpdatePostActivityLog: 활동 로그 기록
 * - InvalidatePostCache: 캐시 무효화
 * - SendPostUpdateNotification: 구독자에게 수정 알림
 */
final class PostUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Post $post
    ) {}
}
