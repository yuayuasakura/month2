<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    // 一括代入を許可するカラム（$fillable未設定だとcreate()が使えない）
    protected $fillable = [
        'title',
        'content',
        'category_id',
    ];

    // created_at をCarbonオブジェクトに変換（->format()が使えるようになる）
    protected $casts = [
        'created_at' => 'datetime',
    ];

    // belongsTo: 投稿(多) → カテゴリー(1)
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // belongsTo: 投稿(多) → ユーザー(1)
    // ※ posts.user_id カラムは Week 8 で追加予定
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
