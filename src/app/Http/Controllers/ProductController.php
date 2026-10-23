<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;

class ProductController extends Controller
{
    // 一覧表示
    public function index()
    {
        $products = Product::latest()->paginate(10);
        return view('products.index', compact('products'));
    }

    // 作成フォーム表示
    public function create()
    {
        $categories = $this->existingCategories();
        return view('products.create', compact('categories'));
    }

    // データ保存（バリデーションは ProductRequest で行う）
    public function store(ProductRequest $request)
    {
        $product = Product::create($request->validated());

        return redirect()
            ->route('products.show', $product)
            ->with('success', '商品を登録しました');
    }

    // 詳細表示（ルートモデルバインディング：{product} のIDから自動で取得、なければ404）
    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    // 編集フォーム表示
    public function edit(Product $product)
    {
        $categories = $this->existingCategories();
        return view('products.edit', compact('product', 'categories'));
    }

    // データ更新
    public function update(ProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return redirect()
            ->route('products.show', $product)
            ->with('success', '商品を更新しました');
    }

    // データ削除
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', '商品を削除しました');
    }

    // 登録済みのカテゴリー名一覧（フォームの入力候補に使う）
    private function existingCategories()
    {
        return Product::query()->distinct()->orderBy('category')->pluck('category');
    }
}
