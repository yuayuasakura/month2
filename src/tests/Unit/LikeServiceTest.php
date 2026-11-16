<?php

namespace Tests\Unit;

use App\Models\Post;
use App\Models\User;
use App\Services\LikeService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// LikeService（いいね）のユニットテスト
//   like()   : いいねする。同じ人が何度押しても1件だけ。戻り値はその投稿のいいね数
//   unlike() : いいねを取り消す。していなくてもエラーにしない。戻り値はその投稿のいいね数
class LikeServiceTest extends TestCase
{
    use RefreshDatabase;

    private LikeService $service;
    private User $user;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LikeService::class);
        $this->user = User::factory()->create();
        $this->post = Post::factory()->create();
    }

    // ===== 正常系 =====

    public function test_like_adds_like_and_returns_count()
    {
        $count = $this->service->like($this->post->id, $this->user->id);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('likes', ['post_id' => $this->post->id, 'user_id' => $this->user->id]);
    }

    public function test_likes_from_different_users_are_counted()
    {
        $this->service->like($this->post->id, $this->user->id);
        $count = $this->service->like($this->post->id, User::factory()->create()->id);

        $this->assertSame(2, $count);
    }

    public function test_unlike_removes_like_and_returns_count()
    {
        $this->service->like($this->post->id, $this->user->id);

        $count = $this->service->unlike($this->post->id, $this->user->id);

        $this->assertSame(0, $count);
        $this->assertDatabaseMissing('likes', ['post_id' => $this->post->id, 'user_id' => $this->user->id]);
    }

    public function test_unlike_removes_only_own_like()
    {
        $other = User::factory()->create();
        $this->service->like($this->post->id, $this->user->id);
        $this->service->like($this->post->id, $other->id);

        $count = $this->service->unlike($this->post->id, $this->user->id);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('likes', ['post_id' => $this->post->id, 'user_id' => $other->id]);
    }

    public function test_likes_are_counted_per_post()
    {
        $otherPost = Post::factory()->create();
        $this->service->like($otherPost->id, $this->user->id);

        $this->assertSame(1, $this->service->like($this->post->id, $this->user->id));
        $this->assertSame(1, $otherPost->likes()->count());
    }

    // ===== 異常系 =====

    public function test_like_missing_post_throws_not_found()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->like(99999, $this->user->id);
    }

    public function test_unlike_missing_post_throws_not_found()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->unlike(99999, $this->user->id);
    }

    public function test_like_with_missing_user_throws_exception()
    {
        $this->expectException(QueryException::class);

        $this->service->like($this->post->id, 99999);
    }

    // ===== 境界値 =====

    public function test_liking_twice_counts_once()
    {
        // 同じ人の2回目のいいねは増えない（1件が上限）
        $this->service->like($this->post->id, $this->user->id);
        $count = $this->service->like($this->post->id, $this->user->id);

        $this->assertSame(1, $count);
        $this->assertDatabaseCount('likes', 1);
    }

    public function test_unlike_without_like_stays_zero()
    {
        // 0件のときに取り消してもマイナスにならず、エラーにもならない（0件が下限）
        $this->assertSame(0, $this->service->unlike($this->post->id, $this->user->id));
    }

    public function test_like_unlike_like_again()
    {
        // 0 → 1 → 0 → 1 と行き来できる
        $this->assertSame(1, $this->service->like($this->post->id, $this->user->id));
        $this->assertSame(0, $this->service->unlike($this->post->id, $this->user->id));
        $this->assertSame(1, $this->service->like($this->post->id, $this->user->id));
    }

    // ===== Post モデルの判定メソッド =====

    public function test_post_is_liked_by()
    {
        $this->service->like($this->post->id, $this->user->id);

        $this->assertTrue($this->post->isLikedBy($this->user));
        $this->assertFalse($this->post->isLikedBy(User::factory()->create()));
        // 未ログイン（null）は常に false
        $this->assertFalse($this->post->isLikedBy(null));
    }

    public function test_like_relations()
    {
        $this->service->like($this->post->id, $this->user->id);
        $like = $this->user->likes()->first();

        $this->assertTrue($like->post->is($this->post));
        $this->assertTrue($like->user->is($this->user));
    }
}
