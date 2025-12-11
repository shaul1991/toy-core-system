<?php

declare(strict_types=1);

namespace App\Enums\Notification;

enum ChannelResultStatus: string
{
    case SENT = 'sent';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::SENT => '발송 성공',
            self::FAILED => '발송 실패',
        };
    }

    public function isSuccess(): bool
    {
        return $this === self::SENT;
    }

    public function isFailure(): bool
    {
        return $this === self::FAILED;
    }
}
