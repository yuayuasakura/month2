<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PostController;

Route::get('/posts', [PostController::class, 'index']);       // 一覧
Route::get('/posts/{id}', [PostController::class, 'show'])->whereNumber('id');   // 1件取得（数字以外のIDは404）
Route::post('/posts', [PostController::class, 'store']);      // 新規作成
