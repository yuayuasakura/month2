<?php

namespace App\Enums;

// タスクの重要度（DBには数値で保存し、画面では「高・中・低」と表示する）
enum TaskPriority: int
{
    case High = 3;
    case Medium = 2;
    case Low = 1;

    // 画面に表示する日本語名
    public function label(): string
    {
        return match ($this) {
            self::High => '高',
            self::Medium => '中',
            self::Low => '低',
        };
    }

    // 一覧で色分けするためのCSSクラス名
    public function cssClass(): string
    {
        return match ($this) {
            self::High => 'priority-high',
            self::Medium => 'priority-medium',
            self::Low => 'priority-low',
        };
    }
}
