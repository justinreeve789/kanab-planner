<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Street-routed drive legs, and the road events (closures, roadwork, incidents) that affect them.
 *
 * An activity's travel_* columns describe the drive that gets you there from the previous stop; a day's return_*
 * columns describe the drive home at the end. travel_source is 'router' or 'estimate'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('itinerary_activities', function (Blueprint $table) {
            $table->unsignedSmallInteger('travel_minutes')->nullable()->after('resource_id');
            $table->decimal('travel_miles', 6, 1)->nullable()->after('travel_minutes');
            $table->string('travel_source', 16)->nullable()->after('travel_miles');
            $table->json('travel_notes')->nullable()->after('travel_source');
        });

        Schema::table('itinerary_days', function (Blueprint $table) {
            $table->unsignedSmallInteger('return_minutes')->nullable()->after('drive_minutes');
            $table->decimal('return_miles', 6, 1)->nullable()->after('return_minutes');
            $table->json('return_notes')->nullable()->after('return_miles');
        });

        Schema::create('road_events', function (Blueprint $table) {
            $table->id();
            $table->string('source', 16);
            $table->string('external_id', 64);
            $table->string('event_type', 32);
            $table->boolean('is_full_closure')->default(false);
            $table->string('severity', 32)->nullable();
            $table->string('roadway')->nullable();
            $table->text('description')->nullable();
            $table->decimal('latitude', 9, 6);
            $table->decimal('longitude', 9, 6);
            $table->decimal('latitude_secondary', 9, 6)->nullable();
            $table->decimal('longitude_secondary', 9, 6)->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_id']);
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('road_events');
        Schema::table('itinerary_days', function (Blueprint $table) {
            $table->dropColumn(['return_minutes', 'return_miles', 'return_notes']);
        });
        Schema::table('itinerary_activities', function (Blueprint $table) {
            $table->dropColumn(['travel_minutes', 'travel_miles', 'travel_source', 'travel_notes']);
        });
    }
};
