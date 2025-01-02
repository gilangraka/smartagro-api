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
        Schema::create('plant_recommendations', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('season_id');
            $table->foreign('season_id')->references('id')->on('m_seasons');
            $table->char('name', 100);
            $table->char('imageUrl', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plant_recommendations');
    }
};
