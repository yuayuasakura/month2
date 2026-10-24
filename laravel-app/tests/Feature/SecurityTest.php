<?php
namespace Tests\Feature;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_xss_escaped_and_csp(): void
    {
        $this->seed(CategorySeeder::class);
        $u = User::factory()->create(['name' => '<img src=x onerror=alert(1)>']);
        $xss = '<script>alert("xss")</script>';
        $this->actingAs($u)->post('/posts', ['title'=>$xss,'content'=>$xss."\n2行目",'category_id'=>Category::first()->id]);
        $p = Post::first();
        foreach (["/posts", "/posts/{$p->id}", "/posts/{$p->id}/edit"] as $url) {
            $r = $this->get($url)->assertOk();
            $r->assertDontSee($xss, false)->assertSee('&lt;script&gt;', false);
            $r->assertDontSee('<img src=x', false);
            $csp = $r->headers->get('Content-Security-Policy');
            $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[A-Za-z0-9]+'/", $csp);
            $this->assertStringContainsString("object-src 'none'", $csp);
            $this->assertSame('nosniff', $r->headers->get('X-Content-Type-Options'));
            $this->assertSame('SAMEORIGIN', $r->headers->get('X-Frame-Options'));
            $this->assertStringNotContainsString('onsubmit=', $r->getContent());
        }
        // 自前スクリプトに正しい nonce が付く
        $r = $this->get("/posts/{$p->id}");
        preg_match("/'nonce-([^']+)'/", $r->headers->get('Content-Security-Policy'), $m);
        $r->assertSee('<script nonce="'.$m[1].'">', false)->assertSee('data-confirm=', false);
        // Breeze 画面（@vite のタグにも nonce）
        $this->get('/dashboard')->assertOk()->assertSee('nonce="', false)->assertDontSee('onclick=', false);
        $this->get('/products')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    public function test_sql_injection_is_harmless(): void
    {
        $this->seed(CategorySeeder::class);
        $u = User::factory()->create();
        $sqli = "'; DROP TABLE posts; -- \" OR 1=1";
        $this->actingAs($u)->post('/posts', ['title'=>$sqli,'content'=>$sqli,'category_id'=>Category::first()->id])->assertRedirect();
        $this->assertTrue(Schema::hasTable('posts'));
        $this->assertSame($sqli, Post::first()->title);
        $this->get("/posts/1 OR 1=1")->assertNotFound();
        $this->get("/posts/1'")->assertNotFound();
        $this->actingAs($u)->post('/posts', ['title'=>'a','content'=>'b','category_id'=>"1 OR 1=1"])->assertSessionHasErrors('category_id');
        auth()->logout();
        $this->post('/login', ['email'=>"' OR '1'='1", 'password'=>"' OR '1'='1"])->assertSessionHasErrors('email');
    }

    public function test_password_security(): void
    {
        $reg = fn($pw) => $this->post('/register', ['name'=>'a','email'=>uniqid().'@ex.jp','password'=>$pw,'password_confirmation'=>$pw]);
        foreach (['short1A', 'alllowercase1', 'ALLUPPER1', 'NoNumbersHere', '12345678'] as $weak) {
            $reg($weak)->assertSessionHasErrors('password');
            $this->assertGuest();
        }
        $reg('Passw0rdOK')->assertSessionHasNoErrors();
        $user = User::latest('id')->first();
        $this->assertNotSame('Passw0rdOK', $user->password);           // 平文で保存しない
        $this->assertStringStartsWith('$2y$', $user->password);        // bcrypt
        $this->assertTrue(Hash::check('Passw0rdOK', $user->password));
        $this->assertArrayNotHasKey('password', $user->toArray());     // JSON等に出さない
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
        auth()->logout();
        // ログイン試行回数制限（5回失敗でロック）
        for ($i = 0; $i < 5; $i++) $this->post('/login', ['email'=>$user->email,'password'=>'Wrong1234']);
        $this->post('/login', ['email'=>$user->email,'password'=>'Passw0rdOK'])->assertSessionHasErrors('email');
        $this->assertGuest();
        // 登録の回数制限
        for ($i = 0; $i < 6; $i++) $reg('weak');
        $reg('Passw0rdOK')->assertStatus(429);
    }

    public function test_mass_assignment_guard(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\MassAssignmentException::class);
        Post::create(['title'=>'a','content'=>'b','category_id'=>1,'user_id'=>999]);
    }
}
