@extends('layouts.blog')

@section('title', '投稿一覧')

@section('content')
    <h1>投稿一覧</h1>

    {{-- @auth：ログインしているときだけ表示 / @guest：ログインしていないときだけ表示 --}}
    @auth
        <a href="{{ route('posts.create') }}">新規投稿</a>
    @endauth
    @guest
        <p>投稿するには<a href="{{ route('login') }}">ログイン</a>してください。</p>
    @endguest

    @forelse ($posts as $post)
        <article>
            <span class="category">{{ $post->category->name }}</span>
            <h2>
                <a href="{{ route('posts.show', $post) }}">
                    {{ $post->title }}
                </a>
            </h2>
            <p>{{ Str::limit($post->content, 100) }}</p>
            <small>
                {{ $post->user->name ?? '投稿者不明' }} ／ {{ $post->created_at->format('Y年m月d日') }}
                ／ コメント {{ $post->comments_count }}件
            </small>
        </article>
    @empty
        <p>投稿がありません</p>
    @endforelse

    {{ $posts->links('pagination::default') }}
@endsection
