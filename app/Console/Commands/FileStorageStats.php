<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\File;
use Illuminate\Console\Command;

/**
 * 파일 스토리지 통계 커맨드
 *
 * 데이터베이스 기반으로 파일 스토리지 사용량 통계를 출력합니다.
 * visibility, mime_type, 기간별 통계를 제공합니다.
 */
class FileStorageStats extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'files:stats
                            {--detailed : MIME 타입별 상세 통계 표시}';

    /**
     * The console command description.
     */
    protected $description = '파일 스토리지 사용량 통계를 출력합니다';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== 파일 스토리지 통계 ===');
        $this->newLine();

        $this->showOverallStats();
        $this->showVisibilityStats();
        $this->showDiskStats();
        $this->showPeriodStats();

        if ($this->option('detailed')) {
            $this->showMimeTypeStats();
        }

        $this->showSoftDeletedStats();

        return Command::SUCCESS;
    }

    /**
     * 전체 통계
     */
    private function showOverallStats(): void
    {
        $stats = File::selectRaw('COUNT(*) as count, COALESCE(SUM(size), 0) as total_size')
            ->first();

        $this->info('[전체 현황]');
        $this->table(
            ['항목', '값'],
            [
                ['총 파일 수', number_format($stats->count).'개'],
                ['총 용량', $this->formatBytes((int) $stats->total_size)],
                ['평균 파일 크기', $stats->count > 0
                    ? $this->formatBytes((int) ($stats->total_size / $stats->count))
                    : '0 B'],
            ]
        );
        $this->newLine();
    }

    /**
     * Visibility별 통계
     */
    private function showVisibilityStats(): void
    {
        $stats = File::selectRaw('visibility, COUNT(*) as count, COALESCE(SUM(size), 0) as total_size')
            ->groupBy('visibility')
            ->get();

        $this->info('[Visibility별 현황]');
        $this->table(
            ['Visibility', '파일 수', '용량', '비율'],
            $stats->map(function ($stat) use ($stats) {
                $totalCount = $stats->sum('count');
                $percentage = $totalCount > 0 ? round(($stat->count / $totalCount) * 100, 1) : 0;

                return [
                    $stat->visibility,
                    number_format($stat->count).'개',
                    $this->formatBytes((int) $stat->total_size),
                    $percentage.'%',
                ];
            })
        );
        $this->newLine();
    }

    /**
     * 디스크별 통계
     */
    private function showDiskStats(): void
    {
        $stats = File::selectRaw('disk, COUNT(*) as count, COALESCE(SUM(size), 0) as total_size')
            ->groupBy('disk')
            ->get();

        $this->info('[디스크별 현황]');
        $this->table(
            ['디스크', '파일 수', '용량'],
            $stats->map(fn ($stat) => [
                $stat->disk,
                number_format($stat->count).'개',
                $this->formatBytes((int) $stat->total_size),
            ])
        );
        $this->newLine();
    }

    /**
     * 기간별 통계
     */
    private function showPeriodStats(): void
    {
        $periods = [
            '오늘' => now()->startOfDay(),
            '최근 7일' => now()->subDays(7),
            '최근 30일' => now()->subDays(30),
            '최근 90일' => now()->subDays(90),
        ];

        $this->info('[기간별 업로드 현황]');

        $rows = [];
        foreach ($periods as $label => $startDate) {
            $stats = File::where('created_at', '>=', $startDate)
                ->selectRaw('COUNT(*) as count, COALESCE(SUM(size), 0) as total_size')
                ->first();

            $rows[] = [
                $label,
                number_format($stats->count).'개',
                $this->formatBytes((int) $stats->total_size),
            ];
        }

        $this->table(['기간', '파일 수', '용량'], $rows);
        $this->newLine();
    }

    /**
     * MIME 타입별 통계
     */
    private function showMimeTypeStats(): void
    {
        $stats = File::selectRaw('mime_type, COUNT(*) as count, COALESCE(SUM(size), 0) as total_size')
            ->groupBy('mime_type')
            ->orderByDesc('total_size')
            ->limit(15)
            ->get();

        $this->info('[MIME 타입별 현황 (상위 15개)]');
        $this->table(
            ['MIME 타입', '파일 수', '용량'],
            $stats->map(fn ($stat) => [
                $stat->mime_type,
                number_format($stat->count).'개',
                $this->formatBytes((int) $stat->total_size),
            ])
        );
        $this->newLine();
    }

    /**
     * Soft Delete 통계
     */
    private function showSoftDeletedStats(): void
    {
        $stats = File::onlyTrashed()
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(size), 0) as total_size')
            ->first();

        if ($stats->count > 0) {
            $this->info('[삭제 대기 파일 (Soft Deleted)]');
            $this->table(
                ['항목', '값'],
                [
                    ['파일 수', number_format($stats->count).'개'],
                    ['용량', $this->formatBytes((int) $stats->total_size)],
                ]
            );
            $this->warn('삭제 대기 파일은 스토리지 공간을 차지합니다. 완전 삭제를 고려하세요.');
            $this->newLine();
        }
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
