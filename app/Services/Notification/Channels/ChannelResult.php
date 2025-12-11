<?php

declare(strict_types=1);

namespace App\Services\Notification\Channels;

final readonly class ChannelResult
{
    private function __construct(
        public string $channel,
        public string $status,
        public ?string $messageId = null,
        public ?string $error = null,
        public array $metadata = [],
    ) {}

    public static function success(string $channel, ?string $messageId = null, array $metadata = []): self
    {
        return new self(
            channel: $channel,
            status: 'sent',
            messageId: $messageId,
            metadata: $metadata,
        );
    }

    public static function failure(string $channel, string $error, array $metadata = []): self
    {
        return new self(
            channel: $channel,
            status: 'failed',
            error: $error,
            metadata: $metadata,
        );
    }

    public function isSuccess(): bool
    {
        return $this->status === 'sent';
    }

    public function isFailure(): bool
    {
        return $this->status === 'failed';
    }

    public function toArray(): array
    {
        $result = [
            'status' => $this->status,
        ];

        if ($this->messageId !== null) {
            $result['message_id'] = $this->messageId;
        }

        if ($this->error !== null) {
            $result['error'] = $this->error;
        }

        if (! empty($this->metadata)) {
            $result['metadata'] = $this->metadata;
        }

        return $result;
    }
}
