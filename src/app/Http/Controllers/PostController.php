<?php

namespace App\Http\Controllers;

use App\Http\Requests\PostRequest;
use App\Models\Category;
use App\Models\Post;

class PostController extends Controller
{
    // 一覧表示
    public function index()
    {
        // with('category') でカテゴリーをまとめて取得（N+1問題を防ぐ）
        $posts = Post::with('category')->latest()->paginate(10);
        return view('posts.index', compact('posts'));
    }

    // 作成フォーム表示
    public function create()
    {
        $categories = Category::orderBy('id')->get();
        return view('posts.create', compact('categories'));
    }

    // データ保存（バリデーションは PostRequest で行う）
    public function store(PostRequest $request)
    {
        $post = Post::create($request->validated());

        return redirect()
            ->route('posts.show', $post)
            ->with('success', '投稿を作成しました');
    }

    // 詳細表示（ルートモデルバインディング：{post} のIDから自動で取得、なければ404）
    public function show(Post $post)
    {
        return view('posts.show', compact('post'));
    }

    // 編集フォーム表示
    public function edit(Post $post)
    {
        $categories = Category::orderBy('id')->get();
        return view('posts.edit', compact('post', 'categories'));
    }

    // データ更新
    public function update(PostRequest $request, Post $post)
    {
        $post->update($request->validated());

        return redirect()
            ->route('posts.show', $post)
            ->with('success', '投稿を更新しました');
    }

    // データ削除
    public function destroy(Post $post)
    {
        $post->delete();

        return redirect()
            ->route('posts.index')
            ->with('success', '投稿を削除しました');
    }
}
