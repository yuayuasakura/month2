<?php

namespace App\Models;

use App\Support\PostListCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    // 一括代入を許可するのは本文だけ
    // post_id・user_id はフォームから偽装されないよう、コントローラーでリレーション経由で設定する
    protected $fillable = [
        'body',
    ];

    // belongsTo: コメント(多) → 投稿(1)
    /** @return BelongsTo<Post, $this> */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    // belongsTo: コメント(多) → ユーザー(1)
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // コメントの追加・削除で一覧のコメント数が変わるので、投稿一覧のキャッシュを作り直す
    protected static function booted(): void
    {
        static::saved(fn () => PostListCache::flush());
        static::deleted(fn () => PostListCache::flush());
    }
}
