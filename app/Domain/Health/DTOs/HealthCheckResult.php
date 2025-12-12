<?php

declare(strict_types=1);

namespace App\Domain\Health\DTOs;

use JsonSerializable;

/**
 * Health Check 결과 DTO
 */
final readonly class HealthCheckResult implements JsonSerializable
{
    public function __construct(
        public string $name,
        public bool $healthy,
        public float $responseTimeMs,
        public ?string $message = null,
        public ?array $metadata = null,
    ) {}

    public static function healthy(
        string $name,
        float $responseTimeMs,
        ?string $message = null,
        ?array $metadata = null,
    ): self {
        return new self(
            name: $name,
            healthy: true,
            responseTimeMs: $responseTimeMs,
            message: $message,
            metadata: $metadata,
        );
    }

    public static function unhealthy(
        string $name,
        float $responseTimeMs,
        string $message,
        ?array $metadata = null,
    ): self {
        return new self(
            name: $name,
            healthy: false,
            responseTimeMs: $responseTimeMs,
            message: $message,
            metadata: $metadata,
        );
    }

    public function jsonSerialize(): array
    {
        $data = [
            'name' => $this->name,
            'status' => $this->healthy ? 'healthy' : 'unhealthy',
            'response_time_ms' => round($this->responseTimeMs, 2),
        ];

        if ($this->message !== null) {
            $data['message'] = $this->message;
        }

        if ($this->metadata !== null) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }
}
