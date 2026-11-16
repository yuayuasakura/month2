<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// 認証機能（ユーザー登録・ログイン・ログアウト）の Feature Test
// Breeze が生成した tests/Feature/Auth/* は「動くかどうか」の最低限なので、
// ここでは DB の中身・エラーメッセージ・異常系まで確認する
class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // 登録時のウェルカムメールは実際には送らない
        Mail::fake();
    }

    private function registerData(array $overrides = []): array
    {
        return array_merge([
            'name' => '山田太郎',
            'email' => 'taro@example.com',
            'password' => 'Passw0rdTest',
            'password_confirmation' => 'Passw0rdTest',
        ], $overrides);
    }

    // ===================================================================
    // ユーザー登録：フォーム送信 → DBに保存 → リダイレクト
    // ===================================================================

    public function test_register_form_is_displayed()
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false);
    }

    public function test_user_can_register_and_is_saved_to_db()
    {
        $response = $this->post('/register', $this->registerData());

        // DB に保存される
        $this->assertDatabaseHas('users', [
            'name' => '山田太郎',
            'email' => 'taro@example.com',
        ]);
        // ダッシュボードへリダイレクト
        $response->assertRedirect(route('dashboard', absolute: false));
        // 登録したユーザーでログイン済みになる
        $this->assertAuthenticatedAs(User::where('email', 'taro@example.com')->first());
    }

    public function test_password_is_stored_hashed()
    {
        $this->post('/register', $this->registerData());

        $user = User::where('email', 'taro@example.com')->first();
        // 平文のまま保存されていない
        $this->assertNotSame('Passw0rdTest', $user->password);
        $this->assertTrue(Hash::check('Passw0rdTest', $user->password));
    }

    public function test_registered_user_can_access_dashboard()
    {
        $this->post('/register', $this->registerData());

        $this->get('/dashboard')->assertOk();
    }

    public function test_logged_in_user_is_redirected_from_register_page()
    {
        // ログイン済みなら登録画面は見せずにダッシュボードへ
        $this->actingAs(User::factory()->create())
            ->get('/register')
            ->assertRedirect(route('dashboard', absolute: false));
    }

    // ----- 登録のバリデーション -----

    public static function invalidRegisterProvider(): array
    {
        return [
            '名前が空' => ['name', ''],
            '名前が256文字' => ['name', str_repeat('a', 256)],
            'メールが空' => ['email', ''],
            'メール形式でない' => ['email', 'not-an-email'],
            'メールに大文字' => ['email', 'Taro@Example.com'],
            'パスワードが空' => ['password', ''],
            'パスワードが7文字' => ['password', 'Passw0r'],
            'パスワードに数字なし' => ['password', 'PasswordTest'],
            'パスワードに大文字なし' => ['password', 'passw0rdtest'],
            'パスワードに小文字なし' => ['password', 'PASSW0RDTEST'],
        ];
    }

    #[DataProvider('invalidRegisterProvider')]
    public function test_register_validation_errors(string $field, string $value)
    {
        $data = $this->registerData([$field => $value]);
        if ($field === 'password') {
            $data['password_confirmation'] = $value;
        }

        $this->from('/register')->post('/register', $data)
            ->assertRedirect('/register')
            ->assertSessionHasErrors($field);

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_password_with_exactly_8_chars_is_accepted()
    {
        // 境界値：8文字ちょうどは OK（7文字は上の DataProvider で NG を確認）
        $this->post('/register', $this->registerData(['password' => 'Passw0rd', 'password_confirmation' => 'Passw0rd']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('users', 1);
    }

    public function test_password_confirmation_must_match()
    {
        $this->post('/register', $this->registerData(['password_confirmation' => 'Different1A']))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_duplicate_email_cannot_register()
    {
        User::factory()->create(['email' => 'taro@example.com']);

        $this->post('/register', $this->registerData())
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_password_is_not_kept_in_old_input()
    {
        // エラーで戻ったとき、パスワードはフォームに残さない（メールなどは残る）
        $this->post('/register', $this->registerData(['name' => '']))
            ->assertSessionHasInput('email', 'taro@example.com')
            ->assertSessionMissing('_old_input.password');
    }

    // ===================================================================
    // ログイン：正しい認証情報 → 成功 / 不正な情報 → エラー
    // ===================================================================

    public function test_login_form_is_displayed()
    {
        $this->get('/login')->assertOk()->assertSee('name="password"', false);
    }

    public function test_user_can_login_with_correct_credentials()
    {
        $user = User::factory()->create(['password' => Hash::make('Passw0rdTest')]);

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'Passw0rdTest']);

        $response->assertRedirect(route('dashboard', absolute: false));
        $response->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_redirects_to_intended_page()
    {
        // ログインが必要なページ → ログイン画面 → ログイン後に元のページへ戻る
        $user = User::factory()->create();

        $this->get('/posts/create')->assertRedirect('/login');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/posts/create');
    }

    public function test_login_with_remember_me_sets_remember_token()
    {
        $user = User::factory()->create(['remember_token' => null]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password', 'remember' => 'on']);

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_login_fails_with_wrong_password()
    {
        $user = User::factory()->create();

        $response = $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email' => trans('auth.failed')]);
        $this->assertGuest();
    }

    public function test_login_fails_with_unknown_email()
    {
        // 存在しないメールでも、パスワード間違いと同じメッセージ（どちらが違うか教えない）
        $this->post('/login', ['email' => 'nobody@example.com', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => trans('auth.failed')]);

        $this->assertGuest();
    }

    public function test_login_fails_with_case_different_password()
    {
        $user = User::factory()->create(['password' => Hash::make('Passw0rdTest')]);

        $this->post('/login', ['email' => $user->email, 'password' => 'passw0rdtest']);

        $this->assertGuest();
    }

    public static function invalidLoginProvider(): array
    {
        return [
            'メールが空' => [['email' => '', 'password' => 'password'], 'email'],
            'メール形式でない' => [['email' => 'abc', 'password' => 'password'], 'email'],
            'パスワードが空' => [['email' => 'a@example.com', 'password' => ''], 'password'],
            '両方なし' => [[], 'email'],
        ];
    }

    #[DataProvider('invalidLoginProvider')]
    public function test_login_validation_errors(array $data, string $errorField)
    {
        $this->post('/login', $data)->assertSessionHasErrors($errorField);

        $this->assertGuest();
    }

    public function test_login_is_locked_after_5_failed_attempts()
    {
        $user = User::factory()->create();

        // 5回失敗
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        // 6回目は正しいパスワードでもロックされる
        $response = $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            explode(':', trans('auth.throttle'))[0],
            session('errors')->first('email')
        );
        $this->assertGuest();
    }

    public function test_login_succeeds_after_4_failed_attempts()
    {
        // 境界値：4回失敗まではロックされない
        $user = User::factory()->create();

        for ($i = 0; $i < 4; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);

        $this->assertAuthenticatedAs($user);
    }

    public function test_logged_in_user_is_redirected_from_login_page()
    {
        $this->actingAs(User::factory()->create())
            ->get('/login')
            ->assertRedirect(route('dashboard', absolute: false));
    }

    // ===================================================================
    // ログアウト
    // ===================================================================

    public function test_user_can_logout()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_after_logout_protected_pages_redirect_to_login()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout');

        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/posts/create')->assertRedirect('/login');
    }

    public function test_guest_cannot_access_dashboard()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_logout_by_get_is_not_allowed()
    {
        // ログアウトは POST のみ（GET のリンクを踏まされてログアウトされるのを防ぐ）
        $this->actingAs(User::factory()->create())
            ->get('/logout')
            ->assertStatus(405);
    }
}
