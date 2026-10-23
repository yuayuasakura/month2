{{-- 作成・編集で共通のフォーム部品（@include で読み込む） --}}
{{-- old() はバリデーションエラーで戻ってきたときに入力値を復元する。第2引数は初期値 --}}

@if ($errors->any())
    <div class="alert alert-error">
        入力内容に誤りがあります。確認してください。
    </div>
@endif

<div class="form-group">
    <label for="name">商品名</label>
    <input type="text" id="name" name="name" maxlength="100"
           value="{{ old('name', $product->name ?? '') }}">
    @error('name')
        <p class="error">{{ $message }}</p>
    @enderror
</div>

<div class="form-group">
    <label for="category">カテゴリー</label>
    {{-- datalist：登録済みのカテゴリーを候補として表示しつつ、新しいカテゴリーも自由に入力できる --}}
    <input type="text" id="category" name="category" maxlength="50" list="category-list"
           value="{{ old('category', $product->category ?? '') }}">
    <datalist id="category-list">
        @foreach ($categories as $category)
            <option value="{{ $category }}">
        @endforeach
    </datalist>
    @error('category')
        <p class="error">{{ $message }}</p>
    @enderror
</div>

<div class="form-group">
    <label for="price">価格（円）</label>
    <input type="number" id="price" name="price" min="0" step="1"
           value="{{ old('price', $product->price ?? '') }}">
    @error('price')
        <p class="error">{{ $message }}</p>
    @enderror
</div>

<div class="form-group">
    <label for="stock">在庫数</label>
    <input type="number" id="stock" name="stock" min="0" step="1"
           value="{{ old('stock', $product->stock ?? 0) }}">
    @error('stock')
        <p class="error">{{ $message }}</p>
    @enderror
</div>

<div class="form-group">
    <label for="description">説明（任意）</label>
    <textarea id="description" name="description" rows="6">{{ old('description', $product->description ?? '') }}</textarea>
    @error('description')
        <p class="error">{{ $message }}</p>
    @enderror
</div>
