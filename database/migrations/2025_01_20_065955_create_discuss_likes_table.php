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
        Schema::create('discuss_likes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('discuss_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('discuss_id')->references('id')->on('discusses')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discuss_likes');
    }
};
