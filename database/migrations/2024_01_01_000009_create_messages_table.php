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
        Schema::create('messages', function (Blueprint $table) {
            $table->id('message_id');
            $table->foreignId('request_id')->constrained('collection_requests', 'request_id');
            $table->foreignId('sender_id')->constrained('users', 'user_id');
            $table->foreignId('receiver_id')->constrained('users', 'user_id');
            $table->text('content');
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();

            // Index pour optimiser les performances
            $table->index('request_id');
            $table->index(['sender_id', 'receiver_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};