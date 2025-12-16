<?php

declare(strict_types=1);

namespace App\Domain\Post\Events;

use App\Models\Post;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PostCreated 도메인 이벤트
 *
 * 새로운 게시물이 생성되었을 때 발행됩니다.
 *
 * 이벤트 리스너 예시:
 * - CreatePostActivityLog: 활동 로그 기록
 * - InvalidatePostCache: 캐시 무효화
 * - SendPostNotification: 구독자에게 알림 발송
 */
final class PostCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Post $post
    ) {}
}
