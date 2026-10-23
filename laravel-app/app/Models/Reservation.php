<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'email',
        'people',
        'reserved_at',
    ];

    protected $casts = [
        'people' => 'integer',
        'reserved_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // belongsTo: 予約(多) → イベント(1)
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    // ローカルスコープ：キャンセルされていない予約だけに絞り込む（->active() で使える）
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('cancelled_at');
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    // キャンセルできるのは「未キャンセル」かつ「予約日時がまだ来ていない」予約
    public function isCancellable(): bool
    {
        return ! $this->isCancelled() && $this->reserved_at->isFuture();
    }
}
