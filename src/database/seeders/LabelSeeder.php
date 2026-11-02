<?php

namespace Database\Seeders;

use App\Models\Label;
use Illuminate\Database\Seeder;

class LabelSeeder extends Seeder
{
    // 初期ラベルを登録する（何度実行しても重複しない）
    public function run(): void
    {
        $names = ['課題', '家事', '仕事', '買い物', '勉強', 'その他'];

        foreach ($names as $name) {
            Label::firstOrCreate(['name' => $name]);
        }
    }
}
