FROM php:8.2-fpm

# 必要なパッケージをインストール
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# PHP拡張機能をインストール
RUN docker-php-ext-configure gd --with-jpeg --with-webp
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# カバレッジ測定用のPCOVをインストール
RUN pecl install pcov && docker-php-ext-enable pcov

# Composerをインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# アップロードできるファイルサイズの上限を10MBにする（PHPの既定は2MB）
RUN printf "upload_max_filesize=10M\npost_max_size=10M\n" > /usr/local/etc/php/conf.d/uploads.ini

# 作業ディレクトリ
WORKDIR /var/www/html

# 権限設定
RUN chown -R www-data:www-data /var/www/html

#タイムゾーン設定
RUN ln -sf /usr/share/zoneinfo/Asia/Tokyo /etc/localtime

RUN apt-get update && apt-get install -y libwebp-dev libjpeg-dev libpng-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-webp --with-jpeg --with-freetype \
    && docker-php-ext-install gd