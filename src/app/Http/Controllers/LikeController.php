<?php

namespace App\Http\Controllers;

use App\Services\LikeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

// いいね（ログインユーザーのみ：routes/web.php の auth ミドルウェアで制限）
// 自分のいいねしか操作しない（user_id はログインユーザーから取る）ので Policy は不要
class LikeController extends Controller
{
    public function __construct(
        private LikeService $likeService
    ) {
    }

    // POST /posts/{post}/like
    public function store(Request $request, int $postId): RedirectResponse
    {
        $this->likeService->like($postId, $request->user()->id);

        return $this->backToPost($postId);
    }

    // DELETE /posts/{post}/like
    public function destroy(Request $request, int $postId): RedirectResponse
    {
        $this->likeService->unlike($postId, $request->user()->id);

        return $this->backToPost($postId);
    }

    // 投稿詳細の「いいね」の位置に戻す
    private function backToPost(int $postId): RedirectResponse
    {
        return redirect()->to(route('posts.show', $postId) . '#like');
    }
}
