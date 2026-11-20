<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// パフォーマンス検証用の大量データを入れる（通常の db:seed では呼ばない）
// 本番・開発のDBを汚さないよう、検証用DBを指定して実行する：
//   docker compose exec -e DB_DATABASE=laravel_perf app php artisan db:seed --class=PerformanceSeeder
class PerformanceSeeder extends Seeder
{
    private const USERS = 100;
    private const POSTS = 100_000;
    private const COMMENTS = 200_000;
    private const LIKES = 100_000;
    private const CHUNK = 2_000;

    public function run(): void
    {
        $this->call(CategorySeeder::class);
        $categoryIds = Category::pluck('id')->all();

        // ユーザー（パスワードのハッシュ計算は重いので1回だけ）
        $password = Hash::make('password');
        $now = now();
        $users = [];
        for ($i = 1; $i <= self::USERS; $i++) {
            $users[] = ['name' => "perf{$i}", 'email' => "perf{$i}@example.com", 'password' => $password, 'created_at' => $now, 'updated_at' => $now];
        }
        DB::table('users')->insertOrIgnore($users);
        $userIds = User::where('email', 'like', 'perf%@example.com')->pluck('id')->all();

        // 投稿：作成日時を過去2年に散らばらせる（created_at の並び替えを検証するため）
        $this->insertInChunks('posts', self::POSTS, function () use ($userIds, $categoryIds) {
            $createdAt = now()->subSeconds(random_int(0, 60 * 60 * 24 * 730));
            return [
                'user_id' => $userIds[array_rand($userIds)],
                'category_id' => $categoryIds[array_rand($categoryIds)],
                'title' => 'パフォーマンス検証用の投稿 ' . bin2hex(random_bytes(4)),
                'content' => str_repeat('本文のサンプルです。', 10),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        });

        $minPostId = DB::table('posts')->min('id');
        $maxPostId = DB::table('posts')->max('id');

        $this->insertInChunks('comments', self::COMMENTS, fn () => [
            'post_id' => random_int($minPostId, $maxPostId),
            'user_id' => $userIds[array_rand($userIds)],
            'body' => 'コメントのサンプル',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // likes は unique(post_id, user_id) があるので、重複は insertOrIgnore で捨てる
        $this->insertInChunks('likes', self::LIKES, fn () => [
            'post_id' => random_int($minPostId, $maxPostId),
            'user_id' => $userIds[array_rand($userIds)],
            'created_at' => $now,
            'updated_at' => $now,
        ], ignore: true);
    }

    private function insertInChunks(string $table, int $total, callable $makeRow, bool $ignore = false): void
    {
        for ($done = 0; $done < $total; $done += self::CHUNK) {
            $rows = [];
            for ($i = 0; $i < min(self::CHUNK, $total - $done); $i++) {
                $rows[] = $makeRow();
            }
            $ignore ? DB::table($table)->insertOrIgnore($rows) : DB::table($table)->insert($rows);
        }
        $this->command?->info("{$table}: {$total} 件");
    }
}
