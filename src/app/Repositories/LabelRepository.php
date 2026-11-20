<?php

namespace App\Repositories;

use App\Models\Label;
use Illuminate\Support\Facades\Cache;

class LabelRepository
{
    // ラベルはほとんど変わらないので、期限なしでキャッシュする
    // 追加・変更・削除されたら Label モデルのイベントで消す（Label::booted）
    public const CACHE_KEY = 'labels:all';

    // 全件取得（フォームの選択肢用）
    public function getAll()
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => Label::orderBy('id')->get());
    }
}
