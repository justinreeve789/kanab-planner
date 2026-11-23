<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where the traveller is staying. itineraries.latitude/longitude already exist; they now hold the pin for the
 * traveller's own lodging (null means "somewhere in the base area").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itineraries', function (Blueprint $table) {
            $table->string('lodging_name', 120)->nullable()->after('base_area_id');
        });
    }

    public function down(): void
    {
        Schema::table('itineraries', function (Blueprint $table) {
            $table->dropColumn('lodging_name');
        });
    }
};
