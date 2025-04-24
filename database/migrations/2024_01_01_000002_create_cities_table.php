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
        Schema::create('cities', function (Blueprint $table) {
            $table->id('city_id');
            $table->string('name', 100)->unique();
            $table->timestamp('created_at')->useCurrent();
        });

        // Insertion des 10 grandes villes du Cameroun
        DB::table('cities')->insert([
            ['name' => 'Douala'],
            ['name' => 'Yaoundé'],
            ['name' => 'Garoua'],
            ['name' => 'Bamenda'],
            ['name' => 'Maroua'],
            ['name' => 'Nkongsamba'],
            ['name' => 'Bafoussam'],
            ['name' => 'Ngaoundéré'],
            ['name' => 'Bertoua'],
            ['name' => 'Loum']
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};