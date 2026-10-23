<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ReservationController;

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

// 商品管理（CRUD全て）
Route::resource('products', ProductController::class);

// 予約システム
// イベントは閲覧のみ（一覧・詳細）
Route::resource('events', EventController::class)->only(['index', 'show']);
// 予約の作成はイベントに紐づくので /events/{event}/reservations/create のようなURLにする（ネストしたリソース）
Route::resource('events.reservations', ReservationController::class)->only(['create', 'store']);
// 予約一覧
Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
// 予約のキャンセル（データは消さずに状態を変えるので DELETE ではなく PATCH）
Route::patch('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');
