<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// 投稿（/posts）の CRUD 全体をテストする
//   Create: create / store
//   Read  : index / show
//   Update: edit / update
//   Delete: destroy
// バリデーションの細かいルールは PostValidationTest で確認する
class PostTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
    }

    // 有効な投稿データ（各テストで必要な項目だけ上書きして使う）
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Test Post',
            'content' => 'This is a test post.',
            'category_id' => $this->category->id,
        ], $overrides);
    }

    // ===== Read（一覧） =====

    public function test_user_can_view_posts()
    {
        Post::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->get('/posts');

        $response->assertStatus(200);
        $response->assertViewIs('posts.index');
        $response->assertViewHas('posts', fn ($posts) => $posts->count() === 3);
    }

    // N+1問題の再発防止：投稿が何件あっても、一覧ページのSQLの本数は変わらない
    public function test_posts_index_query_count_does_not_grow_with_posts()
    {
        $countQueries = function () {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get('/posts')->assertOk();
            return count(DB::getQueryLog());
        };

        Post::factory()->count(2)->create();
        $few = $countQueries();

        // 投稿者・カテゴリーがそれぞれ別の投稿を8件追加（N+1 なら SQL が投稿の数だけ増える）
        Post::factory()->count(8)->create();
        $many = $countQueries();

        $this->assertSame($few, $many);
    }

    public function test_guest_can_view_posts()
    {
        $post = Post::factory()->create(['title' => 'ゲストにも見える投稿']);

        $this->get('/posts')->assertOk()->assertSee($post->title);
    }

    public function test_index_shows_empty_list_when_no_posts()
    {
        $this->get('/posts')
            ->assertOk()
            ->assertViewHas('posts', fn ($posts) => $posts->count() === 0);
    }

    public function test_index_is_paginated_by_10()
    {
        Post::factory()->count(11)->create();

        // 1ページ目は10件、2ページ目に残りの1件
        $this->get('/posts')->assertViewHas('posts', fn ($posts) => $posts->count() === 10 && $posts->total() === 11);
        $this->get('/posts?page=2')->assertViewHas('posts', fn ($posts) => $posts->count() === 1);
    }

    public function test_index_orders_newest_first()
    {
        $old = Post::factory()->create(['created_at' => now()->subDay()]);
        $new = Post::factory()->create(['created_at' => now()]);

        $this->get('/posts')->assertViewHas('posts', fn ($posts) => $posts->first()->is($new) && $posts->last()->is($old));
    }

    // ===== Read（詳細） =====

    public function test_guest_can_view_single_post_with_comments()
    {
        $post = Post::factory()->create(['title' => '詳細ページ']);
        $comment = new Comment(['body' => '最初のコメント']);
        $comment->post_id = $post->id;
        $comment->user_id = $this->user->id;
        $comment->save();

        $this->get("/posts/{$post->id}")
            ->assertOk()
            ->assertViewIs('posts.show')
            ->assertSee('詳細ページ')
            ->assertSee('最初のコメント');
    }

    public function test_show_returns_404_for_missing_post()
    {
        $this->get('/posts/99999')->assertNotFound();
    }

    public function test_show_returns_404_for_non_numeric_id()
    {
        $this->get('/posts/abc')->assertNotFound();
    }

    // ===== Create =====

    public function test_authenticated_user_can_view_create_form()
    {
        $this->actingAs($this->user)
            ->get('/posts/create')
            ->assertOk()
            ->assertViewIs('posts.create')
            ->assertViewHas('categories');
    }

    public function test_guest_cannot_view_create_form()
    {
        $this->get('/posts/create')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_create_post()
    {
        $response = $this->actingAs($this->user)->post('/posts', $this->validData());

        // store は作成した投稿の詳細画面へリダイレクトする
        $post = Post::where('title', 'Test Post')->first();
        $this->assertNotNull($post);
        $response->assertRedirect(route('posts.show', $post));
        $response->assertSessionHas('success', '投稿を作成しました');
        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);
    }

    public function test_guest_cannot_create_post()
    {
        $response = $this->post('/posts', $this->validData());

        $response->assertRedirect('/login');
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_user_id_in_request_is_ignored_on_create()
    {
        // フォームに他人の user_id を混ぜても、投稿者はログインユーザーになる
        $other = User::factory()->create();

        $this->actingAs($this->user)->post('/posts', $this->validData(['user_id' => $other->id]));

        $this->assertDatabaseHas('posts', ['title' => 'Test Post', 'user_id' => $this->user->id]);
        $this->assertDatabaseMissing('posts', ['user_id' => $other->id]);
    }

    public function test_html_in_title_is_escaped_on_display()
    {
        // XSS対策：保存はそのまま、表示時にエスケープされる
        $this->actingAs($this->user)->post('/posts', $this->validData(['title' => '<script>alert(1)</script>']));
        $post = Post::first();

        $this->get("/posts/{$post->id}")
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_multibyte_title_is_saved_correctly()
    {
        $title = '日本語のタイトル🍣';

        $this->actingAs($this->user)->post('/posts', $this->validData(['title' => $title]))->assertRedirect();

        $this->assertDatabaseHas('posts', ['title' => $title]);
    }

    // ===== Update =====

    public function test_owner_can_view_edit_form()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->get("/posts/{$post->id}/edit")
            ->assertOk()
            ->assertViewIs('posts.edit')
            ->assertViewHas('post', fn ($p) => $p->is($post));
    }

    public function test_other_user_cannot_view_edit_form()
    {
        $post = Post::factory()->create();

        $this->actingAs($this->user)->get("/posts/{$post->id}/edit")->assertForbidden();
    }

    public function test_edit_returns_404_for_missing_post()
    {
        $this->actingAs($this->user)->get('/posts/99999/edit')->assertNotFound();
    }

    public function test_owner_can_update_post()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id, 'title' => '元のタイトル']);
        $newCategory = Category::factory()->create();

        $response = $this->actingAs($this->user)->put("/posts/{$post->id}", $this->validData([
            'title' => '新しいタイトル',
            'content' => '新しい本文',
            'category_id' => $newCategory->id,
        ]));

        $response->assertRedirect(route('posts.show', $post));
        $response->assertSessionHas('success', '投稿を更新しました');
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => '新しいタイトル',
            'content' => '新しい本文',
            'category_id' => $newCategory->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_other_user_cannot_update_post()
    {
        $post = Post::factory()->create(['title' => '元のタイトル']);

        $this->actingAs($this->user)
            ->put("/posts/{$post->id}", $this->validData(['title' => '乗っ取り']))
            ->assertForbidden();

        $this->assertSame('元のタイトル', $post->fresh()->title);
    }

    public function test_guest_cannot_update_post()
    {
        $post = Post::factory()->create(['title' => '元のタイトル']);

        $this->put("/posts/{$post->id}", $this->validData(['title' => '乗っ取り']))->assertRedirect('/login');

        $this->assertSame('元のタイトル', $post->fresh()->title);
    }

    public function test_update_cannot_change_owner()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $other = User::factory()->create();

        $this->actingAs($this->user)->put("/posts/{$post->id}", $this->validData(['user_id' => $other->id]));

        $this->assertSame($this->user->id, $post->fresh()->user_id);
    }

    public function test_update_with_invalid_data_keeps_original()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id, 'title' => '元のタイトル']);

        $this->actingAs($this->user)
            ->put("/posts/{$post->id}", $this->validData(['title' => '']))
            ->assertSessionHasErrors('title');

        $this->assertSame('元のタイトル', $post->fresh()->title);
    }

    public function test_post_without_owner_cannot_be_edited_by_anyone()
    {
        // ログイン機能導入前の投稿（user_id = null）は誰も編集できない
        $orphan = Post::factory()->create(['user_id' => null]);

        $this->actingAs($this->user)->get("/posts/{$orphan->id}/edit")->assertForbidden();
        $this->actingAs($this->user)->put("/posts/{$orphan->id}", $this->validData())->assertForbidden();
    }

    // ===== Delete =====

    public function test_owner_can_delete_post()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->delete("/posts/{$post->id}")
            ->assertRedirect(route('posts.index'))
            ->assertSessionHas('success', '投稿を削除しました');

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_deleting_post_also_deletes_its_comments()
    {
        $post = Post::factory()->create(['user_id' => $this->user->id]);
        $comment = new Comment(['body' => '消えるコメント']);
        $comment->post_id = $post->id;
        $comment->user_id = $this->user->id;
        $comment->save();

        $this->actingAs($this->user)->delete("/posts/{$post->id}");

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_other_user_cannot_delete_post()
    {
        $post = Post::factory()->create();

        $this->actingAs($this->user)->delete("/posts/{$post->id}")->assertForbidden();

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_guest_cannot_delete_post()
    {
        $post = Post::factory()->create();

        $this->delete("/posts/{$post->id}")->assertRedirect('/login');

        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_delete_returns_404_for_missing_post()
    {
        $this->actingAs($this->user)->delete('/posts/99999')->assertNotFound();
    }

    // ===== バリデーション（代表例。詳細は PostValidationTest） =====

    public function test_post_validation()
    {
        $response = $this->actingAs($this->user)->post('/posts', $this->validData([
            'title' => '',  // タイトル必須
        ]));

        $response->assertSessionHasErrors(['title' => 'タイトルを入力してください']);
        $this->assertDatabaseCount('posts', 0);
    }
}
