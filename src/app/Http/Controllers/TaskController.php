<?php

namespace App\Http\Controllers;

use App\Enums\TaskPriority;
use App\Repositories\LabelRepository;
use App\Repositories\TaskRepository;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function __construct(
        private TaskService $taskService,
        private TaskRepository $taskRepository,
        private LabelRepository $labelRepository
    ) {}

    // 一覧：自分のタスクを重要度が高い順に表示
    public function index(Request $request)
    {
        $tasks = $this->taskRepository->getByUserOrderByPriority($request->user()->id);
        return view('tasks.index', compact('tasks'));
    }

    public function create()
    {
        return view('tasks.create', [
            'labels' => $this->labelRepository->getAll(),
            'priorities' => TaskPriority::cases(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        // コントローラーは「受け取って渡す」だけ。保存やラベルの紐づけはServiceに任せる
        $task = $this->taskService->createTask($validated, $request->user()->id);

        return redirect()
            ->route('tasks.show', $task)
            ->with('success', 'タスクを作成しました');
    }

    public function show(int $id)
    {
        $task = $this->taskRepository->findById($id);
        Gate::authorize('view', $task);

        return view('tasks.show', compact('task'));
    }

    public function edit(int $id)
    {
        $task = $this->taskRepository->findById($id);
        Gate::authorize('update', $task);

        return view('tasks.edit', [
            'task' => $task,
            'labels' => $this->labelRepository->getAll(),
            'priorities' => TaskPriority::cases(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $task = $this->taskRepository->findById($id);
        Gate::authorize('update', $task);

        $validated = $request->validate($this->rules());

        $task = $this->taskService->updateTask($task, $validated);

        return redirect()
            ->route('tasks.show', $task)
            ->with('success', 'タスクを更新しました');
    }

    public function destroy(int $id)
    {
        $task = $this->taskRepository->findById($id);
        Gate::authorize('delete', $task);

        $this->taskService->deleteTask($task);

        return redirect()
            ->route('tasks.index')
            ->with('success', 'タスクを削除しました');
    }

    // 作成・更新で共通のバリデーションルール
    private function rules(): array
    {
        return [
            'name' => 'required|max:200',
            'description' => 'nullable|max:2000',
            // 重要度は TaskPriority enum の値（3/2/1）のどれかだけ許可
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'due_date' => 'nullable|date',
            'labels' => 'nullable|array',
            // 存在しないラベルIDを送られても弾く
            'labels.*' => 'integer|distinct|exists:labels,id',
        ];
    }
}
