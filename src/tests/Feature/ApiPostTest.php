<?php
namespace Tests\Feature;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ApiPostTest extends TestCase
{
    use RefreshDatabase;
    public function test_api_post_create_and_list(): void
    {
        $this->seed(DatabaseSeeder::class);
        $testUser = User::where('email','test@example.com')->first();
        $other = User::factory()->create();

        // バリデーションエラーは 422 + 日本語メッセージ
        $this->postJson('/api/posts', [])->assertStatus(422)
            ->assertJsonValidationErrors(['title'=>'タイトルを入力してください','content'=>'本文を入力してください']);
        $this->assertSame(0, Post::count());

        // user_id を偽装しても test@example.com の投稿になる。カテゴリーは「その他」
        $this->postJson('/api/posts', ['title'=>'Vueから','content'=>'本文です','user_id'=>$other->id])
            ->assertCreated()
            ->assertJsonPath('data.title','Vueから')
            ->assertJsonPath('data.user.name','Test User');
        $post = Post::first();
        $this->assertSame($testUser->id, $post->user_id);
        $this->assertSame('その他', $post->category->name);

        // 一覧に反映される（新しい順）・メールアドレスは返さない
        $this->postJson('/api/posts', ['title'=>'2件目','content'=>'x'])->assertCreated();
        $this->getJson('/api/posts')->assertOk()
            ->assertJsonPath('data.0.title','2件目')
            ->assertJsonPath('data.1.title','Vueから')
            ->assertJsonMissingPath('data.0.user.email');
        $this->getJson("/api/posts/{$post->id}")->assertOk()->assertJsonPath('data.content','本文です');
        $this->getJson('/api/posts/abc')->assertNotFound();

        // 画面のブログの一覧・詳細にも表示できる
        $this->get("/posts/{$post->id}")->assertOk()->assertSee('投稿者: Test User');
    }

    public function test_cors_allows_vite_dev_server(): void
    {
        $this->seed(CategorySeeder::class);
        $this->options('/api/posts', [], ['Origin'=>'http://localhost:5173','Access-Control-Request-Method'=>'POST','Access-Control-Request-Headers'=>'content-type'])
            ->assertHeader('Access-Control-Allow-Origin','http://localhost:5173');
    }
}
