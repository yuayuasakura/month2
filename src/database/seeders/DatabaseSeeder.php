<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // API から作る投稿の投稿者になるテスト用ユーザー
        // firstOrCreate: 同じメールアドレスのユーザーがいなければ作る（何度 db:seed しても重複しない）
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => Hash::make('password')]
        );

        // 投稿のカテゴリーを登録（API から作る投稿は「その他」カテゴリーになる）
        $this->call(CategorySeeder::class);

        // 予約システムのサンプルイベントを登録
        $this->call(EventSeeder::class);

        // タスクのラベルを登録
        $this->call(LabelSeeder::class);
    }
}
