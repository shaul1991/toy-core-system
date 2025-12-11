<?php

declare(strict_types=1);

namespace App\Services\Notification\Channels;

use App\Enums\Notification\ChannelType;
use App\Models\NotificationQueue;

interface NotificationChannelInterface
{
    public function getType(): ChannelType;

    public function send(NotificationQueue $queue): ChannelResult;

    public function sendBatch(array $queues, array $aggregatedPayload): ChannelResult;

    public function validateRecipient(array $recipient): bool;
}
