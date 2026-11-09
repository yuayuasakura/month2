<?php
namespace Tests\Feature;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
class WelcomeMailTest extends TestCase
{
    use RefreshDatabase;
    public function test_welcome_mail_is_sent_on_registration(): void
    {
        Mail::fake();
        $this->post('/register', ['name'=>'花子','email'=>'hanako@ex.jp','password'=>'Passw0rd123','password_confirmation'=>'Passw0rd123'])->assertRedirect('/dashboard');
        $user = User::where('email','hanako@ex.jp')->first();
        Mail::assertSent(WelcomeMail::class, fn ($mail) => $mail->hasTo('hanako@ex.jp') && $mail->user->is($user));
        Mail::assertSentCount(1);

        // 登録に失敗したら（バリデーションエラー）送らない
        Mail::fake();
        auth()->logout();
        $this->post('/register', ['name'=>'x','email'=>'hanako@ex.jp','password'=>'Passw0rd123','password_confirmation'=>'Passw0rd123'])->assertSessionHasErrors('email');
        Mail::assertNothingSent();
    }

    public function test_welcome_mail_content(): void
    {
        $user = User::factory()->create(['name'=>'花子','email'=>'hanako@ex.jp']);
        $mail = new WelcomeMail($user);
        $mail->assertHasSubject('【'.config('app.name').'】ご登録ありがとうございます');
        $mail->assertSeeInHtml('花子 さん、ようこそ！');
        $mail->assertSeeInHtml('hanako@ex.jp');
        $mail->assertSeeInHtml(route('posts.create'));
    }

    public function test_registration_succeeds_even_if_mail_fails(): void
    {
        // 本物のログファイルに書き込まないよう Log を差し替え、エラーが記録されたかを確認する
        \Illuminate\Support\Facades\Log::spy();
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));
        $this->post('/register', ['name'=>'花子','email'=>'hanako@ex.jp','password'=>'Passw0rd123','password_confirmation'=>'Passw0rd123'])->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        \Illuminate\Support\Facades\Log::shouldHaveReceived('error')->once();
    }
}
