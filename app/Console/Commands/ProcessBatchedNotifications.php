<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessBatchedNotificationsJob;
use App\Repositories\NotificationQueueRepositoryInterface;
use Illuminate\Console\Command;

class ProcessBatchedNotifications extends Command
{
    protected $signature = 'notification:process-batched';

    protected $description = '묶음 알림 중 대기 시간이 지난 알림 그룹을 처리합니다.';

    public function handle(NotificationQueueRepositoryInterface $repository): int
    {
        $this->info('묶음 알림 처리 시작...');

        $groups = $repository->getBatchedReadyGroups();

        if ($groups->isEmpty()) {
            $this->info('처리할 묶음 알림이 없습니다.');

            return Command::SUCCESS;
        }

        $this->info("처리할 묶음 그룹: {$groups->count()}개");

        $dispatched = 0;

        foreach ($groups as $group) {
            ProcessBatchedNotificationsJob::dispatch($group->batch_key);
            $dispatched++;

            $this->line("  - Batch '{$group->batch_key}' ({$group->count}건) 발송 예약됨");
        }

        $this->info("완료: {$dispatched}개의 묶음 그룹이 발송 대기열에 추가되었습니다.");

        return Command::SUCCESS;
    }
}
