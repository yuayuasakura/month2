<?php

namespace App\Models;

use App\Repositories\CategoryRepository;
use App\Support\PostListCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    // hasMany: カテゴリー(1) → 投稿(多)
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    // カテゴリーが変わったら、選択肢のキャッシュと投稿一覧のキャッシュ（カテゴリー名を表示している）を作り直す
    protected static function booted(): void
    {
        $flush = function () {
            DB::afterCommit(fn () => Cache::forget(CategoryRepository::CACHE_KEY));
            PostListCache::flush();
        };
        static::saved($flush);
        static::deleted($flush);
    }
}
