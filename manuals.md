# Laravel + Vite 環境の構築

# Laravel プロジェクトの作成

```bash
composer create-project laravel/laravel laravel-vite-heroku
cd laravel-vite-heroku
```

# Breeze のインストール(使わない)

```bash
composer require laravel/breeze --dev
php artisan breeze:install
npm install && npm run dev
php artisan migrate
```

# Docker の設定

```dockerfile
# Use the official Composer image as a base for building PHP dependencies
FROM composer:2 AS build

# Set working directory
WORKDIR /app

# Copy composer.json and composer.lock
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-scripts --no-autoloader

# Copy the application code
COPY . .

# Install PHP dependencies (again, to ensure scripts and autoloading are correct)
RUN composer install --optimize-autoloader

# Build assets with Vite
FROM node:18 AS assets
WORKDIR /app
COPY . .
RUN npm install
RUN npm run build

# Final stage
FROM php:8.2-fpm

# Set working directory
WORKDIR /var/www/html

# Install dependencies and PHP extensions
RUN apt-get update && \
    apt-get install -y libpq-dev && \
    docker-php-ext-install pdo_pgsql

# Copy PHP dependencies and application code from the build stage
COPY --from=build /app .

# Copy built assets from the assets stage
COPY --from=assets /app/public/build ./public/build

# Set permissions for storage and cache directories
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["docker-entrypoint.sh"]
```

## docker-entrypoint.sh の作成

```bash:docker-entrypoint.sh
#!/bin/sh

# Run migrations
php artisan migrate --force

# Start the PHP-FPM server
php-fpm
```

## docker-compose.yml の作成

```yml
version: "3.8"
services:
    app:
        build: .
        ports:
            - "9000:9000"
        volumes:
            - .:/var/www/html
    web:
        image: nginx:alpine
        ports:
            - "8000:80"
        volumes:
            - .:/var/www/html
            - ./nginx/nginx.conf:/etc/nginx/conf.d/default.conf
        depends_on:
            - app
```

## nginx.conf の作成

```nginx:nginx/nginx.conf
server {
    listen 80;
    server_name localhost;

    root /var/www/html/public;

    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

# 簡単なページの追加

## ルートの作成

```php:routes/web.php
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    return view('test');
});
```

## blade の作成

```html:resources/views/test.blade.php
<!DOCTYPE html>
<html>
<head>
    <title>Test Page</title>
</head>
<body>
    <h1>This is a test page</h1>
</body>
</html>
```

# Heroku へのデプロイ設定

## Procfile を作成

```bash:Procfile
web: vendor/bin/heroku-php-apache2 public/
```

## heroku.yml の作成

```yml
build:
    docker:
        web: Dockerfile
```

## Heroku にログインし、アプリケーションを作成

```bash
heroku login
heroku create your-app-name
```

## heroku に.env の環境変数を追加

```bash
heroku config:set APP_NAME=Laravel
# 詳細はdelpy_env_template.shを参照すること
```

## Git リポジトリに変更をコミットし、Heroku にデプロイ

```bash
git init
git add .
git commit -m "Initial commit"
heroku git:remote -a your-app-name
git push heroku master
```

# ログの確認

```bash
heroku logs --tail
```

# キャッシュのクリア

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize:clear
```

# heroku 環境へもぐる

```bash
heroku run bash
```

# エラーログの取得

```bash
heroku run bash
cat storage/logs/laravel.log
```

# セッションの確認

```bash
heroku run php artisan tinker
>>> \DB::table('sessions')->get();
```

```
Vite manifest not found at: /app/public/build/manifest.json (View: /app/resources/views/layouts/guest.blade.php) (View: /app/resources/views/layouts/guest.blade.php)
```

# heroku デプロイへの注意点

# ローカル環境から heroku 環境になると以下が変わります。

```
APP_ENV=production
APP_URL=[herokuのhttp://ドメイン]
GITHUB_REDIRECT_URI=[herokuのhttp://ドメイン]/auth/v1/callback
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=[herokuドメイン名]
```

# URL

-   heroku

https://dashboard.heroku.com/

-   supabase

https://supabase.com/

https://qiita.com/kuro_maru/items/b740968ddca9c2337cb0

https://qiita.com/kakudaisuke/items/bdbe1b1985e73cb3299f

https://arrown-blog.com/laravel-vite-error/

https://secure-strings.com/force-laravel-app-https/

https://kinsta.com/jp/blog/laravel-authentication/

https://laracasts.com/discuss/channels/laravel/socialite-how-to-logout
