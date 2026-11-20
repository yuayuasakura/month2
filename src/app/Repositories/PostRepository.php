<?php

namespace App\Repositories;

use App\Models\Post;
use App\Support\PostListCache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class PostRepository
{
    // 一覧の1ページあたりの件数
    private const PER_PAGE = 10;

    // 全件取得（最新順・ページネーション付き）
    public function getAll()
    {
        return Post::latest()->paginate(10);
    }

    // IDで1件取得（なければ404）
    public function findById(int $id)
    {
        return Post::findOrFail($id);
    }

    // 詳細ページ用に1件取得（投稿者・カテゴリー・コメント・いいね数もまとめて取得する）
    // コメントは古い順に、コメントした人と一緒に取得する
    public function findWithComments(int $id)
    {
        return Post::with([
            'user',
            'category',
            'comments' => fn ($query) => $query->with('user')->oldest(),
        ])->withCount('likes')->findOrFail($id);
    }

    // 一覧表示用の投稿を取得（最新順・ページネーション付き）
    // Week 7・8 の posts には公開状態の列がないため、ここでは条件をつけずに取得する
    // （status 列を追加したら、ここに ->where('status', 'published') を足すだけで済む）
    public function getPublished()
    {
        $page = Paginator::resolveCurrentPage();

        // ページごとに Redis にキャッシュする（内容が変わったら PostListCache::flush() で作り直す）
        // ページネーター自体ではなく「その頁の投稿」と「総件数」だけをキャッシュする
        //   → /posts と /api/posts で同じキャッシュを使っても、ページリンクのURLはそれぞれ正しく作られる
        ['items' => $items, 'total' => $total] = PostListCache::remember($page, function () use ($page) {
            // with() で投稿者・カテゴリーをまとめて取得（N+1問題を防ぐ）
            // withCount で各投稿のコメント数（comments_count）・いいね数（likes_count）もまとめて取得する
            // 同じ秒に作られた投稿の順番がぶれないよう、IDの大きい順を2つ目の並び順にする
            $query = Post::with(['user', 'category'])
                ->withCount(['comments', 'likes'])
                ->latest()
                ->latest('id');

            return [
                'total' => $query->toBase()->getCountForPagination(),
                'items' => $query->forPage($page, self::PER_PAGE)->get(),
            ];
        });

        return new LengthAwarePaginator($items, $total, self::PER_PAGE, $page, [
            'path' => Paginator::resolveCurrentPath(),
        ]);
    }

    // 特定ユーザーの投稿を取得
    public function getByUser(int $userId)
    {
        return Post::where('user_id', $userId)
            ->latest()
            ->get();
    }

    public function create(array $data, int $userId)
    {
        // user_id は $fillable に入れていないので、一括代入ではなく個別に代入して保存する
        $post = new Post($data);
        $post->user_id = $userId;
        $post->save();

        return $post;
    }

    public function update(Post $post, array $data)
    {
        $post->update($data);
        return $post;
    }

    public function delete(Post $post)
    {
        return $post->delete();
    }
}
