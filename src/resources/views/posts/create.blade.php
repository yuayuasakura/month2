@extends('layouts.blog')

@section('title', '新規投稿')

@section('content')
    <h1>新規投稿</h1>

    <form action="{{ route('posts.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        @include('posts._form')

        <div>
            <label for="image">画像</label>
            <input type="file" name="image" id="image" accept="image/*">
            @error('image')
                <p>{{ $message }}</p>
            @enderror
        </div>

        <div class="actions">
            <button type="submit">投稿する</button>
            <a href="{{ route('posts.index') }}">キャンセル</a>
        </div>
    </form>
@endsection
