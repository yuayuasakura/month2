<?php

namespace Tests\Unit;

use App\Models\Post;
use App\Models\User;
use App\Policies\PostPolicy;
use PHPUnit\Framework\TestCase;

// PostPolicy の判定ロジックだけをテストする（DBは使わない）
class PostPolicyTest extends TestCase
{
    private PostPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new PostPolicy();
    }

    // DBに保存せず、id だけ持ったモデルを作る
    private function user(int $id): User
    {
        $user = new User();
        $user->id = $id;
        return $user;
    }

    private function post(?int $userId): Post
    {
        $post = new Post();
        $post->user_id = $userId;
        return $post;
    }

    public function test_owner_can_update()
    {
        $this->assertTrue($this->policy->update($this->user(1), $this->post(1)));
    }

    public function test_other_user_cannot_update()
    {
        $this->assertFalse($this->policy->update($this->user(2), $this->post(1)));
    }

    public function test_owner_can_delete()
    {
        $this->assertTrue($this->policy->delete($this->user(1), $this->post(1)));
    }

    public function test_other_user_cannot_delete()
    {
        $this->assertFalse($this->policy->delete($this->user(2), $this->post(1)));
    }

    public function test_post_without_owner_cannot_be_updated_or_deleted()
    {
        // 投稿者不明（user_id = null）の投稿は誰も操作できない
        $orphan = $this->post(null);

        $this->assertFalse($this->policy->update($this->user(1), $orphan));
        $this->assertFalse($this->policy->delete($this->user(1), $orphan));
    }
}
