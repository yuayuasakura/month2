<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Label;
use App\Models\Post;
use App\Models\User;
use App\Repositories\CategoryRepository;
use App\Repositories\LabelRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// 一覧データのキャッシュ
//   キャッシュが効いていること（2回目は DB に問い合わせない）
//   データが変わったら、古いキャッシュが表示されないこと
class CacheTest extends TestCase
{
    use RefreshDatabase;

    // $callback の中で、$table への SELECT が何回発行されたか（MySQL は `posts`、テストの SQLite は "posts" と書くので両方に合わせる）
    private function countSelects(string $table, callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();

        return collect(DB::getQueryLog())
            ->filter(fn ($q) => str_starts_with($q['query'], 'select') && preg_match("/from [`\"]{$table}[`\"]/", $q['query']))
            ->count();
    }

    // ===== 投稿一覧 =====

    public function test_posts_index_is_cached()
    {
        Post::factory()->count(3)->create();

        $this->assertGreaterThan(0, $this->countSelects('posts', fn () => $this->get('/posts')->assertOk()));
        // 2回目はキャッシュから返すので、posts テーブルを読まない
        $this->assertSame(0, $this->countSelects('posts', fn () => $this->get('/posts')->assertOk()));
    }

    public function test_each_page_is_cached_separately()
    {
        Post::factory()->count(11)->create();
        $oldest = Post::oldest('id')->first();

        $this->get('/posts')->assertOk()->assertDontSee($oldest->title);
        $this->get('/posts?page=2')->assertOk()->assertSee($oldest->title);
    }

    public function test_new_post_appears_immediately()
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $this->get('/posts')->assertOk();

        $this->actingAs($user)->post('/posts', [
            'title' => 'キャッシュ後に作った投稿',
            'content' => '本文',
            'category_id' => $category->id,
        ]);

        $this->get('/posts')->assertSee('キャッシュ後に作った投稿');
    }

    public function test_updated_and_deleted_posts_are_reflected()
    {
        $post = Post::factory()->create(['title' => '変更前のタイトル']);
        $this->get('/posts')->assertSee('変更前のタイトル');

        $post->update(['title' => '変更後のタイトル']);
        $this->get('/posts')->assertSee('変更後のタイトル')->assertDontSee('変更前のタイトル');

        $post->delete();
        $this->get('/posts')->assertDontSee('変更後のタイトル');
    }

    public function test_like_count_is_reflected()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $this->get('/posts')->assertSee('いいね 0件');

        $this->actingAs($user)->post("/posts/{$post->id}/like");
        $this->get('/posts')->assertSee('いいね 1件');

        $this->actingAs($user)->delete("/posts/{$post->id}/like");
        $this->get('/posts')->assertSee('いいね 0件');
    }

    public function test_comment_count_is_reflected()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $this->get('/posts')->assertSee('コメント 0件');

        $this->actingAs($user)->post("/posts/{$post->id}/comments", ['body' => 'コメント']);
        $this->get('/posts')->assertSee('コメント 1件');

        $comment = Comment::first();
        $this->actingAs($user)->delete("/comments/{$comment->id}");
        $this->get('/posts')->assertSee('コメント 0件');
    }

    public function test_author_name_change_is_reflected()
    {
        $post = Post::factory()->create();
        $this->get('/posts')->assertSee($post->user->name);

        $post->user->update(['name' => '新しい名前']);
        $this->get('/posts')->assertSee('新しい名前');
    }

    public function test_deleted_author_posts_disappear()
    {
        // 投稿は DB の cascadeOnDelete で消える（Post のイベントは起きない）
        $post = Post::factory()->create(['title' => '退会する人の投稿']);
        $this->get('/posts')->assertSee('退会する人の投稿');

        $post->user->delete();
        $this->get('/posts')->assertDontSee('退会する人の投稿');
    }

    public function test_html_and_api_share_cache_but_keep_their_own_pagination_urls()
    {
        Post::factory()->count(11)->create();
        $this->get('/posts')->assertOk();

        // /posts で作ったキャッシュを使っても、API のページリンクは /api/posts になる
        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('links.next', url('/api/posts?page=2'));
    }

    public function test_out_of_range_pages_are_not_cached()
    {
        Post::factory()->create();
        $this->get('/posts?page=999')->assertOk();

        $this->assertGreaterThan(0, $this->countSelects('posts', fn () => $this->get('/posts?page=999')->assertOk()));
    }

    // ===== カテゴリー・ラベル（フォームの選択肢） =====

    public function test_categories_are_cached_and_refreshed_on_change()
    {
        $repository = app(CategoryRepository::class);
        Category::factory()->create(['name' => '最初のカテゴリー']);

        $repository->getAll();
        $this->assertSame(0, $this->countSelects('categories', fn () => $repository->getAll()));

        Category::factory()->create(['name' => '追加したカテゴリー']);
        $this->assertContains('追加したカテゴリー', $repository->getAll()->pluck('name'));
    }

    public function test_labels_are_cached_and_refreshed_on_change()
    {
        $repository = app(LabelRepository::class);
        $label = Label::create(['name' => '仕事']);

        $repository->getAll();
        $this->assertSame(0, $this->countSelects('labels', fn () => $repository->getAll()));

        $label->update(['name' => '趣味']);
        $this->assertSame(['趣味'], $repository->getAll()->pluck('name')->all());
    }
}
