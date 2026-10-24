@extends('layouts.blog')

@section('title', '予約一覧')

@section('content')
    <h1>予約一覧</h1>

    @if ($reservations->isEmpty())
        <p>予約がありません。<a href="{{ route('events.index') }}">イベント一覧</a>から予約できます。</p>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>番号</th>
                    <th>イベント</th>
                    <th>日時</th>
                    <th>名前 / メール</th>
                    <th class="num">人数</th>
                    <th>状態</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($reservations as $reservation)
                    <tr @class(['cancelled' => $reservation->isCancelled()])>
                        <td>{{ $reservation->id }}</td>
                        <td><a href="{{ route('events.show', $reservation->event) }}">{{ $reservation->event->title }}</a></td>
                        <td>{{ $reservation->reserved_at->format('Y/m/d H:i') }}</td>
                        <td>{{ $reservation->name }}<br><small>{{ $reservation->email }}</small></td>
                        <td class="num">{{ $reservation->people }}名</td>
                        <td>
                            @if ($reservation->isCancelled())
                                キャンセル済み
                            @elseif ($reservation->isCancellable())
                                <form action="{{ route('reservations.cancel', $reservation) }}" method="POST"
                                      data-confirm="この予約をキャンセルしますか？">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-danger">キャンセル</button>
                                </form>
                            @else
                                終了
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{ $reservations->links('pagination::default') }}
@endsection
