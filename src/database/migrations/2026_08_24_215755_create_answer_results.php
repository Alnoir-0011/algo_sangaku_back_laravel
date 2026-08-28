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
        Schema::create('answer_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('answer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fixed_input_id')->nullable()->constrained()->cascadeOnDelete();
            $table->text('output')->nullable();
            $table->integer('status')->default(0);
            $table->timestamps();

            $table->unique(['answer_id', 'fixed_input_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('answer_results');
    }
};
