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
        Schema::create('ratings', function (Blueprint $table) {
            $table->id('rating_id');
            $table->foreignId('request_id')->unique()->constrained('collection_requests', 'request_id')->onDelete('cascade');
            $table->foreignId('client_id')->constrained('client_profiles', 'client_id');
            $table->foreignId('collector_id')->constrained('waste_collector_profiles', 'collector_id');
            $table->integer('score')->comment('Note de 1 à 5');
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Index pour optimiser les performances
            $table->index('collector_id');
            $table->index('client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};