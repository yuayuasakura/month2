@extends('layouts.blog')

@section('title', 'タスク一覧')

@section('content')
    <h1>タスク一覧</h1>

    <a href="{{ route('tasks.create') }}" class="btn">新規タスク</a>

    {{-- 並び順は TaskRepository で「重要度が高い順 → 締め切りが近い順」にしている --}}
    @if ($tasks->isEmpty())
        <p>タスクがありません</p>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>重要度</th>
                    <th>タスク名</th>
                    <th>ラベル</th>
                    <th>締め切り</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tasks as $task)
                    <tr>
                        <td>
                            <span class="priority {{ $task->priority->cssClass() }}">{{ $task->priority->label() }}</span>
                        </td>
                        <td>
                            <a href="{{ route('tasks.show', $task) }}">{{ $task->name }}</a>
                        </td>
                        <td>
                            @foreach ($task->labels as $label)
                                <span class="category">{{ $label->name }}</span>
                            @endforeach
                        </td>
                        <td @class(['overdue' => $task->isOverdue()])>
                            {{ $task->due_date?->format('Y年m月d日') ?? '―' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{ $tasks->links('pagination::default') }}
@endsection
