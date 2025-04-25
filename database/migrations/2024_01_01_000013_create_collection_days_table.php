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
        Schema::create('collection_days', function (Blueprint $table) {
            $table->id('collection_day_id');
            $table->foreignId('request_id')->constrained('collection_requests', 'request_id')->onDelete('cascade');
            $table->foreignId('time_slot_id')->constrained('collection_time_slots', 'time_slot_id');
            $table->date('collection_date');
            $table->timestamps();

            // Un jour spécifique ne peut avoir qu'une seule collecte pour une demande
            $table->unique(['request_id', 'collection_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_days');
    }
};