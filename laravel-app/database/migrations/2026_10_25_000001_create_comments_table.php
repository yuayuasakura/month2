<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // コメントテーブルを作成する
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            // 投稿が削除されたら、その投稿へのコメントも削除する
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            // ユーザーが退会（削除）したら、その人のコメントも削除する
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');                          // コメント本文
            $table->timestamps();
        });
    }

    // ロールバック時にテーブルを削除
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
