<?php
namespace Tests\Feature;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class MemberBlogTest extends TestCase
{
    use RefreshDatabase;
    public function test_member_blog(): void
    {
        $this->seed(CategorySeeder::class);
        $cat = Category::first();
        $data = ['title'=>'はじめて','content'=>'本文','category_id'=>$cat->id];

        // ゲスト：閲覧OK、作成系はログインへ
        $this->get('/posts')->assertOk()->assertSee('ログイン')->assertDontSee('新規投稿');
        $this->get('/posts/create')->assertRedirect('/login');
        $this->post('/posts', $data)->assertRedirect('/login');
        $this->assertSame(0, Post::count());

        // 登録 → ログイン状態
        $this->post('/register', ['name'=>'花子','email'=>'hanako@ex.jp','password'=>'Passw0rd123','password_confirmation'=>'Passw0rd123'])->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $hanako = User::where('email','hanako@ex.jp')->first();
        $this->get('/dashboard')->assertOk()->assertSee('ブログ');
        $this->get('/posts')->assertSee('花子 さん')->assertSee('新規投稿')->assertSee('ログアウト');
        $this->get('/posts/create')->assertOk();
        $this->post('/posts', [])->assertSessionHasErrors(['title','content','category_id']);
        // user_id を偽装しても自分の投稿になる
        $other = User::factory()->create(['name'=>'太郎']);
        $this->post('/posts', $data + ['user_id'=>$other->id])->assertRedirect();
        $post = Post::first();
        $this->assertSame($hanako->id, $post->user_id);
        $this->get("/posts/{$post->id}")->assertSee('投稿者: 花子')->assertSee('編集')->assertSee('削除');
        $this->get('/posts')->assertSee('花子 ／');
        $this->get("/posts/{$post->id}/edit")->assertOk();
        $this->put("/posts/{$post->id}", ['title'=>'改題','content'=>'x','category_id'=>$cat->id])->assertRedirect("/posts/{$post->id}");
        $this->assertSame('改題', $post->fresh()->title);

        // 他人：編集・削除できない
        $this->post('/logout'); $this->assertGuest();
        $this->actingAs($other);
        $this->get("/posts/{$post->id}")->assertOk()->assertDontSee('>編集<', false)->assertDontSee('本当に削除しますか');
        $this->get("/posts/{$post->id}/edit")->assertForbidden();
        $this->put("/posts/{$post->id}", ['title'=>'乗っ取り','content'=>'x','category_id'=>$cat->id])->assertForbidden();
        $this->delete("/posts/{$post->id}")->assertForbidden();
        $this->assertSame('改題', $post->fresh()->title);

        // 投稿者不明（既存データ）は誰も編集不可、表示は落ちない
        $orphan = Post::forceCreate(['title'=>'古い投稿','content'=>'a','category_id'=>$cat->id,'user_id'=>null]);
        $this->get('/posts')->assertOk()->assertSee('投稿者不明');
        $this->get("/posts/{$orphan->id}/edit")->assertForbidden();

        // 本人は削除できる
        $this->actingAs($hanako);
        $this->delete("/posts/{$post->id}")->assertRedirect('/posts');
        $this->assertNull(Post::find($post->id));

        // ゲストの削除はログインへ
        auth()->logout();
        $this->delete("/posts/{$orphan->id}")->assertRedirect('/login');

        foreach (['/','/products','/events','/reservations','/login','/register'] as $u) $this->get($u)->assertOk();
    }
}
