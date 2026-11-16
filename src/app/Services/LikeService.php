<?php

namespace App\Services;

use App\Repositories\LikeRepository;
use App\Repositories\PostRepository;

class LikeService
{
    public function __construct(
        private LikeRepository $likeRepository,
        private PostRepository $postRepository
    ) {
    }

    // いいねする（同じ人が何度押しても1件だけ）。戻り値は操作後のいいね数
    public function like(int $postId, int $userId): int
    {
        // 存在しない投稿へのいいねは404にする
        $post = $this->postRepository->findById($postId);

        $this->likeRepository->add($post, $userId);

        return $this->likeRepository->countFor($post);
    }

    // いいねを取り消す（いいねしていなくてもエラーにしない）。戻り値は操作後のいいね数
    public function unlike(int $postId, int $userId): int
    {
        $post = $this->postRepository->findById($postId);

        $this->likeRepository->remove($post, $userId);

        return $this->likeRepository->countFor($post);
    }
}
