<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// 商品の作成・更新で共通して使うバリデーション
class ProductRequest extends FormRequest
{
    // 誰でもリクエストできる（ログイン機能を入れたらここで権限チェックする）
    public function authorize(): bool
    {
        return true;
    }

    // バリデーションルール
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:0', 'max:10000000'],
            'description' => ['nullable', 'string', 'max:2000'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'category' => ['required', 'string', 'max:50'],
        ];
    }

    // エラーメッセージ
    public function messages(): array
    {
        return [
            'name.required' => '商品名を入力してください',
            'name.max' => '商品名は100文字以内で入力してください',
            'price.required' => '価格を入力してください',
            'price.integer' => '価格は整数で入力してください',
            'price.min' => '価格は0円以上で入力してください',
            'price.max' => '価格は10,000,000円以下で入力してください',
            'description.max' => '説明は2000文字以内で入力してください',
            'stock.required' => '在庫数を入力してください',
            'stock.integer' => '在庫数は整数で入力してください',
            'stock.min' => '在庫数は0以上で入力してください',
            'stock.max' => '在庫数は1,000,000以下で入力してください',
            'category.required' => 'カテゴリーを入力してください',
            'category.max' => 'カテゴリーは50文字以内で入力してください',
        ];
    }
}
