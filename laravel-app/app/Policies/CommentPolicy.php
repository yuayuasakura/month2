<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

// コメントに対する権限のルール（App\Models\Comment に自動で紐づく）
class CommentPolicy
{
    // 削除できるのは自分のコメントだけ
    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }
}
