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
        Schema::create('shrines', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->double('latitude', 10, 7);
            $table->double('longitude', 10, 7);
            $table->string('place_id')->unique();
            $table->timestamps();
        });

        Schema::table('shrines', function (Blueprint $table) {
            $table->index(['latitude', 'longitude']);
            $table->index('place_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shrines');
    }
};
