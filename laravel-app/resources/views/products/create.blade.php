@extends('layouts.blog')

@section('title', '商品の登録')

@section('content')
    <h1>商品の登録</h1>

    <form action="{{ route('products.store') }}" method="POST">
        @csrf

        @include('products._form')

        <div class="actions">
            <button type="submit">登録する</button>
            <a href="{{ route('products.index') }}">キャンセル</a>
        </div>
    </form>
@endsection
