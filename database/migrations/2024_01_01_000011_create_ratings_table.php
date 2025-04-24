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
            $table->foreignId('request_id')->constrained('collection_requests', 'request_id');
            $table->foreignId('client_id')->constrained('client_profiles', 'client_id');
            $table->foreignId('collector_id')->constrained('waste_collector_profiles', 'collector_id');
            $table->integer('score')->check('score BETWEEN 1 AND 5');
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();
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