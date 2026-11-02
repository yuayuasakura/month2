<?php

namespace App\Models;

use App\Enums\TaskPriority;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    // 一括代入を許可するカラム
    // user_id は入れない（フォームから偽装されないよう、Service でログインユーザーのIDを設定する）
    protected $fillable = [
        'name',
        'description',
        'priority',
        'due_date',
    ];

    protected $casts = [
        // 数値(1〜3) ⇔ TaskPriority enum を自動変換（$task->priority->label() で「高」などが取れる）
        'priority' => TaskPriority::class,
        // Carbonオブジェクトに変換（->format() が使えるようになる）
        'due_date' => 'date',
    ];

    // belongsTo: タスク(多) → ユーザー(1)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // belongsToMany: タスク(多) ⇔ ラベル(多)（中間テーブル label_task）
    public function labels()
    {
        return $this->belongsToMany(Label::class)->withTimestamps();
    }

    // 締め切りを過ぎているか（今日が締め切りの場合はまだ過ぎていない扱い）
    public function isOverdue(): bool
    {
        return $this->due_date !== null && $this->due_date->lt(today());
    }
}
