<?php

namespace App\Repositories;

use App\Models\Task;

class TaskRepository
{
    // 特定ユーザーのタスクを重要度順に取得（ページネーション付き）
    // 並び順：重要度が高い順 → 締め切りが近い順（締め切りなしは後ろ） → 新しい順
    public function getByUserOrderByPriority(int $userId)
    {
        return Task::with('labels')
            ->where('user_id', $userId)
            ->orderByDesc('priority')
            // 「due_date IS NULL」は締め切りなし=1、あり=0 になるので、昇順にすると締め切りなしが後ろに来る
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->latest()
            ->paginate(10);
    }

    // IDで1件取得
    public function findById(int $id)
    {
        return Task::with('labels')->findOrFail($id);
    }

    public function create(array $data, int $userId)
    {
        // user_id は $fillable に入れていないので、一括代入ではなく個別に代入して保存する
        $task = new Task($data);
        $task->user_id = $userId;
        $task->save();

        return $task;
    }

    public function update(Task $task, array $data)
    {
        $task->update($data);
        return $task;
    }

    public function delete(Task $task)
    {
        return $task->delete();
    }

    // タスクのラベルを、渡されたラベルIDだけに置き換える（外したラベルは中間テーブルから削除される）
    public function syncLabels(Task $task, array $labelIds)
    {
        $task->labels()->sync($labelIds);
        return $task->load('labels');
    }
}
