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
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
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
}
