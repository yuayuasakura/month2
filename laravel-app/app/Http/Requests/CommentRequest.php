<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// コメント投稿のバリデーション
class CommentRequest extends FormRequest
{
    // ログインしているかは routes/web.php の auth ミドルウェアで確認済み
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => 'コメントを入力してください',
            'body.max' => 'コメントは1000文字以内で入力してください',
        ];
    }
}
