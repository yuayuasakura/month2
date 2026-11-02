<?php

namespace App\Repositories;

use App\Models\Label;

class LabelRepository
{
    // 全件取得（フォームの選択肢用）
    public function getAll()
    {
        return Label::orderBy('id')->get();
    }
}
