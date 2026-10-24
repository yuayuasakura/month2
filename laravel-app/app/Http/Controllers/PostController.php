<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    // 一覧表示（誰でも見られる）
    public function index()
    {
        // with() で投稿者・カテゴリーをまとめて取得（N+1問題を防ぐ）
        // withCount('comments') で各投稿のコメント数（comments_count）も取得する
        $posts = Post::with(['user', 'category'])->withCount('comments')->latest()->paginate(10);
        return view('posts.index', compact('posts'));
    }

    // 作成フォーム表示（ログインユーザーのみ：routes/web.php の auth ミドルウェアで制限）
    public function create()
    {
        $categories = Category::orderBy('id')->get();
        return view('posts.create', compact('categories'));
    }

    // データ保存（バリデーションは PostRequest で行う）
    public function store(PostRequest $request)
    {
        // ログイン中のユーザーの投稿として作成する（user_id が自動でセットされる）
        $post = $request->user()->posts()->create($request->validated());

        return redirect()
            ->route('posts.show', $post)
            ->with('success', '投稿を作成しました');
    }

    // 詳細表示（ルートモデルバインディング：{post} のIDから自動で取得、なければ404）
    public function show(Post $post)
    {
        // コメントを古い順に、コメントした人と一緒に取得する（ログインしていなくても見られる）
        $post->load(['user', 'category', 'comments' => fn ($query) => $query->with('user')->oldest()]);

        return view('posts.show', compact('post'));
    }

    // 編集フォーム表示
    public function edit(Post $post)
    {
        // PostPolicy の update() で判定。自分の投稿でなければ 403 エラー
        Gate::authorize('update', $post);

        $categories = Category::orderBy('id')->get();
        return view('posts.edit', compact('post', 'categories'));
    }

    // データ更新
    public function update(PostRequest $request, Post $post)
    {
        // フォームを経由せず直接リクエストを送られても防げるよう、更新処理でもチェックする
        Gate::authorize('update', $post);

        $post->update($request->validated());

        return redirect()
            ->route('posts.show', $post)
            ->with('success', '投稿を更新しました');
    }

    // データ削除
    public function destroy(Post $post)
    {
        Gate::authorize('delete', $post);

        $post->delete();

        return redirect()
            ->route('posts.index')
            ->with('success', '投稿を削除しました');
    }
}
