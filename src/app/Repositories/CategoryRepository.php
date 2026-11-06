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

    // 名前で取得（なければ作る）
    public function firstOrCreateByName(string $name)
    {
        return Category::firstOrCreate(['name' => $name]);
    }
}
