<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // パスワードのルール（ユーザー登録・パスワード変更・パスワード再設定で共通して使われる）
        Password::defaults(function () {
            $rule = Password::min(8)    // 8文字以上
                ->letters()             // 英字を含む
                ->mixedCase()           // 大文字と小文字を両方含む
                ->numbers();            // 数字を含む

            // 本番環境では、過去に流出したパスワード（Have I Been Pwned に登録済み）も拒否する
            // ※ 外部APIに問い合わせるため、開発・テストでは使わない
            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });

        // 開発中は、$fillable に無いカラムへの一括代入を「黙って無視」せずエラーにする
        // （user_id などを勝手に書き換えようとするバグにすぐ気づける）
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // URLの {post} {comment} {task} は数字だけ受け付ける（"abc" や "1 OR 1=1" は404にする）
        // コントローラーが int $id で受け取るので、数字以外が来ると型エラー(500)になるのを防ぐ
        Route::pattern('post', '[0-9]+');
        Route::pattern('comment', '[0-9]+');
        Route::pattern('task', '[0-9]+');
    }
}
