<?php

use App\Enums\Difficulty;
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
            $table->integer('difficulty')->default(Difficulty::EASY->value);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('shrine_id')->nullable()->constrained('shrines')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('fixed_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sangaku_id')->constrained('sangakus')->onDelete('cascade');
            $table->string('content');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_inputs');
        Schema::dropIfExists('sangakus');
    }
};
