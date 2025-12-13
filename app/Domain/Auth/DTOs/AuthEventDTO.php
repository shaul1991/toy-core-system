<?php

declare(strict_types=1);

namespace App\Domain\Auth\DTOs;

use DateTimeImmutable;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

/**
 * 인증 이벤트 DTO
 *
 * MongoDB auth_events 컬렉션에 저장되는 인증 이벤트 데이터입니다.
 */
final readonly class AuthEventDTO
{
    /**
     * 인증 이벤트 액션 상수
     */
    public const ACTION_LOGIN = 'login';

    public const ACTION_LOGOUT = 'logout';

    public const ACTION_LOGOUT_ALL = 'logout_all';

    public const ACTION_TOKEN_REFRESH = 'token_refresh';

    public const ACTION_TOKEN_REFRESH_FAILED = 'token_refresh_failed';

    public const ACTION_TOKEN_REUSE_DETECTED = 'token_reuse_detected';

    public const ACTION_SOCIAL_LINK = 'social_link';

    public const ACTION_SOCIAL_UNLINK = 'social_unlink';

    /**
     * 인증 이벤트 결과 상수
     */
    public const RESULT_SUCCESS = 'success';

    public const RESULT_FAILURE = 'failure';

    public function __construct(
        public ?string $id,
        public ?int $userId,
        public string $action,
        public string $result,
        public ?string $provider = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public ?array $metadata = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?DateTimeImmutable $createdAt = null,
    ) {}

    /**
     * Create from MongoDB document
     */
    public static function fromDocument(object $document): self
    {
        return new self(
            id: isset($document->_id) ? (string) $document->_id : null,
            userId: isset($document->user_id) ? (int) $document->user_id : null,
            action: $document->action,
            result: $document->result,
            provider: $document->provider ?? null,
            errorCode: $document->error_code ?? null,
            errorMessage: $document->error_message ?? null,
            metadata: isset($document->metadata) ? (array) $document->metadata : null,
            ipAddress: $document->ip_address ?? null,
            userAgent: $document->user_agent ?? null,
            createdAt: isset($document->created_at)
                ? self::convertToDateTime($document->created_at)
                : null,
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
            'result' => $this->result,
            'provider' => $this->provider,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
            'metadata' => $this->metadata ? (object) $this->metadata : null,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'created_at' => $this->createdAt ? new UTCDateTime($this->createdAt) : $now,
        ];

        if ($this->id) {
            $document['_id'] = new ObjectId($this->id);
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
            'result' => $this->result,
            'provider' => $this->provider,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
            'metadata' => $this->metadata,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'created_at' => $this->createdAt?->format('c'),
        ];
    }

    private static function convertToDateTime(mixed $value): ?DateTimeImmutable
    {
        if ($value instanceof UTCDateTime) {
            return DateTimeImmutable::createFromMutable($value->toDateTime());
        }

        if (is_string($value)) {
            try {
                return new DateTimeImmutable($value);
            } catch (\Exception) {
                return null;
            }
        }

        return null;
    }
}
