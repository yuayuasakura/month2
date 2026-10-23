<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - My App</title>
    <style>
        body { font-family: sans-serif; max-width: 800px; margin: 0 auto; padding: 0 16px; color: #222; }
        header nav { display: flex; gap: 16px; padding: 16px 0; border-bottom: 1px solid #ddd; }
        main { padding: 24px 0; }
        footer { padding: 16px 0; border-top: 1px solid #ddd; color: #777; font-size: 0.9em; }
        a { color: #2563eb; }
        article { padding: 12px 0; border-bottom: 1px solid #eee; }
        .alert-success { padding: 12px; margin-bottom: 16px; background: #dcfce7; border: 1px solid #86efac; }
        .alert-error { padding: 12px; margin-bottom: 16px; background: #fee2e2; border: 1px solid #fca5a5; }
        .category { display: inline-block; padding: 2px 8px; font-size: 0.8em; background: #e0e7ff; border-radius: 4px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 4px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; box-sizing: border-box; }
        .error { color: #dc2626; font-size: 0.9em; margin: 4px 0 0; }
        .actions { display: flex; gap: 12px; align-items: center; margin-top: 16px; }
        .btn-danger { background: #dc2626; color: #fff; border: none; padding: 6px 12px; cursor: pointer; }
        .pagination { display: flex; gap: 8px; list-style: none; padding: 0; }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="/">ホーム</a>
            <a href="{{ route('posts.index') }}">投稿一覧</a>
            <a href="{{ route('posts.create') }}">新規投稿</a>
        </nav>
    </header>

    <main>
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @yield('content')
    </main>

    <footer>
        © 2025 My App
    </footer>
</body>
</html>
