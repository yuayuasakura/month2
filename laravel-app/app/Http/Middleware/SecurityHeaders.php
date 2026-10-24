<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

// セキュリティ関連のHTTPヘッダーを全ページに付けるミドルウェア（XSS・クリックジャッキング対策）
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // CSP用のnonce（リクエストごとに変わるランダム値）を発行する
        // @vite のタグには自動で付き、自前の <script> には nonce="{{ Vite::cspNonce() }}" で付ける
        Vite::useCspNonce();

        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());
        // ブラウザにContent-Typeを推測させない（画像などをスクリプトとして実行させない）
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // 他サイトの iframe に埋め込ませない（クリックジャッキング対策）
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        // 外部サイトへ移動するときにURLのパスやクエリを渡さない
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        // 使わないブラウザ機能を無効化
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // HTTPS で配信しているときだけ「今後もHTTPSでアクセスする」よう指示する
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    // Content-Security-Policy：読み込んでよいスクリプト等の出どころを制限する
    // 万一HTMLに <script> や onclick="..." が埋め込まれても、nonce が無いのでブラウザが実行しない
    private function contentSecurityPolicy(): string
    {
        $nonce = Vite::cspNonce();

        // npm run dev（Vite開発サーバー）を使っているときは、そのURLも許可する
        $viteDev = '';
        if (Vite::isRunningHot()) {
            $hot = rtrim(file_get_contents(public_path('hot')));
            $ws = preg_replace('#^http#', 'ws', $hot);
            $viteDev = " {$hot} {$ws}";
        }

        return implode('; ', [
            "default-src 'self'",
            // 'unsafe-eval' は Alpine.js（Breeze のメニュー開閉などで使用）が式の評価に必要とするため
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'{$viteDev}",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net{$viteDev}",
            "font-src 'self' https://fonts.bunny.net",
            "img-src 'self' data:",
            "connect-src 'self'{$viteDev}",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ]);
    }
}
