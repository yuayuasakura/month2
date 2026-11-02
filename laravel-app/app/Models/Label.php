<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Label extends Model
{
    protected $fillable = [
        'name',
    ];

    // belongsToMany: ラベル(多) ⇔ タスク(多)（中間テーブル label_task）
    public function tasks()
    {
        return $this->belongsToMany(Task::class)->withTimestamps();
    }
}
