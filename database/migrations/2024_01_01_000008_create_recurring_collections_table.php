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
        Schema::create('recurring_collections', function (Blueprint $table) {
            $table->id('recurring_id');
            $table->foreignId('request_id')->constrained('collection_requests', 'request_id')->onDelete('cascade');
            $table->enum('frequency', ['quotidien', 'hebdomadaire', 'bi-hebdomadaire', 'mensuel']);
            $table->integer('day_of_week')->nullable(); // Pour les collectes hebdomadaires (1-7)
            $table->integer('day_of_month')->nullable(); // Pour les collectes mensuelles (1-31)
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_collections');
    }
};