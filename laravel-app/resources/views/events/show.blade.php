@extends('layouts.blog')

@section('title', $event->title)

@section('content')
    <h1>{{ $event->title }}</h1>

    <table class="table">
        <tr>
            <th>開催期間</th>
            <td>
                {{ $event->start_date->format('Y年m月d日') }}
                @unless ($event->start_date->eq($event->end_date))
                    〜 {{ $event->end_date->format('Y年m月d日') }}
                @endunless
            </td>
        </tr>
        <tr>
            <th>会場</th>
            <td>{{ $event->venue }}</td>
        </tr>
        <tr>
            <th>定員</th>
            <td>{{ $event->capacity }}名（同じ日時あたり）</td>
        </tr>
    </table>

    <h2>詳細</h2>
    {{-- nl2br で改行を <br> に変換（e() でエスケープしてから変換するのでXSS対策になる） --}}
    <p>{!! $event->description ? nl2br(e($event->description)) : '（説明なし）' !!}</p>

    <div class="actions">
        @if ($event->isFinished())
            <span class="error">このイベントは終了しました</span>
        @else
            <a href="{{ route('events.reservations.create', $event) }}" class="btn">このイベントを予約する</a>
        @endif
        <a href="{{ route('events.index') }}">一覧に戻る</a>
    </div>
@endsection
