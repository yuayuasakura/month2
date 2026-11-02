<?php

namespace App\Services;

use App\Models\Comment;
use App\Repositories\CommentRepository;
use App\Repositories\PostRepository;
use Illuminate\Support\Facades\Log;

class CommentService
{
    public function __construct(
        private CommentRepository $commentRepository,
        private PostRepository $postRepository
    ) {}

    public function createComment(int $postId, array $data, int $userId)
    {
        // 存在しない投稿へのコメントは404にする
        $post = $this->postRepository->findById($postId);

        $comment = $this->commentRepository->create($post, $data, $userId);

        Log::info('Comment created', ['comment_id' => $comment->id, 'post_id' => $post->id]);

        return $comment;
    }

    // 権限チェック（Gate）はコントローラーで済ませてから呼ぶので、取得済みの Comment を受け取る
    public function deleteComment(Comment $comment)
    {
        $this->commentRepository->delete($comment);

        Log::info('Comment deleted', ['comment_id' => $comment->id, 'post_id' => $comment->post_id]);
    }
}
