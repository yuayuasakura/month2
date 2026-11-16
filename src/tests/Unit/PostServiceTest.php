<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\PostService;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

// PostService のユニットテスト
// Service は入力チェックをしない（バリデーションは PostRequest の役割）ので、
// 異常系は「存在しないデータ」「DB制約違反」「保存失敗時の後始末」を確認する
class PostServiceTest extends TestCase
{
    use RefreshDatabase;

    private PostService $service;
    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        // 本物の S3 には送らず、テスト用の仮ディスクに保存する
        Storage::fake('s3');
        // コンストラクタで受け取る Repository も自動で渡される
        $this->service = app(PostService::class);
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
    }

    private function data(array $overrides = []): array
    {
        return array_merge([
            'title' => 'タイトル',
            'content' => '本文',
            'category_id' => $this->category->id,
        ], $overrides);
    }

    // ===== createPost：正常系 =====

    public function test_create_post_saves_post_with_given_user()
    {
        $post = $this->service->createPost($this->data(), $this->user->id);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertTrue($post->exists);
        $this->assertSame($this->user->id, $post->user_id);
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'タイトル',
            'content' => '本文',
            'category_id' => $this->category->id,
            'image_path' => null,
        ]);
    }

    public function test_create_post_rejects_user_id_in_data()
    {
        // user_id は $fillable 外。開発環境では AppServiceProvider の preventSilentlyDiscardingAttributes により
        // 黙って無視されず例外になる（投稿者を書き換えようとするバグにすぐ気づける）
        $other = User::factory()->create();

        try {
            $this->service->createPost($this->data(['user_id' => $other->id]), $this->user->id);
            $this->fail('例外が投げられるはず');
        } catch (MassAssignmentException) {
            // 期待どおり
        }

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_create_post_with_image_stores_it_on_s3()
    {
        $post = $this->service->createPost(
            $this->data(['image' => UploadedFile::fake()->image('photo.png')]),
            $this->user->id
        );

        $this->assertStringStartsWith('images/', $post->image_path);
        Storage::disk('s3')->assertExists($post->image_path);
    }

    public function test_create_post_writes_log()
    {
        Log::spy();

        $post = $this->service->createPost($this->data(), $this->user->id);

        Log::shouldHaveReceived('info')->with('Post created', ['post_id' => $post->id])->once();
    }

    // ===== createPost：異常系 =====

    public function test_create_post_with_missing_category_throws_exception()
    {
        $this->expectException(QueryException::class);

        $this->service->createPost($this->data(['category_id' => 99999]), $this->user->id);
    }

    public function test_create_post_with_missing_user_throws_exception()
    {
        $this->expectException(QueryException::class);

        $this->service->createPost($this->data(), 99999);
    }

    public function test_create_post_failure_removes_uploaded_image_and_saves_nothing()
    {
        try {
            $this->service->createPost(
                $this->data(['category_id' => 99999, 'image' => UploadedFile::fake()->image('photo.png')]),
                $this->user->id
            );
            $this->fail('例外が投げられるはず');
        } catch (QueryException) {
            // 期待どおり
        }

        // DB にも S3 にも何も残らない
        $this->assertDatabaseCount('posts', 0);
        $this->assertSame([], Storage::disk('s3')->allFiles());
    }

    // ===== createPost：境界値 =====

    public function test_create_post_with_200_multibyte_char_title()
    {
        // posts.title は 200 文字（バリデーションの上限と同じ）
        $title = str_repeat('あ', 200);

        $post = $this->service->createPost($this->data(['title' => $title]), $this->user->id);

        $this->assertSame(200, mb_strlen($post->fresh()->title));
    }

    public function test_create_post_with_1_char_title_and_content()
    {
        $post = $this->service->createPost($this->data(['title' => 'a', 'content' => 'b']), $this->user->id);

        $this->assertSame('a', $post->fresh()->title);
        $this->assertSame('b', $post->fresh()->content);
    }

    public function test_create_post_with_10000_char_content()
    {
        $post = $this->service->createPost($this->data(['content' => str_repeat('a', 10000)]), $this->user->id);

        $this->assertSame(10000, strlen($post->fresh()->content));
    }

    public function test_create_post_with_null_image_is_same_as_no_image()
    {
        $post = $this->service->createPost($this->data(['image' => null]), $this->user->id);

        $this->assertNull($post->image_path);
        $this->assertSame([], Storage::disk('s3')->allFiles());
    }

    // ===== createApiPost =====

    public function test_create_api_post_uses_test_user_and_other_category()
    {
        $apiUser = User::factory()->create(['email' => 'test@example.com']);

        $post = $this->service->createApiPost(['title' => 'API', 'content' => '本文']);

        $this->assertSame($apiUser->id, $post->user_id);
        $this->assertSame('その他', $post->category->name);
        // レスポンス用に投稿者も読み込まれている
        $this->assertTrue($post->relationLoaded('user'));
    }

    public function test_create_api_post_reuses_existing_category()
    {
        User::factory()->create(['email' => 'test@example.com']);
        $existing = Category::create(['name' => 'その他']);

        $this->service->createApiPost(['title' => '1件目', 'content' => 'x']);
        $post = $this->service->createApiPost(['title' => '2件目', 'content' => 'x']);

        // 「その他」カテゴリーは増えない
        $this->assertSame($existing->id, $post->category_id);
        $this->assertSame(1, Category::where('name', 'その他')->count());
    }

    public function test_create_api_post_without_test_user_throws_exception()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->createApiPost(['title' => 'API', 'content' => '本文']);
    }

    // ===== updatePost =====

    public function test_update_post_changes_fields()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $newCategory = Category::factory()->create();

        $updated = $this->service->updatePost($post, ['title' => '新', 'content' => '新本文', 'category_id' => $newCategory->id]);

        $this->assertSame('新', $updated->title);
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => '新', 'content' => '新本文', 'category_id' => $newCategory->id]);
    }

    public function test_update_post_without_image_keeps_current_image()
    {
        $post = $this->service->createPost($this->data(['image' => UploadedFile::fake()->image('old.png')]), $this->user->id);
        $oldPath = $post->image_path;

        $updated = $this->service->updatePost($post, ['title' => '画像はそのまま']);

        $this->assertSame($oldPath, $updated->image_path);
        Storage::disk('s3')->assertExists($oldPath);
    }

    public function test_update_post_with_image_replaces_and_deletes_old_image()
    {
        $post = $this->service->createPost($this->data(['image' => UploadedFile::fake()->image('old.png')]), $this->user->id);
        $oldPath = $post->image_path;

        $updated = $this->service->updatePost($post, ['image' => UploadedFile::fake()->image('new.png')]);

        $this->assertNotSame($oldPath, $updated->image_path);
        Storage::disk('s3')->assertExists($updated->image_path);
        Storage::disk('s3')->assertMissing($oldPath);
    }

    public function test_update_post_failure_keeps_old_image_and_removes_new_one()
    {
        $post = $this->service->createPost($this->data(['image' => UploadedFile::fake()->image('old.png')]), $this->user->id);
        $oldPath = $post->image_path;

        try {
            $this->service->updatePost($post, [
                'category_id' => 99999, // 外部キー違反で失敗させる
                'image' => UploadedFile::fake()->image('new.png'),
            ]);
            $this->fail('例外が投げられるはず');
        } catch (QueryException) {
            // 期待どおり
        }

        // 元の画像だけが残り、DB も元のまま
        $this->assertSame([$oldPath], Storage::disk('s3')->allFiles());
        $this->assertSame($oldPath, $post->fresh()->image_path);
        $this->assertSame($this->category->id, $post->fresh()->category_id);
    }

    public function test_update_post_with_empty_data_changes_nothing()
    {
        $post = Post::factory()->create(['title' => 'そのまま']);

        $updated = $this->service->updatePost($post, []);

        $this->assertSame('そのまま', $updated->fresh()->title);
    }

    // ===== deletePost =====

    public function test_delete_post_removes_post_image_and_comments()
    {
        $post = $this->service->createPost($this->data(['image' => UploadedFile::fake()->image('a.png')]), $this->user->id);
        $comment = $post->comments()->make(['body' => 'コメント']);
        $comment->user_id = $this->user->id;
        $comment->save();

        $this->service->deletePost($post);

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        Storage::disk('s3')->assertMissing($post->image_path);
    }

    public function test_delete_post_without_image_works()
    {
        $post = Post::factory()->create(['image_path' => null]);

        $this->service->deletePost($post);

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertSame(0, Comment::count());
    }
}
