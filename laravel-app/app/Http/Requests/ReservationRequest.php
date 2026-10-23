<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// 予約作成のバリデーション
class ReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // URLの {event} からイベントを取得（ルートモデルバインディング済み）
        $event = $this->route('event');

        return [
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'people' => ['required', 'integer', 'min:1', 'max:10'],
            'reserved_at' => [
                'required',
                'date',
                'after:now',
                // 開催期間内の日時のみ（終了日はその日の23:59まで）
                'after_or_equal:' . $event->start_date->format('Y-m-d'),
                'before:' . $event->end_date->copy()->addDay()->format('Y-m-d'),
            ],
        ];
    }

    public function messages(): array
    {
        $event = $this->route('event');
        $period = $event->start_date->format('Y/m/d') . '〜' . $event->end_date->format('Y/m/d');

        return [
            'name.required' => '名前を入力してください',
            'name.max' => '名前は50文字以内で入力してください',
            'email.required' => 'メールアドレスを入力してください',
            'email.email' => 'メールアドレスの形式が正しくありません',
            'email.max' => 'メールアドレスは255文字以内で入力してください',
            'people.required' => '人数を入力してください',
            'people.integer' => '人数は整数で入力してください',
            'people.min' => '人数は1人以上で入力してください',
            'people.max' => '1回の予約は10人までです',
            'reserved_at.required' => '日時を選択してください',
            'reserved_at.date' => '日時の形式が正しくありません',
            'reserved_at.after' => '現在より後の日時を選択してください',
            'reserved_at.after_or_equal' => "開催期間（{$period}）内の日時を選択してください",
            'reserved_at.before' => "開催期間（{$period}）内の日時を選択してください",
        ];
    }
}
