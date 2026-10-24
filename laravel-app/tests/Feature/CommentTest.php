<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    private User $author;
    private User $other;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CategorySeeder::class);
        $this->author = User::factory()->create(['name' => '花子']);
        $this->other = User::factory()->create(['name' => '太郎']);
        $this->post = $this->author->posts()->create([
            'title' => 'テスト投稿', 'content' => '本文', 'category_id' => Category::first()->id,
        ]);
    }

    private function addComment(User $user, string $body): Comment
    {
        $comment = $this->post->comments()->make(['body' => $body]);
        $comment->user()->associate($user);
        $comment->save();

        return $comment;
    }

    public function test_guest_can_read_posts_and_comments(): void
    {
        $this->addComment($this->other, "太郎のコメント\n2行目");

        $this->get('/posts')->assertOk()->assertSee('テスト投稿')->assertSee('コメント 1件');
        $this->get("/posts/{$this->post->id}")->assertOk()
            ->assertSee('コメント（1件）')
            ->assertSee('太郎のコメント<br />', false)
            ->assertSee('ログイン</a>してください', false)
            ->assertDontSee('コメントする</button>', false)
            ->assertDontSee('このコメントを削除しますか');
    }

    public function test_guest_cannot_create_post_or_comment(): void
    {
        $this->get('/posts/create')->assertRedirect('/login');
        $this->post("/posts/{$this->post->id}/comments", ['body' => 'ゲスト'])->assertRedirect('/login');
        $this->assertSame(0, Comment::count());
    }

    public function test_logged_in_user_can_comment(): void
    {
        $this->actingAs($this->other)->get("/posts/{$this->post->id}")->assertSee('コメントする</button>', false);

        $this->actingAs($this->other)
            ->post("/posts/{$this->post->id}/comments", ['body' => 'いい記事ですね', 'user_id' => $this->author->id, 'post_id' => 999])
            ->assertRedirect("/posts/{$this->post->id}#comments");

        $comment = Comment::first();
        $this->assertSame('いい記事ですね', $comment->body);
        $this->assertSame($this->other->id, $comment->user_id);   // user_id は偽装できない
        $this->assertSame($this->post->id, $comment->post_id);    // post_id も URL のものが使われる
    }

    public function test_comment_validation_and_xss(): void
    {
        $this->actingAs($this->other)->post("/posts/{$this->post->id}/comments", ['body' => ''])->assertSessionHasErrors('body');
        $this->actingAs($this->other)->post("/posts/{$this->post->id}/comments", ['body' => str_repeat('あ', 1001)])->assertSessionHasErrors('body');

        $this->actingAs($this->other)->post("/posts/{$this->post->id}/comments", ['body' => '<script>alert(1)</script>']);
        $this->get("/posts/{$this->post->id}")->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
    }

    public function test_only_owner_can_delete_comment(): void
    {
        $mine = $this->addComment($this->other, '太郎のコメント');

        // 投稿者本人（花子）でも他人のコメントは消せない
        $this->actingAs($this->author)->get("/posts/{$this->post->id}")->assertDontSee('このコメントを削除しますか');
        $this->actingAs($this->author)->delete("/comments/{$mine->id}")->assertForbidden();
        $this->assertNotNull($mine->fresh());

        // ゲストはログイン画面へ
        auth()->logout();
        $this->delete("/comments/{$mine->id}")->assertRedirect('/login');

        // 本人は消せる
        $this->actingAs($this->other)->get("/posts/{$this->post->id}")->assertSee('このコメントを削除しますか');
        $this->actingAs($this->other)->delete("/comments/{$mine->id}")->assertRedirect("/posts/{$this->post->id}#comments");
        $this->assertNull($mine->fresh());
    }

    public function test_comments_are_deleted_with_post(): void
    {
        $this->addComment($this->other, 'a');
        $this->actingAs($this->author)->delete("/posts/{$this->post->id}")->assertRedirect('/posts');
        $this->assertSame(0, Comment::count());
    }
}
