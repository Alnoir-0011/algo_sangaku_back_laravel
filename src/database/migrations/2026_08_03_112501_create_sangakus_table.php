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
        Schema::create('sangakus', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->text('source');
            // App\Enums\Difficulty::EASY に対応する値。Enum の変更でマイグレーションの
            // 挙動が変わらないようリテラルで固定する
            $table->integer('difficulty')->default(0);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shrine_id')->nullable()->constrained('shrines')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sangakus');
    }
};
