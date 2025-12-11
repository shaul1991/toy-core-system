<?php

declare(strict_types=1);

namespace App\Enums\Notification;

enum NotificationStatus: string
{
    // Queue statuses
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case DISPATCHED = 'dispatched';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    // Log statuses
    case SENT = 'sent';
    case PARTIAL = 'partial';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => '대기 중',
            self::PROCESSING => '처리 중',
            self::DISPATCHED => '발송 완료',
            self::FAILED => '발송 실패',
            self::CANCELLED => '취소됨',
            self::SENT => '발송 성공',
            self::PARTIAL => '부분 성공',
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::DISPATCHED, self::FAILED, self::CANCELLED, self::SENT, self::PARTIAL => true,
            default => false,
        };
    }

    public function canRetry(): bool
    {
        return $this === self::FAILED;
    }

    public function canCancel(): bool
    {
        return $this === self::PENDING;
    }

    public static function queueStatuses(): array
    {
        return [
            self::PENDING,
            self::PROCESSING,
            self::DISPATCHED,
            self::FAILED,
            self::CANCELLED,
        ];
    }

    public static function logStatuses(): array
    {
        return [
            self::SENT,
            self::PARTIAL,
            self::FAILED,
        ];
    }
}
