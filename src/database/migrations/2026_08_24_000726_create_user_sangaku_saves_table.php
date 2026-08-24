<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_sangaku_saves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sangaku_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // 同じユーザーが同じ算額を二重に保存できないようにする。
            // この制約違反が QueryException 経由で 409 Conflict に変換される。
            $table->unique(['user_id', 'sangaku_id']);

            // 複合 unique は左端の user_id からしか引けないため、
            // sangaku_id 側からの絞り込み（savedByUsers）用に単体インデックスを張る。
            $table->index('sangaku_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sangaku_saves');
    }
};
