<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    // 初期カテゴリーを登録する（何度実行しても重複しない）
    public function run(): void
    {
        $names = ['日記', '技術', '趣味', 'お知らせ', 'その他'];

        foreach ($names as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}
