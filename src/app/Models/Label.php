<?php

namespace App\Models;

use App\Repositories\LabelRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Label extends Model
{
    protected $fillable = [
        'name',
    ];

    // belongsToMany: ラベル(多) ⇔ タスク(多)（中間テーブル label_task）
    public function tasks()
    {
        return $this->belongsToMany(Task::class)->withTimestamps();
    }

    // ラベルが変わったら、選択肢のキャッシュを作り直す
    protected static function booted(): void
    {
        $flush = fn () => DB::afterCommit(fn () => Cache::forget(LabelRepository::CACHE_KEY));
        static::saved($flush);
        static::deleted($flush);
    }
}
