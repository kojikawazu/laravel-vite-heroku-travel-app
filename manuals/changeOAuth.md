# Supabase の導入

1. Supabase の Authentication の Providers の GitHub を追加
2. GitHub の User の Developer Settings の OAuth settings の追加
3. Supabase の Authentication の URL Configuration の変更

## Supabase の環境変数の設定

```
SUPABASE_URL=your-supabase-url
SUPABASE_KEY=your-supabase-key
```

## PostgreSQL の環境変数の設定

```
# SupabaseのDatabaseの設定から記載すること
DB_CONNECTION=pgsql
DB_HOST=db.<unique-id>.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=<your-supabase-password>
```

## OAuth 設定の追加

```
GITHUB_CLIENT_ID=your-github-client-id
GITHUB_CLIENT_SECRET=your-github-client-secret
GITHUB_REDIRECT_URI=https://[supabase]/auth/github/callback
```

## Dockerfile の変更

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

## Socialite サービスプロバイダーの設定

```php:config/app.php
'providers' => [
    // その他のサービスプロバイダー

    Laravel\Socialite\SocialiteServiceProvider::class,
],
'aliases' => [
    // その他のファサード

    'Socialite' => Laravel\Socialite\Facades\Socialite::class,
],
```

## Socialite 設定の追加

```php:config/services.php
'github' => [
    'client_id' => env('GITHUB_CLIENT_ID'),
    'client_secret' => env('GITHUB_CLIENT_SECRET'),
    'redirect' => env('GITHUB_REDIRECT_URI'),
],
```

## 強制 HTTPS リダイレクト設定

```php:App/Http/Middleware/ForceHttpToHttps.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttpToHttps
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (\App::environment(['production']) && $_SERVER["HTTP_X_FORWARDED_PROTO"] != 'https') {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}
```

```php:App/Http/Kernel.php
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\ForceHttpToHttps::class,
    ];
```

# ユーザーマイグレーションの追加

```bash
php artisan make:migration add_github_fields_to_users_table --table=users
```

## マイグレーションの設定

```php:database/migrations/add_github_fields_to_users_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('github_id')->nullable();
            $table->string('avatar')->nullable();
            $table->string('token')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['github_id', 'avatar', 'token']);
        });
    }
};
```

```php:database/migrations/make_password_nullable_in_users_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
        });
    }
};
```

## Routing の設定

```php:routes/web.php
Route::get('auth/github', [GitHubController::class, 'redirectToProvider']);
Route::get('auth/v1/callback', [GitHubController::class, 'handleProviderCallback']);
Route::get('logout', [GitHubController::class, 'logout']);
```

## Controller の設定

```php:App/Http/Controllers/Auth/GitHubController.php
<?php

namespace App\Http\Controllers\Auth;

use Laravel\Socialite\Facades\Socialite;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class GitHubController extends Controller
{
    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider()
    {
        return Socialite::driver('github')->redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback()
    {
        try {
            $githubUser = Socialite::driver('github')->stateless()->user();

            // GitHubのアクセストークンをログに出力して確認
            Log::info('GitHub access token', ['token' => $githubUser->token]);

            $user = User::where('github_id', $githubUser->id)
                        ->orWhere('email', $githubUser->email)
                        ->first();

            if ($user) {
                if (is_null($user->github_id)) {
                    $user->github_id = $githubUser->id;
                    $user->save();
                }
                Auth::login($user, true);
            } else {
                $user = User::create([
                    'name' => $githubUser->name,
                    'email' => $githubUser->email,
                    'github_id' => $githubUser->id,
                    'avatar' => $githubUser->avatar,
                    'password' => bcrypt(Str::random(16)),
                ]);

                Auth::login($user, true);
            }

            // ユーザーがログインしていることを確認するためのログ
            Log::info('User logged in', ['user' => $user, 'session' => session()->all()]);

            return redirect('/');
        } catch (\Exception $e) {
            Log::error('Authentication failed', ['error' => $e->getMessage()]);
            return redirect('/')->withErrors(['login' => 'Authentication failed.']);
        }
    }

    /**
     * Logout the user from both Laravel and Supabase.
     *
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        try {
            Log::info('Starting logout process');

            // Laravelからのログアウト処理
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            Log::info('User logged out successfully');

            return redirect('/')->with('status', 'Logout successful');
        } catch (\Exception $e) {
            Log::error('Logout failed', ['error' => $e->getMessage()]);
            return redirect('/')->withErrors('Logout failed: ' . $e->getMessage());
        }
    }
}
```

## Blade の設定

```php:routes/resources/views/welcome.blade.php
<div class="sm:fixed sm:top-0 sm:right-0 p-6 text-right z-10">
    @auth
        <p>{{ Auth::user()->name }} is logged in</p>
        <a href="{{ url('logout') }}"
            class="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">
            Logout with GitHub
        </a>
    @else
        <p>Not logged in</p>
        <a href="{{ url('auth/github') }}"
            class="font-semibold text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline focus:outline-2 focus:rounded-sm focus:outline-red-500">
            Login with GitHub
        </a>
    @endauth
</div>
```
