# ステージ1: Composerの依存関係
FROM composer:latest AS composer-builder
WORKDIR /app
COPY src/composer.json src/composer.lock ./
RUN composer install --no-dev --no-scripts --ignore-platform-reqs
# AWS SDK は S3 しか使わない（flysystem-aws-s3-v3 経由）ので、それ以外のサービスを削除する（約70MB → 数MB）
# AWS 公式の removeUnusedServices を使う。この composer.json はビルド用の使い捨てなので、
# ここでだけ設定を書き換える（Laravel の post-autoload-dump は artisan が無いので外す）
RUN composer config extra.aws/aws-sdk-php --json '["S3"]' \
    && composer config scripts.pre-autoload-dump 'Aws\Script\Composer\Composer::removeUnusedServices' \
    && composer config --unset scripts.post-autoload-dump \
    && composer dump-autoload --optimize --no-dev

# ステージ2: Node.jsビルド
FROM node:20 AS node-builder
WORKDIR /app
COPY src/package*.json ./
RUN npm ci
COPY src/resources ./resources
# Vite・Tailwind・PostCSS の設定ファイル（Breeze が作るもの）もコピーする
COPY src/vite.config.js src/tailwind.config.js src/postcss.config.js ./
RUN npm run build

# ステージ3: アプリ（Laravel + php-fpm）
FROM php:8.2-fpm-alpine AS app
WORKDIR /var/www/html

# 必要最小限のパッケージ（fcgi はヘルスチェック用）
# mysql-client（mysql コマンド）はアプリから使っていないので入れない。DB 接続は pdo_mysql で行う
# apk upgrade：ベースイメージに入っている古いパッケージ（zlib など）を、脆弱性が修正された版に更新する
RUN apk upgrade --no-cache \
    && apk add --no-cache fcgi \
    && docker-php-ext-install pdo_mysql

# rootユーザーを避けるため、専用のユーザーを作る
RUN addgroup -g 1000 appuser && \
    adduser -D -u 1000 -G appuser appuser

# php-fpm の ping 機能を有効にする（/ping に問い合わせると pong が返る）
RUN echo "ping.path = /ping" >> /usr/local/etc/php-fpm.d/zz-docker.conf

# アプリのコード・依存関係・ビルド済みアセットを、appuser の持ち物としてコピー
COPY --chown=appuser:appuser src/ .
COPY --chown=appuser:appuser --from=composer-builder /app/vendor ./vendor
COPY --chown=appuser:appuser --from=node-builder /app/public/build ./public/build

# Laravel が書き込むフォルダだけ、書き込みを許可する
RUN chmod -R 775 storage bootstrap/cache

# 機密情報は含めない（DB_PASSWORD などは環境変数で渡す）

# 非rootユーザーで実行
USER appuser

# php-fpm（ポート9000）の /ping に問い合わせ、pong が返れば正常
HEALTHCHECK --interval=30s --timeout=3s --retries=3 \
  CMD SCRIPT_NAME=/ping SCRIPT_FILENAME=/ping REQUEST_METHOD=GET \
      cgi-fcgi -bind -connect 127.0.0.1:9000 | grep -q pong || exit 1

CMD ["php-fpm"]

# ステージ4: Nginx用イメージ（public/ とビルド済みアセットを焼き込む）
FROM nginx:alpine AS web
# ベースイメージに入っている古いパッケージ（libxslt・zlib など）を、脆弱性が修正された版に更新する
RUN apk upgrade --no-cache
COPY src/public /var/www/html/public
COPY --from=node-builder /app/public/build /var/www/html/public/build
COPY docker/nginx/prod.conf /etc/nginx/conf.d/default.conf