<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MinioFileRepository implements FileRepositoryInterface
{
    private const DISK_PUBLIC = 'minio-public';

    private const DISK_PRIVATE = 'minio-private';

    /**
     * ID로 파일 조회
     */
    public function findById(string $id): ?File
    {
        return File::find($id);
    }

    /**
     * 파일 목록 조회 (Cursor 기반 페이지네이션)
     *
     * @param  array{
     *     visibility?: string,
     *     mime_type?: string,
     *     path_prefix?: string,
     *     created_from?: string,
     *     created_to?: string
     * }  $filters
     */
    public function paginate(int $perPage = 15, ?string $cursor = null, array $filters = []): \Illuminate\Contracts\Pagination\CursorPaginator
    {
        $query = File::query()->orderBy('created_at', 'desc');

        // visibility 필터
        if (isset($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }

        // mime_type 필터
        if (isset($filters['mime_type'])) {
            $query->where('mime_type', $filters['mime_type']);
        }

        // path prefix 필터
        if (isset($filters['path_prefix'])) {
            $query->where('path', 'like', $filters['path_prefix'].'%');
        }

        // created_at 범위 필터
        if (isset($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }

        if (isset($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }

        return $query->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
    }

    /**
     * ID로 파일 조회 (삭제된 것 포함)
     */
    public function findByIdWithTrashed(string $id): ?File
    {
        return File::withTrashed()->find($id);
    }

    /**
     * 파일 업로드 및 메타데이터 저장
     */
    public function upload(
        UploadedFile $file,
        string $visibility = 'private',
        ?string $path = null,
        ?array $metadata = null
    ): File {
        $disk = $this->getDiskForVisibility($visibility);
        $path = $path ?? $this->generatePath();
        $storedName = $this->generateStoredName($file);

        Storage::disk($disk)->putFileAs($path, $file, $storedName);

        return File::create([
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $storedName,
            'path' => $path,
            'disk' => $disk,
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
            'visibility' => $visibility,
            'metadata' => $metadata,
        ]);
    }

    /**
     * 파일 다운로드 스트림 반환
     *
     * @return resource|null
     */
    public function download(File $file): mixed
    {
        $fullPath = $file->full_path;

        if (! Storage::disk($file->disk)->exists($fullPath)) {
            return null;
        }

        return Storage::disk($file->disk)->readStream($fullPath);
    }

    /**
     * 파일 삭제 (soft delete, 스토리지 파일 유지)
     */
    public function delete(File $file): bool
    {
        return $file->delete();
    }

    /**
     * 파일 완전 삭제 (스토리지 파일 포함)
     */
    public function forceDelete(File $file): bool
    {
        $fullPath = $file->full_path;
        $disk = $file->disk;
        $fileId = $file->id;

        $deleted = $file->forceDelete();

        if ($deleted) {
            if (! Storage::disk($disk)->delete($fullPath)) {
                Log::warning('스토리지 파일 삭제 실패', [
                    'file_id' => $fileId,
                    'path' => $fullPath,
                    'disk' => $disk,
                ]);
            }
        }

        return $deleted;
    }

    /**
     * 삭제된 파일 복원
     */
    public function restore(File $file): bool
    {
        return $file->restore();
    }

    /**
     * 파일 visibility 변경
     */
    public function updateVisibility(File $file, string $visibility): File
    {
        $oldDisk = $file->disk;
        $newDisk = $this->getDiskForVisibility($visibility);

        if ($oldDisk !== $newDisk) {
            $this->moveFileBetweenDisks($file, $oldDisk, $newDisk);
            $file->disk = $newDisk;
        }

        $file->visibility = $visibility;
        $file->save();

        return $file->refresh();
    }

    /**
     * 파일 존재 여부 확인 (스토리지)
     */
    public function exists(File $file): bool
    {
        return Storage::disk($file->disk)->exists($file->full_path);
    }

    /**
     * 임시 URL 생성 (private 파일용)
     */
    public function temporaryUrl(File $file, int $expirationMinutes = 60): ?string
    {
        if (! $this->exists($file)) {
            return null;
        }

        return Storage::disk($file->disk)->temporaryUrl(
            $file->full_path,
            now()->addMinutes($expirationMinutes)
        );
    }

    /**
     * visibility에 따른 disk 반환
     */
    private function getDiskForVisibility(string $visibility): string
    {
        return $visibility === 'public' ? self::DISK_PUBLIC : self::DISK_PRIVATE;
    }

    /**
     * 날짜 기반 경로 생성
     */
    private function generatePath(): string
    {
        return now()->format('Y/m/d');
    }

    /**
     * 저장용 파일명 생성 (UUID + 확장자)
     */
    private function generateStoredName(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();

        return Str::uuid()->toString().($extension ? '.'.$extension : '');
    }

    /**
     * 디스크 간 파일 이동 (S3 CopyObject API 사용)
     *
     * S3 서버 사이드 복사를 사용하여 대용량 파일도 효율적으로 이동합니다.
     * 클라이언트를 거치지 않고 MinIO/S3 서버 간 직접 복사가 이루어집니다.
     *
     * @throws \RuntimeException
     */
    private function moveFileBetweenDisks(File $file, string $fromDisk, string $toDisk): void
    {
        $fullPath = $file->full_path;

        try {
            // S3 서버 사이드 복사 시도
            if ($this->copyBetweenDisksUsingS3Api($fullPath, $fromDisk, $toDisk)) {
                // 복사 성공 시 원본 삭제
                $this->deleteSourceAfterCopy($file, $fullPath, $fromDisk, $toDisk);

                return;
            }

            // S3 API 실패 시 스트림 기반 복사로 폴백
            Log::info('S3 CopyObject 실패, 스트림 복사로 폴백', [
                'file_id' => $file->id,
                'path' => $fullPath,
            ]);

            $this->copyBetweenDisksUsingStream($file, $fullPath, $fromDisk, $toDisk);
            $this->deleteSourceAfterCopy($file, $fullPath, $fromDisk, $toDisk);
        } catch (\Throwable $e) {
            // 복사 실패 시 대상 파일 정리
            if (Storage::disk($toDisk)->exists($fullPath)) {
                Storage::disk($toDisk)->delete($fullPath);
            }

            Log::error('디스크 간 파일 이동 실패', [
                'file_id' => $file->id,
                'path' => $fullPath,
                'from_disk' => $fromDisk,
                'to_disk' => $toDisk,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * S3 CopyObject API를 사용한 디스크 간 복사
     *
     * 서버 사이드 복사로 네트워크 트래픽과 메모리 사용을 최소화합니다.
     */
    private function copyBetweenDisksUsingS3Api(string $fullPath, string $fromDisk, string $toDisk): bool
    {
        try {
            $fromAdapter = Storage::disk($fromDisk);
            $toAdapter = Storage::disk($toDisk);

            // S3 클라이언트 및 버킷 정보 가져오기
            /** @var \Aws\S3\S3Client $s3Client */
            $s3Client = $fromAdapter->getClient();
            $fromBucket = $fromAdapter->getConfig()['bucket'] ?? config("filesystems.disks.{$fromDisk}.bucket");
            $toBucket = $toAdapter->getConfig()['bucket'] ?? config("filesystems.disks.{$toDisk}.bucket");

            // S3 CopyObject 실행
            $s3Client->copyObject([
                'Bucket' => $toBucket,
                'Key' => $fullPath,
                'CopySource' => urlencode("{$fromBucket}/{$fullPath}"),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning('S3 CopyObject API 호출 실패', [
                'path' => $fullPath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 스트림 기반 디스크 간 복사 (폴백)
     *
     * @throws \RuntimeException
     */
    private function copyBetweenDisksUsingStream(File $file, string $fullPath, string $fromDisk, string $toDisk): void
    {
        $stream = null;

        try {
            $stream = Storage::disk($fromDisk)->readStream($fullPath);

            if (! $stream) {
                throw new \RuntimeException("원본 파일을 읽을 수 없습니다: {$fullPath}");
            }

            $written = Storage::disk($toDisk)->writeStream($fullPath, $stream);

            if (! $written) {
                throw new \RuntimeException("대상 디스크에 파일을 쓸 수 없습니다: {$fullPath}");
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * 복사 후 원본 파일 삭제
     *
     * @throws \RuntimeException
     */
    private function deleteSourceAfterCopy(File $file, string $fullPath, string $fromDisk, string $toDisk): void
    {
        if (! Storage::disk($fromDisk)->delete($fullPath)) {
            // 원본 삭제 실패 시 대상 파일 롤백
            $targetDeleted = Storage::disk($toDisk)->delete($fullPath);

            if (! $targetDeleted) {
                Log::critical('롤백 실패: 양쪽 디스크에 파일 존재', [
                    'file_id' => $file->id,
                    'path' => $fullPath,
                    'from_disk' => $fromDisk,
                    'to_disk' => $toDisk,
                ]);
            }

            Log::error('원본 파일 삭제 실패로 롤백', [
                'file_id' => $file->id,
                'path' => $fullPath,
                'from_disk' => $fromDisk,
                'to_disk' => $toDisk,
            ]);

            throw new \RuntimeException("원본 파일 삭제에 실패했습니다: {$fullPath}");
        }
    }
}
