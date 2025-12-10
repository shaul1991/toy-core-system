<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

/**
 * 캐시 레이어가 적용된 File Repository
 * Decorator 패턴으로 MinioFileRepository를 래핑
 */
class CachedFileRepository implements FileRepositoryInterface
{
    private const CACHE_TTL_SECONDS = 300; // 5분

    private const CACHE_PREFIX = 'file:';

    public function __construct(
        private MinioFileRepository $repository
    ) {}

    /**
     * ID로 파일 조회 (캐시 적용)
     */
    public function findById(string $id): ?File
    {
        return Cache::remember(
            $this->getCacheKey($id),
            self::CACHE_TTL_SECONDS,
            fn () => $this->repository->findById($id)
        );
    }

    /**
     * ID로 파일 조회 (삭제된 것 포함, 캐시 미적용)
     * - 삭제된 파일은 캐시하지 않음
     */
    public function findByIdWithTrashed(string $id): ?File
    {
        return $this->repository->findByIdWithTrashed($id);
    }

    /**
     * 파일 목록 조회 (캐시 미적용)
     * - 목록 조회는 실시간 데이터가 필요하므로 캐시하지 않음
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
        return $this->repository->paginate($perPage, $cursor, $filters);
    }

    /**
     * 파일 업로드 (캐시 미적용, 새 파일이므로)
     */
    public function upload(
        UploadedFile $file,
        string $visibility = 'private',
        ?string $path = null,
        ?array $metadata = null
    ): File {
        return $this->repository->upload($file, $visibility, $path, $metadata);
    }

    /**
     * 파일 다운로드 (캐시 미적용, 바이너리 스트림)
     *
     * @return resource|null
     */
    public function download(File $file): mixed
    {
        return $this->repository->download($file);
    }

    /**
     * 파일 삭제 (캐시 무효화)
     */
    public function delete(File $file): bool
    {
        $result = $this->repository->delete($file);
        $this->invalidateCache($file->id);

        return $result;
    }

    /**
     * 파일 완전 삭제 (캐시 무효화)
     */
    public function forceDelete(File $file): bool
    {
        $fileId = $file->id;
        $result = $this->repository->forceDelete($file);
        $this->invalidateCache($fileId);

        return $result;
    }

    /**
     * 삭제된 파일 복원 (캐시 무효화)
     */
    public function restore(File $file): bool
    {
        $result = $this->repository->restore($file);
        $this->invalidateCache($file->id);

        return $result;
    }

    /**
     * 파일 visibility 변경 (캐시 무효화)
     */
    public function updateVisibility(File $file, string $visibility): File
    {
        $result = $this->repository->updateVisibility($file, $visibility);
        $this->invalidateCache($file->id);

        return $result;
    }

    /**
     * 파일 존재 여부 확인 (캐시 미적용, 스토리지 직접 확인)
     */
    public function exists(File $file): bool
    {
        return $this->repository->exists($file);
    }

    /**
     * 임시 URL 생성 (캐시 미적용, 매번 새 URL 필요)
     */
    public function temporaryUrl(File $file, int $expirationMinutes = 60): ?string
    {
        return $this->repository->temporaryUrl($file, $expirationMinutes);
    }

    /**
     * 캐시 키 생성
     */
    private function getCacheKey(string $id): string
    {
        return self::CACHE_PREFIX.$id;
    }

    /**
     * 캐시 무효화
     */
    private function invalidateCache(string $id): void
    {
        Cache::forget($this->getCacheKey($id));
    }
}
