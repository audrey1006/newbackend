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
        Schema::create('waste_types', function (Blueprint $table) {
            $table->id('waste_type_id');
            $table->string('name', 50)->unique();
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Insertion des types de déchets de base
        DB::table('waste_types')->insert([
            [
                'name' => 'Déchets ménagers',
                'description' => 'Déchets courants provenant des foyers (restes alimentaires, emballages non recyclables, etc.)'
            ],
            [
                'name' => 'Déchets encombrants',
                'description' => 'Objets volumineux tels que les meubles, électroménager, matelas, etc.'
            ],
            [
                'name' => 'Déchets recyclables',
                'description' => 'Papier, carton, plastique, verre, métal et autres matériaux pouvant être recyclés'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waste_types');
    }
};