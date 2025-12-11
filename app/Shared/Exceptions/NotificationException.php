<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use App\Shared\Http\ApiResponseCode;

class NotificationException extends DomainException
{
    protected ApiResponseCode $responseCode = ApiResponseCode::BAD_REQUEST;

    public static function invalidChannel(string $channel): static
    {
        return (new static("지원하지 않는 채널입니다: {$channel}"))
            ->withDetails(['channel' => $channel]);
    }

    public static function missingRecipient(string $channel, array $requiredFields): static
    {
        return (new static("{$channel} 채널에 필요한 수신자 정보가 없습니다."))
            ->withDetails([
                'channel' => $channel,
                'required_fields' => $requiredFields,
            ]);
    }

    public static function queueNotFound(int $queueId): static
    {
        $exception = new static("알림 대기열을 찾을 수 없습니다: {$queueId}");
        $exception->responseCode = ApiResponseCode::NOT_FOUND;

        return $exception->withDetails(['queue_id' => $queueId]);
    }

    public static function logNotFound(int $logId): static
    {
        $exception = new static("알림 로그를 찾을 수 없습니다: {$logId}");
        $exception->responseCode = ApiResponseCode::NOT_FOUND;

        return $exception->withDetails(['log_id' => $logId]);
    }

    public static function cannotCancel(int $queueId, string $status): static
    {
        return (new static('이 상태에서는 알림을 취소할 수 없습니다.'))
            ->withDetails([
                'queue_id' => $queueId,
                'current_status' => $status,
                'cancellable_status' => 'pending',
            ]);
    }

    public static function cannotRetry(int $queueId, string $status): static
    {
        return (new static('이 상태에서는 알림을 재시도할 수 없습니다.'))
            ->withDetails([
                'queue_id' => $queueId,
                'current_status' => $status,
                'retriable_status' => 'failed',
            ]);
    }

    public static function scheduledAtRequired(): static
    {
        return (new static('예약 발송에는 scheduled_at이 필요합니다.'))
            ->withDetails(['dispatch_type' => 'scheduled']);
    }

    public static function scheduledAtMustBeFuture(): static
    {
        return (new static('예약 시간은 현재 시간보다 이후여야 합니다.'))
            ->withDetails(['dispatch_type' => 'scheduled']);
    }

    public static function batchKeyRequired(): static
    {
        return (new static('묶음 발송에는 batch_key가 필요합니다.'))
            ->withDetails(['dispatch_type' => 'batched']);
    }

    public static function channelFailed(string $channel, string $reason): static
    {
        return (new static("{$channel} 채널 발송에 실패했습니다: {$reason}"))
            ->withDetails([
                'channel' => $channel,
                'reason' => $reason,
            ]);
    }

    public static function allChannelsFailed(array $failures): static
    {
        return (new static('모든 채널 발송에 실패했습니다.'))
            ->withDetails(['failures' => $failures]);
    }

    public static function dispatchFailed(int $queueId, string $reason): static
    {
        $exception = new static("알림 발송에 실패했습니다: {$reason}");
        $exception->responseCode = ApiResponseCode::INTERNAL_ERROR;

        return $exception->withDetails([
            'queue_id' => $queueId,
            'reason' => $reason,
        ]);
    }

    public static function maxRetriesExceeded(int $queueId, int $attempts): static
    {
        return (new static('최대 재시도 횟수를 초과했습니다.'))
            ->withDetails([
                'queue_id' => $queueId,
                'attempts' => $attempts,
            ]);
    }
}
