<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('collection_time_slots', function (Blueprint $table) {
            $table->id('time_slot_id');
            $table->time('collection_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insérer les heures de collecte prédéfinies
        DB::table('collection_time_slots')->insert([
            [
                'collection_time' => '06:00:00',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'collection_time' => '08:00:00',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'collection_time' => '10:00:00',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'collection_time' => '14:00:00',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'collection_time' => '16:00:00',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'collection_time' => '18:00:00',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_time_slots');
    }
};