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
            $table->time('start_time');
            $table->string('description');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insérer les créneaux horaires prédéfinis
        DB::table('collection_time_slots')->insert([
            [
                'start_time' => '08:00:00',
                'description' => 'Matin',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'start_time' => '14:00:00',
                'description' => 'Après-midi',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'start_time' => '20:00:00',
                'description' => 'Soir',
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