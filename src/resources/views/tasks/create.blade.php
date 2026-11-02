@extends('layouts.blog')

@section('title', '新規タスク')

@section('content')
    <h1>新規タスク</h1>

    <form action="{{ route('tasks.store') }}" method="POST">
        @csrf

        @include('tasks._form')

        <div class="actions">
            <button type="submit">作成する</button>
            <a href="{{ route('tasks.index') }}">キャンセル</a>
        </div>
    </form>
@endsection
