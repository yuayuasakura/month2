<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// PostRequest のバリデーションルールをテストする
//   title       : required / string / max:200
//   content     : required / string / max:10000
//   category_id : required / integer / exists:categories,id
//   image       : nullable / image / max:2048(KB)
class PostValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create();
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'タイトル',
            'content' => '本文',
            'category_id' => $this->category->id,
        ], $overrides);
    }

    // ===== 不正な値 → エラーになる =====

    public static function invalidDataProvider(): array
    {
        return [
            'タイトルが空' => ['title', '', 'タイトルを入力してください'],
            'タイトルがnull' => ['title', null, 'タイトルを入力してください'],
            'タイトルが201文字' => ['title', str_repeat('あ', 201), 'タイトルは200文字以内で入力してください'],
            'タイトルが配列' => ['title', ['a'], null],
            '内容が空' => ['content', '', '内容を入力してください'],
            '内容が10001文字' => ['content', str_repeat('a', 10001), '内容は10000文字以内で入力してください'],
            'カテゴリー未選択' => ['category_id', '', 'カテゴリーを選択してください'],
            'カテゴリーが文字列' => ['category_id', 'abc', '正しいカテゴリーを選択してください'],
            'カテゴリーが小数' => ['category_id', '1.5', '正しいカテゴリーを選択してください'],
            'カテゴリーが存在しない' => ['category_id', 99999, '選択されたカテゴリーは存在しません'],
        ];
    }

    #[DataProvider('invalidDataProvider')]
    public function test_invalid_data_is_rejected(string $field, mixed $value, ?string $message)
    {
        $response = $this->actingAs($this->user)
            ->from('/posts/create')
            ->post('/posts', $this->validData([$field => $value]));

        // 入力画面に戻され、エラーメッセージが表示される
        $response->assertRedirect('/posts/create');
        $response->assertSessionHasErrors($message ? [$field => $message] : [$field]);
        $this->assertDatabaseCount('posts', 0);
    }

    public function test_all_required_fields_missing()
    {
        $this->actingAs($this->user)
            ->post('/posts', [])
            ->assertSessionHasErrors(['title', 'content', 'category_id']);
    }

    public function test_old_input_is_kept_after_validation_error()
    {
        // エラーで戻ったとき、入力した値がフォームに残る
        $this->actingAs($this->user)
            ->post('/posts', $this->validData(['title' => '', 'content' => '残したい本文']))
            ->assertSessionHasInput('content', '残したい本文');
    }

    // ===== 境界値 → ちょうど上限なら通る =====

    public function test_title_with_exactly_200_chars_is_accepted()
    {
        $title = str_repeat('あ', 200);

        $this->actingAs($this->user)
            ->post('/posts', $this->validData(['title' => $title]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('posts', ['title' => $title]);
    }

    public function test_title_with_1_char_is_accepted()
    {
        $this->actingAs($this->user)
            ->post('/posts', $this->validData(['title' => 'a']))
            ->assertSessionHasNoErrors();
    }

    public function test_content_with_exactly_10000_chars_is_accepted()
    {
        $this->actingAs($this->user)
            ->post('/posts', $this->validData(['content' => str_repeat('a', 10000)]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('posts', 1);
    }

    public function test_whitespace_only_title_is_rejected()
    {
        // TrimStrings ミドルウェアで空白が除去され、空文字扱い（→ required エラー）になる
        $this->actingAs($this->user)
            ->post('/posts', $this->validData(['title' => '   ']))
            ->assertSessionHasErrors('title');
    }

    // ===== 画像 =====

    public function test_image_is_optional()
    {
        $this->actingAs($this->user)
            ->post('/posts', $this->validData())
            ->assertSessionHasNoErrors();
    }

    public function test_non_image_file_is_rejected()
    {
        $this->actingAs($this->user)
            ->post('/posts', $this->validData(['image' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')]))
            ->assertSessionHasErrors('image');
    }

    public function test_image_at_2048kb_is_accepted()
    {
        $this->actingAs($this->user)
            ->post('/posts', $this->validData(['image' => UploadedFile::fake()->image('ok.png')->size(2048)]))
            ->assertSessionHasNoErrors();
    }

    public function test_image_over_2048kb_is_rejected()
    {
        $this->actingAs($this->user)
            ->post('/posts', $this->validData(['image' => UploadedFile::fake()->image('big.png')->size(2049)]))
            ->assertSessionHasErrors('image');

        $this->assertDatabaseCount('posts', 0);
    }

    // ===== 更新時も同じルール =====

    public function test_update_uses_same_validation_rules()
    {
        $post = \App\Models\Post::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->put("/posts/{$post->id}", ['title' => str_repeat('a', 201), 'content' => '', 'category_id' => 99999])
            ->assertSessionHasErrors(['title', 'content', 'category_id']);
    }
}
