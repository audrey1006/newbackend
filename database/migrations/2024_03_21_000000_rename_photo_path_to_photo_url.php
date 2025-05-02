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
        Schema::table('waste_collector_profiles', function (Blueprint $table) {
            $table->renameColumn('photo_path', 'photo_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waste_collector_profiles', function (Blueprint $table) {
            $table->renameColumn('photo_url', 'photo_path');
        });
    }
};