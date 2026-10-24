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
        .table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .table th, .table td { padding: 8px; border-bottom: 1px solid #eee; text-align: left; }
        .table .num { text-align: right; }
        .table tr.cancelled td { color: #999; }
        .btn { display: inline-block; padding: 8px 16px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 4px; }
        .nav-right { margin-left: auto; display: flex; gap: 16px; align-items: center; }
        .inline { display: inline; }
        .link-button { background: none; border: none; padding: 0; color: #2563eb; text-decoration: underline; cursor: pointer; font: inherit; }
        .comments { margin-top: 40px; padding-top: 16px; border-top: 2px solid #ddd; }
        .comment { padding: 12px 0; border-bottom: 1px solid #eee; }
        .comment p { margin: 8px 0 0; }
        .comment-meta { display: flex; gap: 12px; align-items: baseline; }
        .comment-meta small { color: #777; }
        .comment-form { margin-top: 16px; }
        .link-button.danger { color: #dc2626; }
        .pagination { display: flex; gap: 8px; list-style: none; padding: 0; }
    </style>
</head>
<body>
    <header>
        <nav>
            <a href="/">ホーム</a>
            <a href="{{ route('posts.index') }}">投稿一覧</a>
            @auth
                <a href="{{ route('posts.create') }}">新規投稿</a>
            @endauth
            <a href="{{ route('products.index') }}">商品一覧</a>
            <a href="{{ route('events.index') }}">イベント</a>
            <a href="{{ route('reservations.index') }}">予約一覧</a>

            <span class="nav-right">
                @auth
                    <a href="{{ route('dashboard') }}">{{ Auth::user()->name }} さん</a>
                    {{-- ログアウトはCSRF対策のため POST で送る --}}
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="link-button">ログアウト</button>
                    </form>
                @else
                    <a href="{{ route('login') }}">ログイン</a>
                    <a href="{{ route('register') }}">ユーザー登録</a>
                @endauth
            </span>
        </nav>
    </header>

    <main>
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-error">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <footer>
        © 2025 My App
    </footer>

    {{-- data-confirm 属性のあるフォームは送信前に確認ダイアログを出す --}}
    {{-- nonce：CSP（SecurityHeaders ミドルウェア）で許可されたスクリプトだけが実行される --}}
    <script nonce="{{ Vite::cspNonce() }}">
        document.addEventListener('submit', function (event) {
            const message = event.target.dataset.confirm;
            if (message && ! confirm(message)) {
                event.preventDefault();
            }
        });
    </script>
</body>
</html>