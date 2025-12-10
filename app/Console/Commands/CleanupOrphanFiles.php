<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\File;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * 고아 파일 정리 커맨드
 *
 * 스토리지에 존재하지만 데이터베이스에 레코드가 없는 파일을 찾아 정리합니다.
 * 주기적으로 실행하여 스토리지 공간을 효율적으로 관리할 수 있습니다.
 */
class CleanupOrphanFiles extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'files:cleanup-orphans
                            {--disk=all : 정리할 디스크 (all, minio-public, minio-private)}
                            {--dry-run : 실제 삭제 없이 대상 파일만 출력}
                            {--older-than=7 : N일 이상 된 파일만 대상 (기본: 7일)}
                            {--batch-size=100 : 한 번에 처리할 파일 수}';

    /**
     * The console command description.
     */
    protected $description = '데이터베이스에 없는 고아 파일을 스토리지에서 정리합니다';

    private const DISKS = ['minio-public', 'minio-private'];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $diskOption = $this->option('disk');
        $dryRun = (bool) $this->option('dry-run');
        $olderThanDays = (int) $this->option('older-than');
        $batchSize = (int) $this->option('batch-size');

        $disks = $diskOption === 'all' ? self::DISKS : [$diskOption];

        if (! in_array($diskOption, array_merge(['all'], self::DISKS))) {
            $this->error("유효하지 않은 디스크: {$diskOption}");
            $this->info('사용 가능: all, minio-public, minio-private');

            return Command::FAILURE;
        }

        $this->info($dryRun ? '[DRY RUN] 실제 삭제 없이 대상 파일만 출력합니다.' : '고아 파일 정리를 시작합니다.');
        $this->info("대상: {$olderThanDays}일 이상 된 파일");
        $this->newLine();

        $totalOrphans = 0;
        $totalDeleted = 0;
        $totalSize = 0;

        foreach ($disks as $disk) {
            $this->info("디스크 [{$disk}] 스캔 중...");

            [$orphans, $deleted, $size] = $this->processDisk($disk, $dryRun, $olderThanDays, $batchSize);

            $totalOrphans += $orphans;
            $totalDeleted += $deleted;
            $totalSize += $size;

            $this->newLine();
        }

        $this->newLine();
        $this->info('=== 결과 요약 ===');
        $this->info("발견된 고아 파일: {$totalOrphans}개");

        if ($dryRun) {
            $this->info('예상 확보 용량: '.$this->formatBytes($totalSize));
        } else {
            $this->info("삭제된 파일: {$totalDeleted}개");
            $this->info('확보된 용량: '.$this->formatBytes($totalSize));
        }

        return Command::SUCCESS;
    }

    /**
     * 디스크별 고아 파일 처리
     *
     * @return array{int, int, int} [발견된 수, 삭제된 수, 총 크기]
     */
    private function processDisk(string $disk, bool $dryRun, int $olderThanDays, int $batchSize): array
    {
        $storage = Storage::disk($disk);
        $cutoffDate = now()->subDays($olderThanDays);

        $orphans = 0;
        $deleted = 0;
        $totalSize = 0;

        // 스토리지의 모든 파일 경로 가져오기
        $allFiles = $storage->allFiles();
        $batches = array_chunk($allFiles, $batchSize);

        $progressBar = $this->output->createProgressBar(count($allFiles));
        $progressBar->start();

        foreach ($batches as $batch) {
            $storedNames = [];

            foreach ($batch as $filePath) {
                $storedName = basename($filePath);
                $storedNames[$storedName] = $filePath;
            }

            // DB에 존재하는 파일들 조회
            $existingFiles = File::withTrashed()
                ->whereIn('stored_name', array_keys($storedNames))
                ->where('disk', $disk)
                ->pluck('stored_name')
                ->toArray();

            // 고아 파일 처리
            foreach ($storedNames as $storedName => $filePath) {
                if (in_array($storedName, $existingFiles)) {
                    $progressBar->advance();

                    continue;
                }

                // 파일 수정 시간 확인
                $lastModified = $storage->lastModified($filePath);

                if ($lastModified > $cutoffDate->timestamp) {
                    $progressBar->advance();

                    continue; // 최근 파일은 스킵
                }

                $fileSize = $storage->size($filePath);
                $orphans++;
                $totalSize += $fileSize;

                if ($dryRun) {
                    $progressBar->advance();

                    continue;
                }

                // 실제 삭제
                if ($storage->delete($filePath)) {
                    $deleted++;

                    Log::info('고아 파일 삭제됨', [
                        'disk' => $disk,
                        'path' => $filePath,
                        'size' => $fileSize,
                    ]);
                } else {
                    Log::warning('고아 파일 삭제 실패', [
                        'disk' => $disk,
                        'path' => $filePath,
                    ]);
                }

                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine();

        return [$orphans, $deleted, $totalSize];
    }

    /**
     * 바이트를 사람이 읽기 쉬운 형식으로 변환
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
