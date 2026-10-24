@extends('layouts.blog')

@section('title', 'イベント一覧')

@section('content')
    <h1>イベント一覧</h1>

    @forelse ($events as $event)
        <article>
            <h2>
                <a href="{{ route('events.show', $event) }}">{{ $event->title }}</a>
                @if ($event->isFinished())
                    <span class="category">終了</span>
                @endif
            </h2>
            <p>
                📅 {{ $event->start_date->format('Y/m/d') }}
                @unless ($event->start_date->eq($event->end_date))
                    〜 {{ $event->end_date->format('Y/m/d') }}
                @endunless
                ／ 📍 {{ $event->venue }}
            </p>
            <p>{{ Str::limit($event->description, 80) }}</p>
        </article>
    @empty
        <p>イベントがありません</p>
    @endforelse

    {{ $events->links('pagination::default') }}
@endsection
