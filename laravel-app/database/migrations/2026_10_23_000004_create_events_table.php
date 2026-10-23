<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // イベントテーブルを作成する
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);                  // イベント名
            $table->text('description')->nullable();       // 説明
            $table->string('venue', 100);                  // 会場
            $table->date('start_date');                    // 開催開始日
            $table->date('end_date');                      // 開催終了日
            $table->unsignedInteger('capacity');           // 定員（同じ日時あたりの人数）
            $table->timestamps();
        });
    }

    // ロールバック時にテーブルを削除
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
