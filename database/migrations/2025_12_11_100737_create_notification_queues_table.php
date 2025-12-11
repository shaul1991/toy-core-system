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
        Schema::create('notification_queues', function (Blueprint $table) {
            $table->id();

            // 발송 유형
            $table->string('dispatch_type', 20);  // immediate, scheduled, batched

            // 예약 발송용
            $table->timestamp('scheduled_at')->nullable();

            // 묶음 발송용
            $table->string('batch_key', 100)->nullable();  // 그룹화 키
            $table->unsignedInteger('batch_window')->nullable();  // 묶음 간격 (초)

            // 알림 정보
            $table->string('type', 100);  // 알림 유형 (welcome, order_complete 등)
            $table->json('channels');  // ["email", "sms", "slack"]
            $table->json('recipient');  // 수신자 정보
            $table->json('payload');  // 알림 데이터
            $table->unsignedInteger('priority')->default(0);  // 우선순위 (높을수록 먼저)

            // 상태
            $table->string('status', 20)->default('pending');  // pending, processing, dispatched, failed, cancelled
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();

            // 타임스탬프
            $table->timestamps();
            $table->timestamp('dispatched_at')->nullable();

            // 인덱스
            $table->index(['dispatch_type', 'status']);
            $table->index('scheduled_at');
            $table->index('batch_key');
            $table->index(['priority', 'created_at']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_queues');
    }
};
