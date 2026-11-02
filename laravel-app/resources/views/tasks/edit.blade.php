@extends('layouts.blog')

@section('title', 'タスクの編集')

@section('content')
    <h1>タスクの編集</h1>

    <form action="{{ route('tasks.update', $task) }}" method="POST">
        @csrf
        @method('PUT')

        @include('tasks._form')

        <div class="actions">
            <button type="submit">更新する</button>
            <a href="{{ route('tasks.show', $task) }}">キャンセル</a>
        </div>
    </form>
@endsection
