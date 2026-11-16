<?php

namespace Tests\Unit;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\CommentService;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// CommentService のユニットテスト
class CommentServiceTest extends TestCase
{
    use RefreshDatabase;

    private CommentService $service;
    private User $user;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CommentService::class);
        $this->user = User::factory()->create();
        $this->post = Post::factory()->create();
    }

    // ===== createComment：正常系 =====

    public function test_create_comment_saves_comment_for_post_and_user()
    {
        $comment = $this->service->createComment($this->post->id, ['body' => 'いい記事です'], $this->user->id);

        $this->assertInstanceOf(Comment::class, $comment);
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'post_id' => $this->post->id,
            'user_id' => $this->user->id,
            'body' => 'いい記事です',
        ]);
    }

    public function test_create_comment_rejects_post_id_and_user_id_in_data()
    {
        // post_id・user_id は $fillable 外。開発環境では黙って無視されず例外になる
        $otherPost = Post::factory()->create();
        $otherUser = User::factory()->create();

        try {
            $this->service->createComment(
                $this->post->id,
                ['body' => 'x', 'post_id' => $otherPost->id, 'user_id' => $otherUser->id],
                $this->user->id
            );
            $this->fail('例外が投げられるはず');
        } catch (MassAssignmentException) {
            // 期待どおり
        }

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_create_comment_writes_log()
    {
        Log::spy();

        $comment = $this->service->createComment($this->post->id, ['body' => 'x'], $this->user->id);

        Log::shouldHaveReceived('info')
            ->with('Comment created', ['comment_id' => $comment->id, 'post_id' => $this->post->id])
            ->once();
    }

    // ===== createComment：異常系 =====

    public function test_create_comment_on_missing_post_throws_not_found()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->createComment(99999, ['body' => 'x'], $this->user->id);
    }

    public function test_create_comment_with_missing_user_throws_exception()
    {
        $this->expectException(QueryException::class);

        $this->service->createComment($this->post->id, ['body' => 'x'], 99999);
    }

    public function test_create_comment_without_body_throws_exception()
    {
        // comments.body は NOT NULL
        $this->expectException(QueryException::class);

        $this->service->createComment($this->post->id, [], $this->user->id);
    }

    // ===== createComment：境界値（投稿IDの範囲） =====

    public static function invalidPostIdProvider(): array
    {
        return [
            'ID 0' => [0],
            'ID -1' => [-1],
        ];
    }

    #[DataProvider('invalidPostIdProvider')]
    public function test_create_comment_with_out_of_range_post_id_throws_not_found(int $postId)
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->createComment($postId, ['body' => 'x'], $this->user->id);
    }

    public function test_create_comment_on_last_post_id_succeeds_and_next_id_fails()
    {
        // 存在する最後のID → 成功、その次のID → 404
        $lastId = Post::max('id');

        $this->assertNotNull($this->service->createComment($lastId, ['body' => 'x'], $this->user->id));

        $this->expectException(ModelNotFoundException::class);
        $this->service->createComment($lastId + 1, ['body' => 'x'], $this->user->id);
    }

    public function test_create_comment_with_1_char_and_long_body()
    {
        // 1文字（下限）と 1000文字（CommentRequest の上限想定）のどちらも保存できる
        $short = $this->service->createComment($this->post->id, ['body' => 'a'], $this->user->id);
        $long = $this->service->createComment($this->post->id, ['body' => str_repeat('あ', 1000)], $this->user->id);

        $this->assertSame('a', $short->fresh()->body);
        $this->assertSame(1000, mb_strlen($long->fresh()->body));
    }

    // ===== deleteComment =====

    public function test_delete_comment_removes_only_that_comment()
    {
        $target = $this->service->createComment($this->post->id, ['body' => '消す'], $this->user->id);
        $keep = $this->service->createComment($this->post->id, ['body' => '残す'], $this->user->id);

        $this->service->deleteComment($target);

        $this->assertDatabaseMissing('comments', ['id' => $target->id]);
        $this->assertDatabaseHas('comments', ['id' => $keep->id]);
        // 投稿は消えない
        $this->assertDatabaseHas('posts', ['id' => $this->post->id]);
    }

    public function test_delete_comment_writes_log()
    {
        $comment = $this->service->createComment($this->post->id, ['body' => 'x'], $this->user->id);
        Log::spy();

        $this->service->deleteComment($comment);

        Log::shouldHaveReceived('info')
            ->with('Comment deleted', ['comment_id' => $comment->id, 'post_id' => $this->post->id])
            ->once();
    }
}
