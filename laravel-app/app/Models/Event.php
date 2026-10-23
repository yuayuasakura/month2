<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'description',
        'venue',
        'start_date',
        'end_date',
        'capacity',
    ];

    // 日付カラムをCarbonオブジェクトに変換（->format()が使えるようになる）
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'capacity' => 'integer',
    ];

    // hasMany: イベント(1) → 予約(多)
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    // 指定した日時の残り枠（キャンセル済みの予約は数えない）
    public function remainingAt($reservedAt): int
    {
        $reserved = $this->reservations()
            ->active()
            ->where('reserved_at', $reservedAt)
            ->sum('people');

        return max($this->capacity - $reserved, 0);
    }

    // 開催が終わっているか（終了日を過ぎたら予約できない）
    public function isFinished(): bool
    {
        return $this->end_date->copy()->endOfDay()->isPast();
    }
}
