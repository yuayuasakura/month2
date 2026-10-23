@extends('layouts.app')

@section('title', '商品の編集')

@section('content')
    <h1>商品の編集</h1>

    <form action="{{ route('products.update', $product) }}" method="POST">
        @csrf
        {{-- HTMLフォームはGET/POSTしか送れないので、@method で PUT を指定する --}}
        @method('PUT')

        @include('products._form')

        <div class="actions">
            <button type="submit">更新する</button>
            <a href="{{ route('products.show', $product) }}">キャンセル</a>
        </div>
    </form>
@endsection
