<?php

declare(strict_types=1);

namespace App\Enums\Notification;

enum DispatchType: string
{
    case IMMEDIATE = 'immediate';
    case SCHEDULED = 'scheduled';
    case BATCHED = 'batched';

    public function label(): string
    {
        return match ($this) {
            self::IMMEDIATE => '즉시 발송',
            self::SCHEDULED => '예약 발송',
            self::BATCHED => '묶음 발송',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::IMMEDIATE => '대기 없이 즉시 발송합니다.',
            self::SCHEDULED => '지정된 시간에 발송합니다.',
            self::BATCHED => '일정 시간 동안 모인 알림을 하나로 묶어 발송합니다.',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
