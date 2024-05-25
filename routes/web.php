<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GitHubController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    return view('test');
});

Route::get('auth/github', [GitHubController::class, 'redirectToProvider']);
Route::get('auth/v1/callback', [GitHubController::class, 'handleProviderCallback']);
Route::get('/logout', [GitHubController::class, 'logout'])->name('logout');

// Route::get('/set-session', function () {
//     session(['test_key' => 'test_value']);
//     return 'Session set';
// });

// Route::get('/get-session', function () {
//     return session('test_key', 'default_value');
// });