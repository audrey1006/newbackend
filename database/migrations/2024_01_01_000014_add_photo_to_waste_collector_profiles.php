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
            $table->string('photo_path')->nullable()->after('district_id');
            $table->boolean('is_verified')->default(false)->after('photo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waste_collector_profiles', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'is_verified']);
        });
    }
};