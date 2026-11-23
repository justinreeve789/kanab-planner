<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - itineraries.edit_token_hash: SHA-256 of the secret handed to whoever created the itinerary. Holding the token
 *   (or being the owning user, via user_itineraries) is what allows editing.
 * - itinerary_days.preamble: the day's intro text, previously only kept in memory and lost on reload.
 * - itinerary_days.drive_minutes: one-way drive from the base area, shown on the day and used for scheduling.
 * - itineraries.*_priorities: the events, places, restaurants and areas the user asked for, kept so a day can be
 *   regenerated with the same wishes (only trail_priorities had a column).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itineraries', function (Blueprint $table) {
            $table->string('edit_token_hash', 64)->nullable()->after('name');
            $table->json('area_priorities')->nullable()->after('activity_priorities');
            $table->json('event_priorities')->nullable()->after('event_ids');
            $table->json('location_priorities')->nullable()->after('location_ids');
            $table->json('restaurant_priorities')->nullable()->after('restaurant_ids');
        });
        Schema::table('itinerary_days', function (Blueprint $table) {
            $table->text('preamble')->nullable()->after('end_time');
            $table->smallInteger('drive_minutes')->unsigned()->nullable()->after('preamble');
        });
        Schema::table('user_itineraries', function (Blueprint $table) {
            $table->unique(['user_id', 'itinerary_id']);
        });
    }

    public function down(): void
    {
        Schema::table('user_itineraries', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'itinerary_id']);
        });
        Schema::table('itinerary_days', function (Blueprint $table) {
            $table->dropColumn(['preamble', 'drive_minutes']);
        });
        Schema::table('itineraries', function (Blueprint $table) {
            $table->dropColumn(['edit_token_hash', 'area_priorities', 'event_priorities', 'location_priorities', 'restaurant_priorities']);
        });
    }
};
