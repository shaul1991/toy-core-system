<?php

declare(strict_types=1);

namespace App\Enums\Notification;

enum ChannelType: string
{
    case EMAIL = 'email';
    case SMS = 'sms';
    case SLACK = 'slack';

    public function label(): string
    {
        return match ($this) {
            self::EMAIL => '이메일',
            self::SMS => 'SMS',
            self::SLACK => 'Slack',
        };
    }

    public function requiredRecipientFields(): array
    {
        return match ($this) {
            self::EMAIL => ['email'],
            self::SMS => ['phone'],
            self::SLACK => ['slack_webhook'],
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromValues(array $values): array
    {
        return array_filter(
            array_map(fn ($value) => self::tryFrom($value), $values)
        );
    }
}
