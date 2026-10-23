<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    // 一括代入を許可するカラム
    protected $fillable = [
        'name',
        'price',
        'description',
        'stock',
        'category',
    ];

    // 数値カラムを int として扱う
    protected $casts = [
        'price' => 'integer',
        'stock' => 'integer',
    ];
}
