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
        Schema::create('collection_requests', function (Blueprint $table) {
            $table->id('request_id');
            $table->foreignId('client_id')->constrained('client_profiles', 'client_id');
            $table->foreignId('collector_id')->nullable()->constrained('waste_collector_profiles', 'collector_id');
            $table->foreignId('waste_type_id')->constrained('waste_types', 'waste_type_id');
            $table->foreignId('district_id')->constrained('districts', 'district_id');
            $table->enum('status', ['en attente', 'acceptée', 'en cours', 'effectuée', 'annulée'])->default('en attente');
            $table->enum('collection_type', ['ponctuelle', 'périodique']);
            $table->string('frequency', 50)->nullable();
            $table->timestamp('scheduled_date');
            $table->timestamp('completed_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Index pour optimiser les performances
            $table->index('status');
            $table->index('collector_id');
            $table->index('client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_requests');
    }
};