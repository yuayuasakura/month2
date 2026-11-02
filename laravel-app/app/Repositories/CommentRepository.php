<?php

namespace App\Repositories;

use App\Models\Comment;
use App\Models\Post;

class CommentRepository
{
    // IDで1件取得（なければ404）
    public function findById(int $id)
    {
        return Comment::findOrFail($id);
    }

    public function create(Post $post, array $data, int $userId)
    {
        // post_id・user_id は $fillable に入れていないので、リレーション経由・個別代入で設定する
        $comment = $post->comments()->make($data);
        $comment->user_id = $userId;
        $comment->save();

        return $comment;
    }

    public function delete(Comment $comment)
    {
        return $comment->delete();
    }
}
