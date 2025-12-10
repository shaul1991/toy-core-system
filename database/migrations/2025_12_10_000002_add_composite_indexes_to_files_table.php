<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * 자주 사용되는 쿼리 패턴에 최적화된 인덱스를 추가합니다.
     * - deleted_at: SoftDeletes 기본 쿼리 (WHERE deleted_at IS NULL) 최적화
     * - visibility + created_at: visibility별 최신 파일 조회
     * - disk + path: 특정 디스크/경로 내 파일 조회
     * - mime_type + visibility: MIME 타입별 공개/비공개 파일 필터링
     */
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            // SoftDeletes 기본 쿼리 최적화 (모든 쿼리에 자동 적용되는 deleted_at IS NULL)
            $table->index('deleted_at', 'files_deleted_at_idx');

            // visibility별 최신순 정렬 쿼리 최적화
            $table->index(['visibility', 'created_at'], 'files_visibility_created_at_idx');

            // 디스크/경로 기반 조회 최적화
            $table->index(['disk', 'path'], 'files_disk_path_idx');

            // MIME 타입 + visibility 필터링 최적화
            $table->index(['mime_type', 'visibility'], 'files_mime_type_visibility_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex('files_deleted_at_idx');
            $table->dropIndex('files_visibility_created_at_idx');
            $table->dropIndex('files_disk_path_idx');
            $table->dropIndex('files_mime_type_visibility_idx');
        });
    }
};
