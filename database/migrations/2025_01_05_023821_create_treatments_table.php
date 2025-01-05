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
        Schema::create('treatments', function (Blueprint $table) {
            $table->id();
            $table->string('disease_name');
            
            $table->unsignedBigInteger('chemical_id');
            $table->unsignedBigInteger('biological_id');
            $table->unsignedBigInteger('prevention_id');
            
            $table->foreign('chemical_id')->references('id')->on('chemicals')->onDelete('cascade');
            $table->foreign('biological_id')->references('id')->on('biologicals')->onDelete('cascade');
            $table->foreign('prevention_id')->references('id')->on('preventions')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treatments');
    }
};
