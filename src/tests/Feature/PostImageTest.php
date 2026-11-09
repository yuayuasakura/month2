<?php
namespace Tests\Feature;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
class PostImageTest extends TestCase
{
    use RefreshDatabase;
    public function test_post_image_upload_via_service(): void
    {
        // 本物の S3 には送らず、テスト用の仮ディスクに保存する
        Storage::fake('s3');
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $cat = Category::first();

        // 画面から画像付きで投稿 → S3 に保存され、パスが記録される
        $this->actingAs($user)->post('/posts', ['title'=>'画像あり','content'=>'x','category_id'=>$cat->id,'image'=>UploadedFile::fake()->image('a.jpg')])->assertRedirect();
        $post = Post::where('title','画像あり')->first();
        $this->assertStringStartsWith('images/', $post->image_path);
        Storage::disk('s3')->assertExists($post->image_path);
        $this->get("/posts/{$post->id}")->assertOk()->assertSee($post->image_path);

        // 画像なしでも投稿できる
        $this->post('/posts', ['title'=>'画像なし','content'=>'x','category_id'=>$cat->id])->assertRedirect();
        $this->assertNull(Post::where('title','画像なし')->first()->image_path);

        // 画像以外は弾く
        $this->post('/posts', ['title'=>'a','content'=>'x','category_id'=>$cat->id,'image'=>UploadedFile::fake()->create('a.pdf', 10, 'application/pdf')])->assertSessionHasErrors('image');

        // 更新で画像を差し替えると古い画像は消える
        $old = $post->image_path;
        $this->put("/posts/{$post->id}", ['title'=>'画像あり','content'=>'y','category_id'=>$cat->id,'image'=>UploadedFile::fake()->image('b.jpg')])->assertRedirect();
        $post->refresh();
        $this->assertNotSame($old, $post->image_path);
        Storage::disk('s3')->assertMissing($old);
        Storage::disk('s3')->assertExists($post->image_path);
        // 画像なしで更新すると今の画像のまま
        $this->put("/posts/{$post->id}", ['title'=>'画像あり','content'=>'z','category_id'=>$cat->id])->assertRedirect();
        $this->assertSame($post->image_path, $post->fresh()->image_path);

        // 削除すると画像も消える
        $path = $post->image_path;
        $this->delete("/posts/{$post->id}")->assertRedirect('/posts');
        Storage::disk('s3')->assertMissing($path);

        // API からも同じ処理で画像付き投稿ができる
        $res = $this->post('/api/posts', ['title'=>'API画像','content'=>'x','image'=>UploadedFile::fake()->image('c.png')], ['Accept'=>'application/json'])->assertCreated();
        $apiPost = Post::where('title','API画像')->first();
        Storage::disk('s3')->assertExists($apiPost->image_path);
        $this->assertStringContainsString($apiPost->image_path, $res->json('data.image_url'));
        $this->postJson('/api/posts', ['title'=>'画像なし','content'=>'x'])->assertCreated()->assertJsonPath('data.image_url', null);
    }

    public function test_uploaded_image_is_deleted_when_db_save_fails(): void
    {
        Storage::fake('s3');
        // Repository の保存でエラーが起きる状況を作る
        $this->mock(\App\Repositories\PostRepository::class)
            ->shouldReceive('create')->andThrow(new \RuntimeException('DB error'));

        try {
            app(\App\Services\PostService::class)->createPost(['title'=>'a','content'=>'b','image'=>UploadedFile::fake()->image('a.jpg')], 1);
            $this->fail('例外が投げられるはず');
        } catch (\RuntimeException $e) {
            $this->assertSame('DB error', $e->getMessage());
        }
        // S3 に先に上げた画像が残っていない
        $this->assertSame([], Storage::disk('s3')->allFiles());
    }
}
