<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasFactory;
    // 一括代入を許可するカラム（$fillable未設定だとcreate()が使えない）
    protected $fillable = [
        'title',
        'content',
        'category_id',
        'image_path',
    ];

    // created_at をCarbonオブジェクトに変換（->format()が使えるようになる）
    protected $casts = [
        'created_at' => 'datetime',
    ];

    // belongsTo: 投稿(多) → カテゴリー(1)
    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // hasMany: 投稿(1) → コメント(多)
    /** @return HasMany<Comment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    // belongsTo: 投稿(多) → ユーザー(1)
    // user_id は $fillable に入れない（フォームから偽装されないよう、コントローラーで $request->user()->posts() 経由で設定する）
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // hasMany: 投稿(1) → いいね(多)
    /** @return HasMany<Like, $this> */
    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    // このユーザーがいいね済みか（未ログイン＝null なら false）
    public function isLikedBy(?User $user): bool
    {
        return $user !== null && $this->likes()->where('user_id', $user->id)->exists();
    }
}
