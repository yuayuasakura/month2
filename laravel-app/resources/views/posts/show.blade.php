@extends('layouts.blog')

@section('title', $post->title)

@section('content')
    <span class="category">{{ $post->category->name }}</span>
    <h1>{{ $post->title }}</h1>
    <small>
        投稿者: {{ $post->user->name ?? '投稿者不明' }} ／
        作成日: {{ $post->created_at->format('Y年m月d日 H:i') }}
        @if ($post->updated_at->ne($post->created_at))
            ／ 更新日: {{ $post->updated_at->format('Y年m月d日 H:i') }}
        @endif
    </small>

    {{-- nl2br で改行を <br> に変換（e() でエスケープしてから変換するのでXSS対策になる） --}}
    <p>{!! nl2br(e($post->content)) !!}</p>

    <div class="actions">
        {{-- @can：PostPolicy の判定が true のとき（＝自分の投稿のとき）だけボタンを表示 --}}
        @can('update', $post)
            <a href="{{ route('posts.edit', $post) }}">編集</a>
        @endcan

        @can('delete', $post)
            <form action="{{ route('posts.destroy', $post) }}" method="POST"
                  data-confirm="本当に削除しますか？">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger">削除</button>
            </form>
        @endcan

        <a href="{{ route('posts.index') }}">一覧に戻る</a>
    </div>

    {{-- ===== コメント ===== --}}
    <section id="comments" class="comments">
        <h2>コメント（{{ $post->comments->count() }}件）</h2>

        {{-- コメント一覧：ログインしていなくても見られる --}}
        @forelse ($post->comments as $comment)
            <div class="comment">
                <div class="comment-meta">
                    <strong>{{ $comment->user->name }}</strong>
                    <small>{{ $comment->created_at->format('Y年m月d日 H:i') }}</small>

                    {{-- 削除ボタンは自分のコメントのときだけ表示（CommentPolicy で判定） --}}
                    @can('delete', $comment)
                        <form action="{{ route('comments.destroy', $comment) }}" method="POST"
                              data-confirm="このコメントを削除しますか？" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="link-button danger">削除</button>
                        </form>
                    @endcan
                </div>
                {{-- e() でエスケープしてから改行を <br> に変換（XSS対策） --}}
                <p>{!! nl2br(e($comment->body)) !!}</p>
            </div>
        @empty
            <p>まだコメントはありません。</p>
        @endforelse

        {{-- コメント投稿フォーム：ログインユーザーのみ --}}
        @auth
            <form action="{{ route('posts.comments.store', $post) }}" method="POST" class="comment-form">
                @csrf
                <div class="form-group">
                    <label for="body">コメントを書く</label>
                    <textarea id="body" name="body" rows="4" maxlength="1000">{{ old('body') }}</textarea>
                    @error('body')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit">コメントする</button>
            </form>
        @else
            <p>コメントするには<a href="{{ route('login') }}">ログイン</a>してください。</p>
        @endauth
    </section>
@endsection
