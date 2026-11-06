<?php

namespace App\Services;

use App\Models\Post;
use App\Repositories\CategoryRepository;
use App\Repositories\PostRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PostService
{
    // コンストラクタインジェクション（DIパターン、次のSection 4で詳しく説明）
    // 「private PostRepository $postRepository」はPHP 8のコンストラクタプロモーションという書き方
    // 従来の書き方と同じ意味：
    //   private PostRepository $postRepository;
    //   public function __construct(PostRepository $postRepository) {
    //       $this->postRepository = $postRepository;
    //   }
    public function __construct(
        private PostRepository $postRepository,
        private UserRepository $userRepository,
        private CategoryRepository $categoryRepository
    ) {}

    // API の投稿者・カテゴリー（今週の API は認証なしなので固定）
    private const API_USER_EMAIL = 'test@example.com';
    private const API_CATEGORY_NAME = 'その他';

    public function createPost(array $data, int $userId)
    {
        // DB::transaction(): この中の処理が全て成功すれば確定、途中でエラーが起きたら全て取り消す
        return DB::transaction(function () use ($data, $userId) {
            // 1. 投稿作成（投稿者はログインユーザー。フォームから送られた user_id は使わない）
            $post = $this->postRepository->create($data, $userId);

            // 2. ログ記録
            Log::info('Post created', ['post_id' => $post->id]);

            // 処理が増えた場合の例（Week 7・8 のブログには画像やメール通知はないので、今は書かなくてOK）
            //   画像を保存する → 画像を保存して、そのパスを投稿に記録する処理をここに追加
            //   投稿者にメールで通知する → メール送信の処理をここに追加
            // 処理が増えても、コントローラーは createPost() を呼ぶだけで変わらない

            return $post;
        });
    }

    // API（Vue.js のフォーム）からの投稿作成
    // Seeder で作ったテスト用ユーザーの投稿として、「その他」カテゴリーで保存する
    public function createApiPost(array $data)
    {
        $user = $this->userRepository->findByEmail(self::API_USER_EMAIL);
        $data['category_id'] = $this->categoryRepository->firstOrCreateByName(self::API_CATEGORY_NAME)->id;

        // 保存処理そのものは画面からの投稿と同じ createPost() を使う
        // レスポンスで投稿者名を返すので、user も読み込んでおく
        return $this->createPost($data, $user->id)->load('user');
    }

    // 権限チェック（Gate）はコントローラーで済ませてから呼ぶので、取得済みの Post を受け取る
    public function updatePost(Post $post, array $data)
    {
        return DB::transaction(function () use ($post, $data) {
            $updated = $this->postRepository->update($post, $data);

            Log::info('Post updated', ['post_id' => $updated->id]);

            return $updated;
        });
    }

    public function deletePost(Post $post)
    {
        // コメントは外部キーの cascadeOnDelete で自動的に消える
        $this->postRepository->delete($post);

        Log::info('Post deleted', ['post_id' => $post->id]);
    }
}
