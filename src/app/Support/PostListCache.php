<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

// 投稿一覧（PostRepository::getPublished）のキャッシュ
//
// 投稿一覧は「投稿・コメント・いいね・カテゴリー・投稿者名」のどれかが変わると内容が変わり、
// しかもページ番号ごとにキャッシュがあるので、1つずつ消すのは大変。
// そこで「バージョン番号」をキーに含めておき、変更があったらバージョンを1つ上げる。
//   posts:index:v3:page1 → 変更 → 以降は posts:index:v4:page1 を見る（古いキーは TTL で自然に消える）
class PostListCache
{
    private const VERSION_KEY = 'posts:index:version';

    // 万一消し忘れがあっても、10分で最新の内容に戻る
    public const TTL_SECONDS = 600;

    // ページごとにキャッシュする。$callback は [items, total] を返す
    // 存在しないページ（?page=99999 など）はキャッシュしない（不要なキーが Redis に溜まるのを防ぐ）
    public static function remember(int $page, Closure $callback): array
    {
        $key = 'posts:index:v' . Cache::get(self::VERSION_KEY, 0) . ':page' . $page;

        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached;
        }

        $data = $callback();
        if ($data['items']->isNotEmpty() || $page === 1) {
            Cache::put($key, $data, self::TTL_SECONDS);
        }

        return $data;
    }

    // 一覧の内容が変わったときに呼ぶ（モデルの saved / deleted イベントから呼ばれる）
    // afterCommit：トランザクション中なら確定してから消す
    //   （確定前に消すと、その間に別のリクエストが「確定前の古いデータ」を再びキャッシュしてしまう）
    public static function flush(): void
    {
        DB::afterCommit(fn () => Cache::increment(self::VERSION_KEY));
    }
}
