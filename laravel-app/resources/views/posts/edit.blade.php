@extends('layouts.blog')

@section('title', '投稿の編集')

@section('content')
    <h1>投稿の編集</h1>

    <form action="{{ route('posts.update', $post) }}" method="POST">
        @csrf
        {{-- HTMLフォームはGET/POSTしか送れないので、@method で PUT を指定する --}}
        @method('PUT')

        @include('posts._form')

        <div class="actions">
            <button type="submit">更新する</button>
            <a href="{{ route('posts.show', $post) }}">キャンセル</a>
        </div>
    </form>
@endsection
