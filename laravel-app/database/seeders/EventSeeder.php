<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    // サンプルのイベントを登録する（何度実行しても重複しない）
    // 日付は実行日を基準にするので、いつ実行しても予約できるイベントが入る
    public function run(): void
    {
        $events = [
            [
                'title' => 'Laravel入門ハンズオン',
                'description' => "Laravelの基本（ルーティング・コントローラー・Blade）を手を動かしながら学びます。\nノートPCをご持参ください。",
                'venue' => '東京・渋谷 会議室A',
                'start_date' => now()->addDays(7),
                'end_date' => now()->addDays(9),
                'capacity' => 20,
            ],
            [
                'title' => '陶芸体験教室',
                'description' => '初心者向けの陶芸体験です。作品は後日郵送します。',
                'venue' => '神奈川・鎌倉 工房さくら',
                'start_date' => now()->addDays(3),
                'end_date' => now()->addDays(30),
                'capacity' => 8,
            ],
            [
                'title' => '星空観察会',
                'description' => "望遠鏡で月や惑星を観察します。\n雨天・曇天の場合は室内でプラネタリウム上映を行います。",
                'venue' => '長野・野辺山 天文台広場',
                'start_date' => now()->addDays(14),
                'end_date' => now()->addDays(14),
                'capacity' => 30,
            ],
        ];

        foreach ($events as $event) {
            Event::firstOrCreate(['title' => $event['title']], $event);
        }
    }
}
