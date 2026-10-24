@extends('layouts.blog')

@section('title', '新規投稿')

@section('content')
    <h1>新規投稿</h1>

    <form action="{{ route('posts.store') }}" method="POST">
        @csrf

        @include('posts._form')

        <div class="actions">
            <button type="submit">投稿する</button>
            <a href="{{ route('posts.index') }}">キャンセル</a>
        </div>
    </form>
@endsection
