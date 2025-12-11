<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\DispatchNotificationJob;
use App\Repositories\NotificationQueueRepositoryInterface;
use Illuminate\Console\Command;

class ProcessScheduledNotifications extends Command
{
    protected $signature = 'notification:process-scheduled
                            {--limit=100 : 한 번에 처리할 최대 알림 수}';

    protected $description = '예약된 알림 중 발송 시간이 된 알림을 처리합니다.';

    public function handle(NotificationQueueRepositoryInterface $repository): int
    {
        $limit = (int) $this->option('limit');

        $this->info('예약 알림 처리 시작...');

        $queues = $repository->getScheduledReady($limit);

        if ($queues->isEmpty()) {
            $this->info('처리할 예약 알림이 없습니다.');

            return Command::SUCCESS;
        }

        $this->info("처리할 예약 알림: {$queues->count()}건");

        $dispatched = 0;

        foreach ($queues as $queue) {
            DispatchNotificationJob::dispatch($queue->id);
            $dispatched++;

            $this->line("  - Queue #{$queue->id} ({$queue->type}) 발송 예약됨");
        }

        $this->info("완료: {$dispatched}건의 알림이 발송 대기열에 추가되었습니다.");

        return Command::SUCCESS;
    }
}
