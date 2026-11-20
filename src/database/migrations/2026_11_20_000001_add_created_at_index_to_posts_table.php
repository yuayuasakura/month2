<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    // 投稿一覧（PostRepository::getPublished）の「ORDER BY created_at DESC, id DESC LIMIT 10」用のインデックス
    // インデックスがないと、10件だけ欲しくても全件を読んで並び替える（Table scan + filesort）
    // 並び順と同じ (created_at, id) の順で作ると、インデックスを後ろから10件読むだけで済む
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->index(['created_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['created_at', 'id']);
        });
    }
};
