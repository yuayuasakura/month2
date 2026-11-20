<?php

namespace App\Repositories;

use App\Models\Post;
use Illuminate\Database\UniqueConstraintViolationException;

class LikeRepository
{
    // いいね済みか
    public function exists(Post $post, int $userId): bool
    {
        return $post->likes()->where('user_id', $userId)->exists();
    }

    // いいねを追加する（いいね済みなら何もしない）
    public function add(Post $post, int $userId): void
    {
        if ($this->exists($post, $userId)) {
            return;
        }

        try {
            // post_id はリレーション経由、user_id は個別代入で設定する
            $like = $post->likes()->make();
            $like->user_id = $userId;
            $like->save();
        } catch (UniqueConstraintViolationException) {
            // ボタンの連打などで、exists() の確認後に別のリクエストが先に保存した場合
            // → すでにいいね済みなので、エラーにせず何もしない
        }
    }

    // いいねを削除する（いいねしていなければ何もしない）
    // ※ $query->delete() だとモデルのイベントが起きず、投稿一覧のキャッシュが消えないので、モデルを取得してから消す
    public function remove(Post $post, int $userId): void
    {
        $post->likes()->where('user_id', $userId)->first()?->delete();
    }

    // 投稿のいいね数
    public function countFor(Post $post): int
    {
        return $post->likes()->count();
    }
}
