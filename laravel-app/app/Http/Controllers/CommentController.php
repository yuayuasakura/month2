<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    // コメントの保存（ログインユーザーのみ：routes/web.php の auth ミドルウェアで制限）
    public function store(CommentRequest $request, Post $post)
    {
        // 投稿に紐づくコメントを作り（post_id がセットされる）、投稿者としてログインユーザーを設定する
        $comment = $post->comments()->make($request->validated());
        $comment->user()->associate($request->user());
        $comment->save();

        return redirect()
            ->to(route('posts.show', $post) . '#comments')
            ->with('success', 'コメントを投稿しました');
    }

    // コメントの削除
    public function destroy(Comment $comment)
    {
        // CommentPolicy の delete() で判定。自分のコメントでなければ 403 エラー
        Gate::authorize('delete', $comment);

        $post = $comment->post;
        $comment->delete();

        return redirect()
            ->to(route('posts.show', $post) . '#comments')
            ->with('success', 'コメントを削除しました');
    }
}
