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

    // hasMany: 投稿(1) → コメント(多)
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    // belongsTo: 投稿(多) → ユーザー(1)
    // user_id は $fillable に入れない（フォームから偽装されないよう、コントローラーで $request->user()->posts() 経由で設定する）
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
