<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\StripeWebhookController;

Route::get('/posts', [PostController::class, 'index']);       // 一覧
Route::get('/posts/{id}', [PostController::class, 'show'])->whereNumber('id');   // 1件取得（数字以外のIDは404）
Route::post('/posts', [PostController::class, 'store']);      // 新規作成
Route::post('/webhook/stripe', [StripeWebhookController::class, 'handle']);

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Week 10 の Api\PostController にあるメソッド（index・show・store）だけを登録する
    Route::apiResource('posts', PostController::class)->only(['index', 'show', 'store']);
});