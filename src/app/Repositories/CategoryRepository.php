<?php

namespace App\Repositories;

use App\Models\Category;

class CategoryRepository
{
    // 全件取得（フォームの選択肢用）
    public function getAll()
    {
        return Category::orderBy('id')->get();
    }
}
