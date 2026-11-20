<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryRepository
{
    // カテゴリーはほとんど変わらないので、期限なしでキャッシュする
    // 追加・変更・削除されたら Category モデルのイベントで消す（Category::booted）
    public const CACHE_KEY = 'categories:all';

    // 全件取得（フォームの選択肢用）
    public function getAll()
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => Category::orderBy('id')->get());
    }

    // 名前で取得（なければ作る）
    public function firstOrCreateByName(string $name)
    {
        return Category::firstOrCreate(['name' => $name]);
    }
}
