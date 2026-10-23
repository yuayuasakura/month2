<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// 投稿の作成・更新で共通して使うバリデーション
class PostRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:200'],
            'content' => ['required', 'string', 'max:10000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
        ];
    }

    // エラーメッセージ
    public function messages(): array
    {
        return [
            'title.required' => 'タイトルを入力してください',
            'title.max' => 'タイトルは200文字以内で入力してください',
            'content.required' => '内容を入力してください',
            'content.max' => '内容は10000文字以内で入力してください',
            'category_id.required' => 'カテゴリーを選択してください',
            'category_id.integer' => '正しいカテゴリーを選択してください',
            'category_id.exists' => '選択されたカテゴリーは存在しません',
        ];
    }
}
