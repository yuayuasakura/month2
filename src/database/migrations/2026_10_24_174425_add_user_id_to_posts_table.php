<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // posts テーブルに投稿者（user_id）を追加する
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // nullable：ログイン機能を入れる前に作られた投稿には投稿者がいないため
            // cascadeOnDelete：ユーザーが退会（削除）したら、その人の投稿も削除する
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    // ロールバック時は外部キー制約ごとカラムを削除
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
