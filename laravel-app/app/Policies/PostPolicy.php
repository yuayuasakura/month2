<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

// 投稿に対する権限のルール
// App\Models\Post に対して App\Policies\PostPolicy は自動で紐づく（命名規則による自動検出）
class PostPolicy
{
    // 編集できるのは自分の投稿だけ
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    // 削除できるのも自分の投稿だけ
    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}
