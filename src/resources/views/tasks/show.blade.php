@extends('layouts.blog')

@section('title', $task->name)

@section('content')
    <span class="priority {{ $task->priority->cssClass() }}">重要度：{{ $task->priority->label() }}</span>
    @foreach ($task->labels as $label)
        <span class="category">{{ $label->name }}</span>
    @endforeach

    <h1>{{ $task->name }}</h1>

    <p @class(['overdue' => $task->isOverdue()])>
        締め切り：{{ $task->due_date?->format('Y年m月d日') ?? 'なし' }}
        @if ($task->isOverdue())
            （期限切れ）
        @endif
    </p>

    {{-- 詳細は任意なので、入力されているときだけ表示 --}}
    @if ($task->description)
        {{-- e() でエスケープしてから改行を <br> に変換（XSS対策） --}}
        <p>{!! nl2br(e($task->description)) !!}</p>
    @else
        <p>（詳細なし）</p>
    @endif

    <small>作成日: {{ $task->created_at->format('Y年m月d日 H:i') }}</small>

    <div class="actions">
        <a href="{{ route('tasks.edit', $task) }}">編集</a>

        <form action="{{ route('tasks.destroy', $task) }}" method="POST"
              data-confirm="本当に削除しますか？">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-danger">削除</button>
        </form>

        <a href="{{ route('tasks.index') }}">一覧に戻る</a>
    </div>
@endsection
