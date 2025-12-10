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
     * 디스크 간 파일 이동
     */
    private function moveFileBetweenDisks(File $file, string $fromDisk, string $toDisk): void
    {
        $fullPath = $file->full_path;

        $stream = Storage::disk($fromDisk)->readStream($fullPath);
        Storage::disk($toDisk)->writeStream($fullPath, $stream);
        Storage::disk($fromDisk)->delete($fullPath);

        if (is_resource($stream)) {
            fclose($stream);
        }
    }
}
