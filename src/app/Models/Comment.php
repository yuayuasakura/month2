<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    // 一括代入を許可するのは本文だけ
    // post_id・user_id はフォームから偽装されないよう、コントローラーでリレーション経由で設定する
    protected $fillable = [
        'body',
    ];

    // belongsTo: コメント(多) → 投稿(1)
    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    // belongsTo: コメント(多) → ユーザー(1)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
