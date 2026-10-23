<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
    ];

    // hasMany: カテゴリー(1) → 投稿(多)
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
