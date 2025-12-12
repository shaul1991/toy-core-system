<?php

declare(strict_types=1);

namespace App\Domain\UserActivity\DTOs;

use DateTimeImmutable;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

final readonly class UserActivityDTO
{
    public function __construct(
        public ?string $id,
        public int $userId,
        public string $action,
        public ?string $targetType = null,
        public ?string $targetId = null,
        public ?array $metadata = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
    ) {}

    /**
     * Create from MongoDB document
     */
    public static function fromDocument(object $document): self
    {
        return new self(
            id: isset($document->_id) ? (string) $document->_id : null,
            userId: (int) $document->user_id,
            action: $document->action,
            targetType: $document->target_type ?? null,
            targetId: $document->target_id ?? null,
            metadata: isset($document->metadata) ? (array) $document->metadata : null,
            ipAddress: $document->ip_address ?? null,
            userAgent: $document->user_agent ?? null,
            createdAt: isset($document->created_at)
                ? self::convertToDateTime($document->created_at)
                : null,
            updatedAt: isset($document->updated_at)
                ? self::convertToDateTime($document->updated_at)
                : null,
        );
    }

    /**
     * Create from request data
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromRequest(array $data): self
    {
        return new self(
            id: null,
            userId: (int) $data['user_id'],
            action: $data['action'],
            targetType: $data['target_type'] ?? null,
            targetId: $data['target_id'] ?? null,
            metadata: $data['metadata'] ?? null,
            ipAddress: $data['ip_address'] ?? null,
            userAgent: $data['user_agent'] ?? null,
        );
    }

    /**
     * Convert to MongoDB document format
     *
     * @return array<string, mixed>
     */
    public function toDocument(): array
    {
        $now = new UTCDateTime;

        $document = [
            'user_id' => $this->userId,
            'action' => $this->action,
            'target_type' => $this->targetType,
            'target_id' => $this->targetId,
            'metadata' => $this->metadata ? (object) $this->metadata : null,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'updated_at' => $now,
        ];

        if ($this->id) {
            $document['_id'] = new ObjectId($this->id);
        }

        if (! $this->createdAt) {
            $document['created_at'] = $now;
        } else {
            $document['created_at'] = new UTCDateTime($this->createdAt);
        }

        return $document;
    }

    /**
     * Convert to array for API response
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'action' => $this->action,
            'target_type' => $this->targetType,
            'target_id' => $this->targetId,
            'metadata' => $this->metadata,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'created_at' => $this->createdAt?->format('c'),
            'updated_at' => $this->updatedAt?->format('c'),
        ];
    }

    private static function convertToDateTime(mixed $value): ?DateTimeImmutable
    {
        if ($value instanceof UTCDateTime) {
            return DateTimeImmutable::createFromMutable($value->toDateTime());
        }

        if (is_string($value)) {
            return new DateTimeImmutable($value);
        }

        return null;
    }
}
