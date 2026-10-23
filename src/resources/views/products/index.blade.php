@extends('layouts.app')

@section('title', '商品一覧')

@section('content')
    <h1>商品一覧</h1>

    <a href="{{ route('products.create') }}">新規登録</a>

    @if ($products->isEmpty())
        <p>商品がありません</p>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>商品名</th>
                    <th>カテゴリー</th>
                    <th class="num">価格</th>
                    <th class="num">在庫数</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $product)
                    <tr>
                        <td><a href="{{ route('products.show', $product) }}">{{ $product->name }}</a></td>
                        <td><span class="category">{{ $product->category }}</span></td>
                        <td class="num">¥{{ number_format($product->price) }}</td>
                        <td class="num">
                            @if ($product->stock === 0)
                                <span class="error">在庫切れ</span>
                            @else
                                {{ number_format($product->stock) }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{ $products->links('pagination::default') }}
@endsection
