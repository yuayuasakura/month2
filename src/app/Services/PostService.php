<?php

namespace App\Services;

use App\Models\Post;
use App\Repositories\CategoryRepository;
use App\Repositories\PostRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

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

    // 投稿画像の保存先（S3 の images フォルダ）
    private const IMAGE_DISK = 's3';
    private const IMAGE_DIR = 'images';

    // $data['image'] に画像（UploadedFile）があれば S3 に保存して、そのパスを投稿に記録する
    // 画面からの投稿（PostController）も API からの投稿（createApiPost）もこのメソッドを通る
    public function createPost(array $data, int $userId)
    {
        // 1. 画像を S3 に保存（image は posts テーブルの列ではないので $data から取り出す）
        $image = $data['image'] ?? null;
        unset($data['image']);
        $data['image_path'] = $image ? $this->storeImage($image) : null;

        try {
            // DB::transaction(): この中の処理が全て成功すれば確定、途中でエラーが起きたら全て取り消す
            return DB::transaction(function () use ($data, $userId) {
                // 2. 投稿作成（投稿者はログインユーザー。フォームから送られた user_id は使わない）
                $post = $this->postRepository->create($data, $userId);

                // 3. ログ記録
                Log::info('Post created', ['post_id' => $post->id]);

                // 処理が増えた場合の例
                //   投稿者にメールで通知する → メール送信の処理をここに追加
                // 処理が増えても、コントローラーは createPost() を呼ぶだけで変わらない

                return $post;
            });
        } catch (Throwable $e) {
            // DB の保存に失敗したら、先に S3 に上げた画像を消す
            // （DB::transaction で取り消せるのは DB だけで、S3 のファイルは残ってしまうため）
            $this->deleteImage($data['image_path']);
            throw $e;
        }
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
    // 画像が送られてきたら新しい画像に差し替える（送られてこなければ今の画像のまま）
    public function updatePost(Post $post, array $data)
    {
        $image = $data['image'] ?? null;
        unset($data['image']);

        $oldImagePath = $post->image_path;
        if ($image) {
            $data['image_path'] = $this->storeImage($image);
        }

        try {
            $updated = DB::transaction(function () use ($post, $data) {
                $updated = $this->postRepository->update($post, $data);

                Log::info('Post updated', ['post_id' => $updated->id]);

                return $updated;
            });
        } catch (Throwable $e) {
            // 保存に失敗したら、新しくアップロードした画像を消す（元の画像は残す）
            $this->deleteImage($data['image_path'] ?? null);
            throw $e;
        }

        // DB の更新が成功してから古い画像を消す（先に消すと、失敗したときに画像が無くなるため）
        if ($image) {
            $this->deleteImage($oldImagePath);
        }

        return $updated;
    }

    public function deletePost(Post $post)
    {
        // コメントは外部キーの cascadeOnDelete で自動的に消える
        $this->postRepository->delete($post);

        // 投稿を消したら、S3 の画像も消す（残すと誰からも参照されないファイルが溜まっていく）
        $this->deleteImage($post->image_path);

        Log::info('Post deleted', ['post_id' => $post->id]);
    }

    // 画像を S3 に保存して、保存先のパスを返す（ファイル名は Laravel が重複しない名前をつける）
    private function storeImage(UploadedFile $image): string
    {
        return Storage::disk(self::IMAGE_DISK)->put(self::IMAGE_DIR, $image);
    }

    private function deleteImage(?string $path): void
    {
        if ($path) {
            Storage::disk(self::IMAGE_DISK)->delete($path);
        }
    }
}
