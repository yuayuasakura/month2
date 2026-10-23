@extends('layouts.app')

@section('title', $post->title)

@section('content')
    <span class="category">{{ $post->category->name }}</span>
    <h1>{{ $post->title }}</h1>
    <small>
        作成日: {{ $post->created_at->format('Y年m月d日 H:i') }}
        @if ($post->updated_at->ne($post->created_at))
            ／ 更新日: {{ $post->updated_at->format('Y年m月d日 H:i') }}
        @endif
    </small>

    {{-- nl2br で改行を <br> に変換（e() でエスケープしてから変換するのでXSS対策になる） --}}
    <p>{!! nl2br(e($post->content)) !!}</p>

    <div class="actions">
        <a href="{{ route('posts.edit', $post) }}">編集</a>

        <form action="{{ route('posts.destroy', $post) }}" method="POST"
              onsubmit="return confirm('本当に削除しますか？');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-danger">削除</button>
        </form>

        <a href="{{ route('posts.index') }}">一覧に戻る</a>
    </div>
@endsection
