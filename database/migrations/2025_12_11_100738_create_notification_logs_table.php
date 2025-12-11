<?php

declare(strict_types=1);

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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();

            // 원본 대기열 참조
            $table->unsignedBigInteger('queue_id')->nullable();  // 단건 발송 시
            $table->json('batch_queue_ids')->nullable();  // 묶음 발송 시 포함된 queue_id 목록

            // 알림 정보
            $table->string('type', 100);
            $table->json('channels');
            $table->json('recipient');
            $table->json('payload');

            // 발송 결과
            $table->string('status', 20);  // sent, partial, failed
            $table->json('channel_results');  // {"email": {"status": "sent", "message_id": "..."}, ...}

            // 타임스탬프
            $table->timestamp('sent_at');
            $table->timestamps();

            // 인덱스
            $table->index('queue_id');
            $table->index('type');
            $table->index('status');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
