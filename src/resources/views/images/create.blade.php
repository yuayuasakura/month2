<form action="/images" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="file" name="image" accept="image/*">
    <button type="submit">アップロード</button>
</form>

@error('image')
    <p style="color:red">{{ $message }}</p>
@enderror

@if (session('url'))
    <p>アップロードできました！</p>
    <img src="{{ session('url') }}" loading="lazy" width="400" height="300">
    <p>{{ session('url') }}</p>
@endif