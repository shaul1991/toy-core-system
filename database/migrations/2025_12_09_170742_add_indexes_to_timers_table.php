<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('timers', function (Blueprint $table) {
            // Soft delete 조회 최적화: WHERE key = ? AND deleted_at IS NULL
            $table->index(['key', 'deleted_at'], 'timers_key_deleted_at_index');

            // 만료된 타이머 조회/정리 최적화
            $table->index('target_at', 'timers_target_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timers', function (Blueprint $table) {
            $table->dropIndex('timers_key_deleted_at_index');
            $table->dropIndex('timers_target_at_index');
        });
    }
};
