<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // タスクにつけるラベル（課題・家事など）のテーブルを作成する
    public function up(): void
    {
        Schema::create('labels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->timestamps();
        });
    }

    // ロールバック時にテーブルを削除
    public function down(): void
    {
        Schema::dropIfExists('labels');
    }
};
