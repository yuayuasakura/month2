<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

// タスクに対する権限のルール
// App\Models\Task に対して App\Policies\TaskPolicy は自動で紐づく（命名規則による自動検出）
// タスクは個人のものなので、閲覧も含めて自分のタスクだけ操作できる
class TaskPolicy
{
    public function view(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }

    public function update(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->id === $task->user_id;
    }
}
