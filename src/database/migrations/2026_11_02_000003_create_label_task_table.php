<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // タスクとラベルの中間テーブル（1つのタスクに複数ラベル、1つのラベルに複数タスク＝多対多）
    // テーブル名は2つのモデル名を単数形・アルファベット順に並べた「label_task」（Laravelの命名規則）
    public function up(): void
    {
        Schema::create('label_task', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('label_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // 同じタスクに同じラベルが2回つかないようにする
            $table->unique(['task_id', 'label_id']);
        });
    }

    // ロールバック時にテーブルを削除
    public function down(): void
    {
        Schema::dropIfExists('label_task');
    }
};
