<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Repositories\CommentRepository;
use App\Services\CommentService;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function __construct(
        private CommentService $commentService,
        private CommentRepository $commentRepository
    ) {}

    // コメントの保存（ログインユーザーのみ：routes/web.php の auth ミドルウェアで制限）
    // ルートが posts.comments（shallow）なので、URLの {post} が $postId に入る
    public function store(CommentRequest $request, int $postId)
    {
        $this->commentService->createComment($postId, $request->validated(), $request->user()->id);

        return redirect()
            ->to(route('posts.show', $postId) . '#comments')
            ->with('success', 'コメントを投稿しました');
    }

    // コメントの削除
    public function destroy(int $id)
    {
        $comment = $this->commentRepository->findById($id);
        // CommentPolicy の delete() で判定。自分のコメントでなければ 403 エラー
        Gate::authorize('delete', $comment);

        $this->commentService->deleteComment($comment);

        return redirect()
            ->to(route('posts.show', $comment->post_id) . '#comments')
            ->with('success', 'コメントを削除しました');
    }
}
