<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

// 商品管理（/products）の CRUD とバリデーション
class ProductTest extends TestCase
{
    use RefreshDatabase;

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'テスト商品',
            'price' => 1000,
            'description' => '説明文',
            'stock' => 5,
            'category' => '食品',
        ], $overrides);
    }

    private function createProduct(array $overrides = []): Product
    {
        return Product::create($this->validData($overrides));
    }

    // ===== Read =====

    public function test_index_lists_products()
    {
        $this->createProduct(['name' => '商品A']);
        $this->createProduct(['name' => '商品B']);

        $this->get('/products')
            ->assertOk()
            ->assertViewIs('products.index')
            ->assertSee('商品A')
            ->assertSee('商品B');
    }

    public function test_index_is_paginated_by_10()
    {
        for ($i = 0; $i < 11; $i++) {
            $this->createProduct(['name' => "商品{$i}"]);
        }

        $this->get('/products')->assertViewHas('products', fn ($p) => $p->count() === 10 && $p->total() === 11);
    }

    public function test_show_displays_product()
    {
        $product = $this->createProduct(['name' => '詳細商品']);

        $this->get("/products/{$product->id}")
            ->assertOk()
            ->assertViewIs('products.show')
            ->assertSee('詳細商品');
    }

    public function test_show_returns_404_for_missing_product()
    {
        $this->get('/products/99999')->assertNotFound();
    }

    // ===== Create =====

    public function test_create_form_shows_existing_categories_without_duplicates()
    {
        $this->createProduct(['category' => '食品']);
        $this->createProduct(['category' => '食品']);
        $this->createProduct(['category' => '雑貨']);

        $this->get('/products/create')
            ->assertOk()
            ->assertViewHas('categories', fn ($c) => $c->values()->all() === ['雑貨', '食品'] || $c->values()->all() === ['食品', '雑貨']);
    }

    public function test_store_creates_product()
    {
        $response = $this->post('/products', $this->validData());

        $product = Product::first();
        $response->assertRedirect(route('products.show', $product));
        $response->assertSessionHas('success', '商品を登録しました');
        $this->assertDatabaseHas('products', ['name' => 'テスト商品', 'price' => 1000, 'stock' => 5]);
    }

    public function test_store_without_description_is_allowed()
    {
        $this->post('/products', $this->validData(['description' => null]))->assertSessionHasNoErrors();

        $this->assertNull(Product::first()->description);
    }

    // ===== Update =====

    public function test_edit_form_is_displayed()
    {
        $product = $this->createProduct();

        $this->get("/products/{$product->id}/edit")
            ->assertOk()
            ->assertViewIs('products.edit')
            ->assertViewHas('product', fn ($p) => $p->is($product));
    }

    public function test_update_changes_product()
    {
        $product = $this->createProduct();

        $this->put("/products/{$product->id}", $this->validData(['name' => '新しい名前', 'price' => 2000]))
            ->assertRedirect(route('products.show', $product))
            ->assertSessionHas('success', '商品を更新しました');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => '新しい名前', 'price' => 2000]);
    }

    public function test_update_with_invalid_data_keeps_original()
    {
        $product = $this->createProduct(['price' => 1000]);

        $this->put("/products/{$product->id}", $this->validData(['price' => -1]))->assertSessionHasErrors('price');

        $this->assertSame(1000, $product->fresh()->price);
    }

    // ===== Delete =====

    public function test_destroy_deletes_product()
    {
        $product = $this->createProduct();

        $this->delete("/products/{$product->id}")
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('success', '商品を削除しました');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_destroy_returns_404_for_missing_product()
    {
        $this->delete('/products/99999')->assertNotFound();
    }

    // ===== バリデーション =====

    public static function invalidProvider(): array
    {
        return [
            '商品名が空' => ['name', '', '商品名を入力してください'],
            '商品名が101文字' => ['name', str_repeat('a', 101), '商品名は100文字以内で入力してください'],
            '価格が空' => ['price', '', '価格を入力してください'],
            '価格が文字列' => ['price', 'abc', '価格は整数で入力してください'],
            '価格が小数' => ['price', '10.5', '価格は整数で入力してください'],
            '価格がマイナス' => ['price', -1, '価格は0円以上で入力してください'],
            '価格が上限超え' => ['price', 10000001, '価格は10,000,000円以下で入力してください'],
            '説明が2001文字' => ['description', str_repeat('a', 2001), '説明は2000文字以内で入力してください'],
            '在庫が空' => ['stock', '', '在庫数を入力してください'],
            '在庫がマイナス' => ['stock', -1, '在庫数は0以上で入力してください'],
            '在庫が上限超え' => ['stock', 1000001, '在庫数は1,000,000以下で入力してください'],
            'カテゴリーが空' => ['category', '', 'カテゴリーを入力してください'],
            'カテゴリーが51文字' => ['category', str_repeat('a', 51), 'カテゴリーは50文字以内で入力してください'],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function test_invalid_data_is_rejected(string $field, mixed $value, string $message)
    {
        $this->post('/products', $this->validData([$field => $value]))
            ->assertSessionHasErrors([$field => $message]);

        $this->assertDatabaseCount('products', 0);
    }

    public static function boundaryProvider(): array
    {
        return [
            '価格0円' => ['price', 0],
            '価格が上限ちょうど' => ['price', 10000000],
            '在庫0' => ['stock', 0],
            '在庫が上限ちょうど' => ['stock', 1000000],
            '商品名100文字' => ['name', str_repeat('a', 100)],
            'カテゴリー50文字' => ['category', str_repeat('a', 50)],
        ];
    }

    #[DataProvider('boundaryProvider')]
    public function test_boundary_values_are_accepted(string $field, mixed $value)
    {
        $this->post('/products', $this->validData([$field => $value]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', [$field => $value]);
    }
}
