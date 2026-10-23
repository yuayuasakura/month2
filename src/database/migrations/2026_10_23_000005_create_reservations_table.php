<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 予約テーブルを作成する
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            // イベントへの外部キー（予約が残っているイベントは削除できない）
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->string('name', 50);                    // 予約者名
            $table->string('email', 255);                  // メールアドレス
            $table->unsignedInteger('people');             // 人数
            $table->dateTime('reserved_at');               // 予約日時
            // キャンセル日時（null なら有効な予約。削除せずに残すことで履歴が分かる）
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();

            // 「同じイベント・同じ日時の予約人数」を集計するので複合インデックスを張る
            $table->index(['event_id', 'reserved_at']);
        });
    }

    // ロールバック時にテーブルを削除
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
