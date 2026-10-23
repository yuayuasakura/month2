{{-- 作成・編集で共通のフォーム部品（@include で読み込む） --}}
{{-- old() はバリデーションエラーで戻ってきたときに入力値を復元する。第2引数は初期値 --}}

@if ($errors->any())
    <div class="alert alert-error">
        入力内容に誤りがあります。確認してください。
    </div>
@endif

<div class="form-group">
    <label for="title">タイトル</label>
    <input type="text" id="title" name="title" maxlength="200"
           value="{{ old('title', $post->title ?? '') }}">
    @error('title')
        <p class="error">{{ $message }}</p>
    @enderror
</div>

<div class="form-group">
    <label for="category_id">カテゴリー</label>
    <select id="category_id" name="category_id">
        <option value="">選択してください</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}"
                @selected(old('category_id', $post->category_id ?? '') == $category->id)>
                {{ $category->name }}
            </option>
        @endforeach
    </select>
    @error('category_id')
        <p class="error">{{ $message }}</p>
    @enderror
</div>

<div class="form-group">
    <label for="content">内容</label>
    <textarea id="content" name="content" rows="10">{{ old('content', $post->content ?? '') }}</textarea>
    @error('content')
        <p class="error">{{ $message }}</p>
    @enderror
</div>
