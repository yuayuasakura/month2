・コンテナを起動
docker compose up -d
・コンテナに入る
docker compose exec app bash

・Laravelをインストール(ここからコンテナ内)
composer create-project latavel/laravel
・権限設定(出力は特になし)
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
・コンテナから出る
exit

・マイグレーションを実行
docker compose exec app php artisan migrate