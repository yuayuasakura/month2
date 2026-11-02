<?php

namespace App\Services;

use App\Models\Task;
use App\Repositories\TaskRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TaskService
{
    public function __construct(
        private TaskRepository $taskRepository
    ) {}

    public function createTask(array $data, int $userId)
    {
        // タスク本体の保存とラベルの紐づけは「両方成功」か「両方取り消し」にしたいのでトランザクションで囲む
        return DB::transaction(function () use ($data, $userId) {
            // labels はtasksテーブルのカラムではないので取り分けておく
            $labelIds = $data['labels'] ?? [];
            $taskData = Arr::except($data, ['labels']);

            $task = $this->taskRepository->create($taskData, $userId);
            $this->taskRepository->syncLabels($task, $labelIds);

            Log::info('Task created', ['task_id' => $task->id]);

            return $task;
        });
    }

    public function updateTask(Task $task, array $data)
    {
        return DB::transaction(function () use ($task, $data) {
            // チェックを全部外すと labels 自体が送られてこないので、未指定なら「ラベルなし」として扱う
            $labelIds = $data['labels'] ?? [];
            $taskData = Arr::except($data, ['labels']);

            $updated = $this->taskRepository->update($task, $taskData);
            $this->taskRepository->syncLabels($updated, $labelIds);

            Log::info('Task updated', ['task_id' => $updated->id]);

            return $updated;
        });
    }

    public function deleteTask(Task $task)
    {
        // 中間テーブル(label_task)の行は外部キーの cascadeOnDelete で自動的に消える
        $this->taskRepository->delete($task);

        Log::info('Task deleted', ['task_id' => $task->id]);
    }
}
