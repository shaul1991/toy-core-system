<?php

declare(strict_types=1);

namespace Tests\Feature\File;

use App\Models\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('minio-public');
        Storage::fake('minio-private');
    }

    // ========================================
    // POST /api/files - 파일 업로드
    // ========================================

    public function test_can_upload_file_with_default_visibility(): void
    {
        $file = UploadedFile::fake()->image('test-image.jpg', 100, 100);

        $response = $this->postJson('/api/files', [
            'file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'original_name' => 'test-image.jpg',
                    'mime_type' => 'image/jpeg',
                    'visibility' => 'private',
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'original_name',
                    'mime_type',
                    'size',
                    'human_readable_size',
                    'visibility',
                    'url',
                    'metadata',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('files', [
            'original_name' => 'test-image.jpg',
            'visibility' => 'private',
        ]);
    }

    public function test_can_upload_file_with_public_visibility(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = $this->postJson('/api/files', [
            'file' => $file,
            'visibility' => 'public',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'visibility' => 'public',
                ],
            ]);

        $this->assertDatabaseHas('files', [
            'original_name' => 'document.pdf',
            'visibility' => 'public',
        ]);
    }

    public function test_can_upload_file_with_custom_path(): void
    {
        $file = UploadedFile::fake()->image('avatar.png');

        $response = $this->postJson('/api/files', [
            'file' => $file,
            'path' => 'users/avatars',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('files', [
            'path' => 'users/avatars',
        ]);
    }

    public function test_can_upload_file_with_metadata(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->postJson('/api/files', [
            'file' => $file,
            'metadata' => ['user_id' => 123, 'category' => 'profile'],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'metadata' => ['user_id' => 123, 'category' => 'profile'],
                ],
            ]);
    }

    public function test_upload_requires_file(): void
    {
        $response = $this->postJson('/api/files', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_upload_validates_visibility(): void
    {
        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('/api/files', [
            'file' => $file,
            'visibility' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['visibility']);
    }

    // ========================================
    // GET /api/files/{id} - 파일 메타데이터 조회
    // ========================================

    public function test_can_get_file_metadata(): void
    {
        $file = File::create([
            'original_name' => 'test.jpg',
            'stored_name' => 'abc123.jpg',
            'path' => '2025/01/01',
            'disk' => 'minio-private',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'visibility' => 'private',
        ]);

        $response = $this->getJson("/api/files/{$file->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $file->id,
                    'original_name' => 'test.jpg',
                    'mime_type' => 'image/jpeg',
                    'size' => 1024,
                    'visibility' => 'private',
                ],
            ]);
    }

    public function test_get_file_returns_404_when_not_found(): void
    {
        $response = $this->getJson('/api/files/nonexistent-uuid');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                ],
            ]);
    }

    public function test_deleted_file_is_not_accessible(): void
    {
        $file = File::create([
            'original_name' => 'deleted.jpg',
            'stored_name' => 'del123.jpg',
            'path' => '2025/01/01',
            'disk' => 'minio-private',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'visibility' => 'private',
        ]);
        $file->delete();

        $response = $this->getJson("/api/files/{$file->id}");

        $response->assertStatus(404);
    }

    // ========================================
    // GET /api/files/{id}/download - 파일 다운로드
    // ========================================

    public function test_can_download_file(): void
    {
        Storage::disk('minio-private')->put('2025/01/01/test123.txt', 'Hello World');

        $file = File::create([
            'original_name' => 'hello.txt',
            'stored_name' => 'test123.txt',
            'path' => '2025/01/01',
            'disk' => 'minio-private',
            'mime_type' => 'text/plain',
            'size' => 11,
            'visibility' => 'private',
        ]);

        $response = $this->get("/api/files/{$file->id}/download");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/plain')
            ->assertHeader('Content-Disposition', 'attachment; filename=hello.txt');
    }

    // ========================================
    // DELETE /api/files/{id} - 파일 삭제 (soft delete)
    // ========================================

    public function test_can_soft_delete_file(): void
    {
        $file = File::create([
            'original_name' => 'to-delete.jpg',
            'stored_name' => 'delete123.jpg',
            'path' => '2025/01/01',
            'disk' => 'minio-private',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'visibility' => 'private',
        ]);

        $response = $this->deleteJson("/api/files/{$file->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $file->id,
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'deleted_at'],
            ]);

        $this->assertSoftDeleted('files', ['id' => $file->id]);
    }

    public function test_delete_returns_404_when_not_found(): void
    {
        $response = $this->deleteJson('/api/files/nonexistent-uuid');

        $response->assertStatus(404);
    }

    // ========================================
    // DELETE /api/files/{id}/force - 파일 완전 삭제
    // ========================================

    public function test_can_force_delete_file(): void
    {
        Storage::disk('minio-private')->put('2025/01/01/force-del.jpg', 'content');

        $file = File::create([
            'original_name' => 'force-delete.jpg',
            'stored_name' => 'force-del.jpg',
            'path' => '2025/01/01',
            'disk' => 'minio-private',
            'mime_type' => 'image/jpeg',
            'size' => 7,
            'visibility' => 'private',
        ]);

        $response = $this->deleteJson("/api/files/{$file->id}/force");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $file->id,
                    'message' => '파일이 완전히 삭제되었습니다.',
                ],
            ]);

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
        Storage::disk('minio-private')->assertMissing('2025/01/01/force-del.jpg');
    }

    // ========================================
    // PATCH /api/files/{id}/visibility - visibility 변경
    // ========================================

    public function test_can_update_visibility_to_public(): void
    {
        Storage::disk('minio-private')->put('2025/01/01/vis-test.jpg', 'content');

        $file = File::create([
            'original_name' => 'visibility-test.jpg',
            'stored_name' => 'vis-test.jpg',
            'path' => '2025/01/01',
            'disk' => 'minio-private',
            'mime_type' => 'image/jpeg',
            'size' => 7,
            'visibility' => 'private',
        ]);

        $response = $this->patchJson("/api/files/{$file->id}/visibility", [
            'visibility' => 'public',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'visibility' => 'public',
                ],
            ]);
    }

    public function test_update_visibility_requires_visibility_field(): void
    {
        $file = File::create([
            'original_name' => 'test.jpg',
            'stored_name' => 'test123.jpg',
            'path' => '2025/01/01',
            'disk' => 'minio-private',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'visibility' => 'private',
        ]);

        $response = $this->patchJson("/api/files/{$file->id}/visibility", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['visibility']);
    }

    public function test_update_visibility_validates_value(): void
    {
        $file = File::create([
            'original_name' => 'test.jpg',
            'stored_name' => 'test123.jpg',
            'path' => '2025/01/01',
            'disk' => 'minio-private',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'visibility' => 'private',
        ]);

        $response = $this->patchJson("/api/files/{$file->id}/visibility", [
            'visibility' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['visibility']);
    }

    // ========================================
    // POST /api/files/{id}/temporary-url - 임시 URL 생성
    // ========================================

    public function test_can_generate_temporary_url(): void
    {
        Storage::disk('minio-private')->put('2025/01/01/temp-url.jpg', 'content');

        $file = File::create([
            'original_name' => 'temp-url-test.jpg',
            'stored_name' => 'temp-url.jpg',
            'path' => '2025/01/01',
            'disk' => 'minio-private',
            'mime_type' => 'image/jpeg',
            'size' => 7,
            'visibility' => 'private',
        ]);

        $response = $this->postJson("/api/files/{$file->id}/temporary-url");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['url', 'expires_at'],
            ]);
    }

    public function test_can_generate_temporary_url_with_custom_expiration(): void
    {
        Storage::disk('minio-private')->put('2025/01/01/temp-url2.jpg', 'content');

        $file = File::create([
            'original_name' => 'temp-url-test2.jpg',
            'stored_name' => 'temp-url2.jpg',
            'path' => '2025/01/01',
            'disk' => 'minio-private',
            'mime_type' => 'image/jpeg',
            'size' => 7,
            'visibility' => 'private',
        ]);

        $response = $this->postJson("/api/files/{$file->id}/temporary-url", [
            'expiration_minutes' => 120,
        ]);

        $response->assertStatus(200);
    }

    public function test_temporary_url_validates_expiration_range(): void
    {
        $file = File::create([
            'original_name' => 'test.jpg',
            'stored_name' => 'test123.jpg',
            'path' => '2025/01/01',
            'disk' => 'minio-private',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'visibility' => 'private',
        ]);

        $response = $this->postJson("/api/files/{$file->id}/temporary-url", [
            'expiration_minutes' => 20000, // 최대 7일(10080분) 초과
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['expiration_minutes']);
    }

    // ========================================
    // GET /api/files - 파일 목록 조회
    // ========================================

    public function test_can_list_files(): void
    {
        File::factory()->count(5)->create(['visibility' => 'private']);

        $response = $this->getJson('/api/files');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'original_name',
                        'mime_type',
                        'size',
                        'visibility',
                    ],
                ],
                'pagination' => [
                    'type',
                    'per_page',
                    'next_cursor',
                    'prev_cursor',
                    'has_more_pages',
                ],
            ]);
    }

    public function test_can_list_files_with_visibility_filter(): void
    {
        File::factory()->count(3)->create(['visibility' => 'public']);
        File::factory()->count(2)->create(['visibility' => 'private']);

        $response = $this->getJson('/api/files?visibility=public');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }

    public function test_can_list_files_with_mime_type_filter(): void
    {
        File::factory()->count(2)->create(['mime_type' => 'image/jpeg']);
        File::factory()->count(3)->create(['mime_type' => 'application/pdf']);

        $response = $this->getJson('/api/files?mime_type=image/jpeg');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_list_files_with_pagination(): void
    {
        File::factory()->count(20)->create();

        $response = $this->getJson('/api/files?per_page=5');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
        $this->assertTrue($response->json('pagination.has_more_pages'));
    }

    public function test_list_validates_per_page_range(): void
    {
        $response = $this->getJson('/api/files?per_page=200');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    // ========================================
    // 다운로드 최적화 테스트
    // ========================================

    public function test_download_returns_etag_header(): void
    {
        Storage::disk('minio-private')->put('2025/01/01/etag-test.txt', 'Hello World');

        $file = File::create([
            'original_name' => 'etag-test.txt',
            'stored_name' => 'etag-test.txt',
            'path' => '2025/01/01',
            'disk' => 'minio-private',
            'mime_type' => 'text/plain',
            'size' => 11,
            'visibility' => 'private',
        ]);

        $response = $this->get("/api/files/{$file->id}/download");

        $response->assertStatus(200)
            ->assertHeader('ETag');
    }

    public function test_download_returns_last_modified_header(): void
    {
        Storage::disk('minio-private')->put('2025/01/01/lm-test.txt', 'Hello World');

        $file = File::create([
            'original_name' => 'lm-test.txt',
            'stored_name' => 'lm-test.txt',
            'path' => '2025/01/01',
            'disk' => 'minio-private',
            'mime_type' => 'text/plain',
            'size' => 11,
            'visibility' => 'private',
        ]);

        $response = $this->get("/api/files/{$file->id}/download");

        $response->assertStatus(200)
            ->assertHeader('Last-Modified');
    }

    public function test_download_returns_304_with_matching_etag(): void
    {
        Storage::disk('minio-private')->put('2025/01/01/304-test.txt', 'Hello World');

        $file = File::create([
            'original_name' => '304-test.txt',
            'stored_name' => '304-test.txt',
            'path' => '2025/01/01',
            'disk' => 'minio-private',
            'mime_type' => 'text/plain',
            'size' => 11,
            'visibility' => 'private',
        ]);

        $response = $this->withHeaders([
            'If-None-Match' => $file->etag,
        ])->get("/api/files/{$file->id}/download");

        $response->assertStatus(304);
    }

    public function test_download_returns_304_with_if_modified_since(): void
    {
        Storage::disk('minio-private')->put('2025/01/01/ims-test.txt', 'Hello World');

        $file = File::create([
            'original_name' => 'ims-test.txt',
            'stored_name' => 'ims-test.txt',
            'path' => '2025/01/01',
            'disk' => 'minio-private',
            'mime_type' => 'text/plain',
            'size' => 11,
            'visibility' => 'private',
        ]);

        $response = $this->withHeaders([
            'If-Modified-Since' => $file->last_modified,
        ])->get("/api/files/{$file->id}/download");

        $response->assertStatus(304);
    }

    public function test_download_returns_accept_ranges_header(): void
    {
        Storage::disk('minio-private')->put('2025/01/01/range-test.txt', 'Hello World');

        $file = File::create([
            'original_name' => 'range-test.txt',
            'stored_name' => 'range-test.txt',
            'path' => '2025/01/01',
            'disk' => 'minio-private',
            'mime_type' => 'text/plain',
            'size' => 11,
            'visibility' => 'private',
        ]);

        $response = $this->get("/api/files/{$file->id}/download");

        $response->assertStatus(200)
            ->assertHeader('Accept-Ranges', 'bytes');
    }

    public function test_download_handles_range_request(): void
    {
        Storage::disk('minio-private')->put('2025/01/01/partial-test.txt', 'Hello World');

        $file = File::create([
            'original_name' => 'partial-test.txt',
            'stored_name' => 'partial-test.txt',
            'path' => '2025/01/01',
            'disk' => 'minio-private',
            'mime_type' => 'text/plain',
            'size' => 11,
            'visibility' => 'private',
        ]);

        $response = $this->withHeaders([
            'Range' => 'bytes=0-4',
        ])->get("/api/files/{$file->id}/download");

        $response->assertStatus(206)
            ->assertHeader('Content-Range', 'bytes 0-4/11')
            ->assertHeader('Content-Length', '5');
    }

    public function test_download_handles_suffix_range_request(): void
    {
        Storage::disk('minio-private')->put('2025/01/01/suffix-test.txt', 'Hello World');

        $file = File::create([
            'original_name' => 'suffix-test.txt',
            'stored_name' => 'suffix-test.txt',
            'path' => '2025/01/01',
            'disk' => 'minio-private',
            'mime_type' => 'text/plain',
            'size' => 11,
            'visibility' => 'private',
        ]);

        $response = $this->withHeaders([
            'Range' => 'bytes=-5',
        ])->get("/api/files/{$file->id}/download");

        $response->assertStatus(206)
            ->assertHeader('Content-Range', 'bytes 6-10/11');
    }
}
