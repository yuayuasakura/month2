<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// いいね機能の Feature Test
//   POST   /posts/{post}/like : いいねする
//   DELETE /posts/{post}/like : いいねを取り消す
class LikeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->post = Post::factory()->create();
    }

    // ===== いいねする =====

    public function test_user_can_like_post()
    {
        $response = $this->actingAs($this->user)->post("/posts/{$this->post->id}/like");

        $response->assertRedirect(route('posts.show', $this->post) . '#like');
        $this->assertDatabaseHas('likes', ['post_id' => $this->post->id, 'user_id' => $this->user->id]);
        $this->assertSame(1, $this->post->likes()->count());
    }

    public function test_guest_cannot_like_post()
    {
        $this->post("/posts/{$this->post->id}/like")->assertRedirect('/login');

        $this->assertDatabaseCount('likes', 0);
    }

    public function test_liking_twice_counts_once()
    {
        $this->actingAs($this->user)->post("/posts/{$this->post->id}/like");
        $this->actingAs($this->user)->post("/posts/{$this->post->id}/like");

        $this->assertSame(1, $this->post->likes()->count());
    }

    public function test_multiple_users_likes_are_counted()
    {
        $this->actingAs($this->user)->post("/posts/{$this->post->id}/like");
        $this->actingAs(User::factory()->create())->post("/posts/{$this->post->id}/like");

        $this->assertSame(2, $this->post->likes()->count());
    }

    public function test_user_can_like_own_post()
    {
        $own = Post::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)->post("/posts/{$own->id}/like")->assertRedirect();

        $this->assertSame(1, $own->likes()->count());
    }

    public function test_like_missing_post_returns_404()
    {
        $this->actingAs($this->user)->post('/posts/99999/like')->assertNotFound();
        $this->actingAs($this->user)->post('/posts/abc/like')->assertNotFound();
    }

    // ===== いいねを取り消す =====

    public function test_user_can_unlike_post()
    {
        $this->actingAs($this->user)->post("/posts/{$this->post->id}/like");

        $response = $this->actingAs($this->user)->delete("/posts/{$this->post->id}/like");

        $response->assertRedirect(route('posts.show', $this->post) . '#like');
        $this->assertSame(0, $this->post->likes()->count());
    }

    public function test_unlike_without_like_does_nothing()
    {
        $this->actingAs($this->user)->delete("/posts/{$this->post->id}/like")->assertRedirect();

        $this->assertSame(0, $this->post->likes()->count());
    }

    public function test_unlike_does_not_remove_other_users_like()
    {
        $other = User::factory()->create();
        $this->actingAs($other)->post("/posts/{$this->post->id}/like");

        $this->actingAs($this->user)->delete("/posts/{$this->post->id}/like");

        $this->assertDatabaseHas('likes', ['post_id' => $this->post->id, 'user_id' => $other->id]);
    }

    public function test_guest_cannot_unlike_post()
    {
        $this->actingAs($this->user)->post("/posts/{$this->post->id}/like");
        auth()->logout();

        $this->delete("/posts/{$this->post->id}/like")->assertRedirect('/login');

        $this->assertSame(1, $this->post->likes()->count());
    }

    // ===== 表示 =====

    public function test_show_page_displays_like_count()
    {
        $this->actingAs(User::factory()->create())->post("/posts/{$this->post->id}/like");
        $this->actingAs(User::factory()->create())->post("/posts/{$this->post->id}/like");
        auth()->logout();

        $this->get("/posts/{$this->post->id}")->assertOk()->assertSee('いいね 2件');
    }

    public function test_show_page_displays_zero_likes()
    {
        $this->get("/posts/{$this->post->id}")->assertOk()->assertSee('いいね 0件');
    }

    public function test_show_page_button_toggles_by_like_state()
    {
        // いいね前は「いいね」ボタン、いいね後は「いいねを取り消す」ボタン
        $this->actingAs($this->user)->get("/posts/{$this->post->id}")
            ->assertSee('>いいね<', false)
            ->assertDontSee('いいねを取り消す');

        $this->actingAs($this->user)->post("/posts/{$this->post->id}/like");

        $this->actingAs($this->user)->get("/posts/{$this->post->id}")
            ->assertSee('いいねを取り消す');
    }

    public function test_guest_sees_count_but_no_like_button()
    {
        $this->get("/posts/{$this->post->id}")
            ->assertSee('いいね 0件')
            ->assertDontSee('>いいね<', false)
            ->assertDontSee('いいねを取り消す');
    }

    public function test_index_page_displays_like_count()
    {
        $this->actingAs($this->user)->post("/posts/{$this->post->id}/like");

        $this->get('/posts')->assertOk()->assertSee('いいね 1件');
    }

    public function test_index_does_not_run_query_per_post_for_like_count()
    {
        // N+1問題の防止：投稿が増えても、いいね数のためにクエリが増えない
        $countQueries = function () {
            \DB::flushQueryLog();
            \DB::enableQueryLog();
            $this->get('/posts')->assertOk();
            return count(\DB::getQueryLog());
        };

        $withOne = $countQueries();
        Post::factory()->count(5)->create();
        $withSix = $countQueries();

        $this->assertSame($withOne, $withSix);
    }

    // ===== 関連データの削除 =====

    public function test_deleting_post_deletes_its_likes()
    {
        $this->actingAs($this->user)->post("/posts/{$this->post->id}/like");

        $this->post->delete();

        $this->assertDatabaseCount('likes', 0);
    }

    public function test_deleting_user_deletes_their_likes()
    {
        $this->actingAs($this->user)->post("/posts/{$this->post->id}/like");

        $this->user->delete();

        $this->assertSame(0, $this->post->likes()->count());
    }
}
