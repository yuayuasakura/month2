<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 投稿テーブルを作成する（categories より後に作る必要があるのでファイル名の日時を後にしている）
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            // カテゴリーへの外部キー（投稿が残っているカテゴリーは削除できない）
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('title', 200);
            $table->text('content');
            // 投稿者（user_id）の外部キーは、ログイン機能を入れる Week 8 で追加します
            $table->timestamps();
        });
    }

    // ロールバック時にテーブルを削除
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
