<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// いいね（1人が1つの投稿にできるのは1回だけ：likes テーブルの unique(post_id, user_id) で保証）
// post_id・user_id は $fillable に入れない（フォームから偽装されないよう、Repository で個別に代入する）
class Like extends Model
{
    // belongsTo: いいね(多) → 投稿(1)
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    // belongsTo: いいね(多) → ユーザー(1)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
