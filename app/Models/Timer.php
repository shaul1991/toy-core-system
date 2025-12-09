<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Timer extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'target_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'target_at' => 'datetime',
        ];
    }

    /**
     * Get the remaining seconds until the target time.
     * Negative value means time remaining, positive means time elapsed.
     */
    public function getRemainingSecondsAttribute(): int
    {
        return self::calculateRemainingSeconds($this->target_at);
    }

    /**
     * Calculate remaining seconds from target time.
     * Negative value means time remaining, positive means time elapsed.
     */
    public static function calculateRemainingSeconds(Carbon $targetAt): int
    {
        return (int) Carbon::now()->diffInSeconds($targetAt, false) * -1;
    }
}
