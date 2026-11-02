<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // タスクテーブルを作成する
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            // タスクの持ち主（ユーザーを削除したらそのユーザーのタスクも削除）
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 200);
            // 詳細は任意なので nullable
            $table->text('description')->nullable();
            // 重要度：3=高 / 2=中 / 1=低（数値にしておくと「高い順」に並び替えやすい）
            $table->unsignedTinyInteger('priority')->default(2);
            // 締め切りは任意なので nullable
            $table->date('due_date')->nullable();
            $table->timestamps();

            // 一覧で「ユーザーごと・重要度順」に取得するのでインデックスを張る
            $table->index(['user_id', 'priority']);
        });
    }

    // ロールバック時にテーブルを削除
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
