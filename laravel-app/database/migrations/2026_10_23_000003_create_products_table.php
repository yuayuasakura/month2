<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 商品テーブルを作成する
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);                        // 商品名
            $table->unsignedInteger('price');                   // 価格（円・マイナス不可）
            $table->text('description')->nullable();            // 説明（任意）
            $table->unsignedInteger('stock')->default(0);       // 在庫数（マイナス不可）
            $table->string('category', 50);                     // カテゴリー
            $table->timestamps();
        });
    }

    // ロールバック時にテーブルを削除
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
