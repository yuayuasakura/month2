<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ProfileController;
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

// ===== Laravel Breeze（ダッシュボード・プロフィール） =====
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// ===== ブログ =====
// 投稿の作成・編集・削除はログインユーザーのみ（未ログインならログイン画面へリダイレクト）
// ※ /posts/create が /posts/{post} に吸い込まれないよう、show より先に登録する
Route::middleware('auth')->group(function () {
    Route::resource('posts', PostController::class)->except(['index', 'show']);

    // コメントの投稿・削除もログインユーザーのみ（コメントの表示は投稿詳細ページで誰でも見られる）
    // shallow()：投稿は POST /posts/{post}/comments、削除は DELETE /comments/{comment} という短いURLになる
    // throttle：連投（スパム）を防ぐため、コメント関連の操作は1分に10回までに制限
    Route::resource('posts.comments', CommentController::class)
        ->only(['store', 'destroy'])
        ->shallow()
        ->middleware('throttle:10,1');
});
// 一覧・詳細は誰でも見られる
Route::resource('posts', PostController::class)->only(['index', 'show']);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ===== 商品管理（CRUD全て） =====
Route::resource('products', ProductController::class);

// ===== 予約システム =====
// イベントは閲覧のみ（一覧・詳細）
Route::resource('events', EventController::class)->only(['index', 'show']);
// 予約の作成はイベントに紐づくので /events/{event}/reservations/create のようなURLにする（ネストしたリソース）
Route::resource('events.reservations', ReservationController::class)->only(['create', 'store']);
// 予約一覧
Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
// 予約のキャンセル（データは消さずに状態を変えるので DELETE ではなく PATCH）
Route::patch('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');

// ログイン・ユーザー登録・パスワードリセットなど（Breeze が生成）
require __DIR__.'/auth.php';
