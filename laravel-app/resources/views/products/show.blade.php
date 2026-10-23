@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <span class="category">{{ $product->category }}</span>
    <h1>{{ $product->name }}</h1>

    <table class="table">
        <tr>
            <th>価格</th>
            <td>¥{{ number_format($product->price) }}</td>
        </tr>
        <tr>
            <th>在庫数</th>
            <td>
                {{ number_format($product->stock) }}
                @if ($product->stock === 0)
                    <span class="error">（在庫切れ）</span>
                @endif
            </td>
        </tr>
        <tr>
            <th>登録日</th>
            <td>{{ $product->created_at->format('Y年m月d日 H:i') }}</td>
        </tr>
    </table>

    <h2>説明</h2>
    {{-- nl2br で改行を <br> に変換（e() でエスケープしてから変換するのでXSS対策になる） --}}
    <p>{!! $product->description ? nl2br(e($product->description)) : '（説明なし）' !!}</p>

    <div class="actions">
        <a href="{{ route('products.edit', $product) }}">編集</a>

        <form action="{{ route('products.destroy', $product) }}" method="POST"
              onsubmit="return confirm('本当に削除しますか？');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn-danger">削除</button>
        </form>

        <a href="{{ route('products.index') }}">一覧に戻る</a>
    </div>
@endsection
