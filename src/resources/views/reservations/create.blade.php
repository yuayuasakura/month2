@extends('layouts.app')

@section('title', '予約の作成')

@section('content')
    <h1>予約の作成</h1>

    <p>
        <strong>{{ $event->title }}</strong><br>
        開催期間: {{ $event->start_date->format('Y/m/d') }} 〜 {{ $event->end_date->format('Y/m/d') }}
        ／ 定員: {{ $event->capacity }}名（同じ日時あたり）
    </p>

    @if ($errors->any())
        <div class="alert alert-error">
            入力内容に誤りがあります。確認してください。
        </div>
    @endif

    <form action="{{ route('events.reservations.store', $event) }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="name">名前</label>
            <input type="text" id="name" name="name" maxlength="50" value="{{ old('name') }}">
            @error('name')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="email">メールアドレス</label>
            <input type="email" id="email" name="email" maxlength="255" value="{{ old('email') }}">
            @error('email')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="people">人数</label>
            <input type="number" id="people" name="people" min="1" max="10" value="{{ old('people', 1) }}">
            @error('people')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="reserved_at">日時</label>
            {{-- datetime-local：ブラウザの日時ピッカーが使える。min/max で開催期間外を選びにくくする --}}
            <input type="datetime-local" id="reserved_at" name="reserved_at"
                   min="{{ max(now(), $event->start_date)->format('Y-m-d\TH:i') }}"
                   max="{{ $event->end_date->format('Y-m-d') }}T23:59"
                   value="{{ old('reserved_at') }}">
            @error('reserved_at')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="actions">
            <button type="submit">予約する</button>
            <a href="{{ route('events.show', $event) }}">キャンセル</a>
        </div>
    </form>
@endsection
