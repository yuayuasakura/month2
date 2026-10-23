<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;

// 基本的なルート（GETリクエストで / にアクセスしたら welcome ビューを返す）
Route::get('/', function () {
    return view('welcome');
});

// ※ UserController はまだ作成していないためコメントアウト
//    （存在しないコントローラーを指定すると artisan route:list などがエラーになる）
// use App\Http\Controllers\UserController;
// Route::get('/users', [UserController::class, 'index']);
// Route::get('/users/{id}', [UserController::class, 'show']);

// RESTfulなリソースルート（CRUD全て）
// index / create / store / show / edit / update / destroy の7つのルートがまとめて作られる
Route::resource('posts', PostController::class);
