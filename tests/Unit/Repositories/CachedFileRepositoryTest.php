<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\File;
use App\Repositories\CachedFileRepository;
use App\Repositories\MinioFileRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class CachedFileRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CachedFileRepository $cachedRepository;

    private MinioFileRepository $minioRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->minioRepository = Mockery::mock(MinioFileRepository::class);
        $this->cachedRepository = new CachedFileRepository($this->minioRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_find_by_id_caches_result(): void
    {
        $file = File::factory()->create();

        $this->minioRepository
            ->shouldReceive('findById')
            ->once()
            ->with($file->id)
            ->andReturn($file);

        // 첫 번째 호출 - DB 조회
        $result1 = $this->cachedRepository->findById($file->id);
        $this->assertEquals($file->id, $result1->id);

        // 두 번째 호출 - 캐시에서 조회 (minioRepository는 다시 호출되지 않음)
        $result2 = $this->cachedRepository->findById($file->id);
        $this->assertEquals($file->id, $result2->id);
    }

    public function test_find_by_id_returns_null_when_not_found(): void
    {
        $nonExistentId = 'non-existent-uuid';

        $this->minioRepository
            ->shouldReceive('findById')
            ->once()
            ->with($nonExistentId)
            ->andReturn(null);

        $result = $this->cachedRepository->findById($nonExistentId);
        $this->assertNull($result);
    }

    public function test_find_by_id_with_trashed_does_not_use_cache(): void
    {
        $file = File::factory()->create();

        $this->minioRepository
            ->shouldReceive('findByIdWithTrashed')
            ->twice()
            ->with($file->id)
            ->andReturn($file);

        // 두 번 호출해도 캐시를 사용하지 않음
        $this->cachedRepository->findByIdWithTrashed($file->id);
        $this->cachedRepository->findByIdWithTrashed($file->id);
    }

    public function test_delete_invalidates_cache(): void
    {
        $file = File::factory()->create();
        $cacheKey = 'file:'.$file->id;

        // 먼저 캐시에 저장
        Cache::put($cacheKey, $file, 300);
        $this->assertTrue(Cache::has($cacheKey));

        $this->minioRepository
            ->shouldReceive('delete')
            ->once()
            ->with($file)
            ->andReturn(true);

        $this->cachedRepository->delete($file);

        // 캐시가 무효화되었는지 확인
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_force_delete_invalidates_cache(): void
    {
        $file = File::factory()->create();
        $cacheKey = 'file:'.$file->id;

        Cache::put($cacheKey, $file, 300);
        $this->assertTrue(Cache::has($cacheKey));

        $this->minioRepository
            ->shouldReceive('forceDelete')
            ->once()
            ->with($file)
            ->andReturn(true);

        $this->cachedRepository->forceDelete($file);

        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_restore_invalidates_cache(): void
    {
        $file = File::factory()->create();
        $cacheKey = 'file:'.$file->id;

        Cache::put($cacheKey, null, 300); // 삭제된 상태로 캐시됨

        $this->minioRepository
            ->shouldReceive('restore')
            ->once()
            ->with($file)
            ->andReturn(true);

        $this->cachedRepository->restore($file);

        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_update_visibility_invalidates_cache(): void
    {
        $file = File::factory()->create(['visibility' => 'private']);
        $cacheKey = 'file:'.$file->id;

        Cache::put($cacheKey, $file, 300);

        $updatedFile = clone $file;
        $updatedFile->visibility = 'public';

        $this->minioRepository
            ->shouldReceive('updateVisibility')
            ->once()
            ->with($file, 'public')
            ->andReturn($updatedFile);

        $this->cachedRepository->updateVisibility($file, 'public');

        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_paginate_does_not_use_cache(): void
    {
        $paginator = Mockery::mock(\Illuminate\Contracts\Pagination\CursorPaginator::class);

        $this->minioRepository
            ->shouldReceive('paginate')
            ->twice()
            ->with(15, null, [])
            ->andReturn($paginator);

        // 두 번 호출해도 캐시를 사용하지 않음
        $this->cachedRepository->paginate(15, null, []);
        $this->cachedRepository->paginate(15, null, []);
    }

    public function test_download_does_not_use_cache(): void
    {
        $file = File::factory()->create();
        $stream = fopen('php://memory', 'r');

        $this->minioRepository
            ->shouldReceive('download')
            ->twice()
            ->with($file)
            ->andReturn($stream);

        $this->cachedRepository->download($file);
        $this->cachedRepository->download($file);

        fclose($stream);
    }

    public function test_exists_does_not_use_cache(): void
    {
        $file = File::factory()->create();

        $this->minioRepository
            ->shouldReceive('exists')
            ->twice()
            ->with($file)
            ->andReturn(true);

        $this->cachedRepository->exists($file);
        $this->cachedRepository->exists($file);
    }

    public function test_temporary_url_does_not_use_cache(): void
    {
        $file = File::factory()->create();

        $this->minioRepository
            ->shouldReceive('temporaryUrl')
            ->twice()
            ->with($file, 60)
            ->andReturn('https://example.com/temp-url');

        $this->cachedRepository->temporaryUrl($file, 60);
        $this->cachedRepository->temporaryUrl($file, 60);
    }
}
