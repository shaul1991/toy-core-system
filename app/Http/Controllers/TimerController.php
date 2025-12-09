<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Timer;
use App\Shared\Exceptions\NotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimerController extends Controller
{
    /**
     * 타이머 조회 (남은 시간 확인)
     */
    public function show(string $key): JsonResponse
    {
        $timer = Timer::where('key', $key)->first();

        if (! $timer) {
            throw NotFoundException::forResource('Timer', $key);
        }

        return $this->successResponse([
            'key' => $timer->key,
            'target_at' => $timer->target_at->toIso8601String(),
            'remaining_seconds' => $timer->remaining_seconds,
        ]);
    }

    /**
     * 타이머 설정 (upsert)
     */
    public function upsert(Request $request, string $key): JsonResponse
    {
        $validated = $request->validate([
            'target_at' => ['required', 'date'],
        ]);

        $timer = Timer::withTrashed()->where('key', $key)->first();

        if ($timer) {
            $timer->restore();
            $timer->update(['target_at' => $validated['target_at']]);
        } else {
            $timer = Timer::create([
                'key' => $key,
                'target_at' => $validated['target_at'],
            ]);
        }

        return $this->successResponse([
            'key' => $timer->key,
            'target_at' => $timer->target_at->toIso8601String(),
        ]);
    }

    /**
     * 타이머 초기화 (soft delete)
     */
    public function destroy(string $key): JsonResponse
    {
        $timer = Timer::where('key', $key)->first();

        if (! $timer) {
            throw NotFoundException::forResource('Timer', $key);
        }

        $timer->delete();

        return $this->successResponse([
            'key' => $timer->key,
            'deleted_at' => $timer->deleted_at->toIso8601String(),
        ]);
    }
}
