<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

// API（Vue.js のフォーム）からの投稿作成のバリデーション
// フォームはタイトルと本文だけなので、カテゴリーは受け取らない（Service で「その他」にする）
class PostStoreRequest extends FormRequest
{
    // 今週の API は認証なしなので誰でもリクエストできる
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'content' => ['required', 'string', 'max:10000'],
        ];
    }

    // エラーメッセージ（バリデーションエラー時は 422 と一緒に JSON で返り、フロントで表示する）
    public function messages(): array
    {
        return [
            'title.required' => 'タイトルを入力してください',
            'title.max' => 'タイトルは200文字以内で入力してください',
            'content.required' => '本文を入力してください',
            'content.max' => '本文は10000文字以内で入力してください',
        ];
    }
}
