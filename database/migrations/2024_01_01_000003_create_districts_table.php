<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->id('district_id');
            $table->foreignId('city_id')->constrained('cities', 'city_id');
            $table->string('name', 100);
            $table->timestamps(); 

            // Index pour optimiser les performances
            $table->index('city_id');

            // Contrainte d'unicité pour éviter les doublons de noms dans une même ville
            $table->unique(['city_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};