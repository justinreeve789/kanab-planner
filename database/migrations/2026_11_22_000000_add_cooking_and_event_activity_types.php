<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ItineraryService schedules "cooking" tasks (meals at home) and "event" activities, but neither type existed, so
 * those activities were created with a null activity_type and saved with activity_type_id = 0. Anything that then
 * asked a period "do you already have a cooking task?" either crashed on the null relation or always said no.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('activity_types')->insertOrIgnore([
            ['name' => 'cooking', 'description' => 'Meal at Home', 'adventurousness' => 0.0, 'solitariness' => 0.3, 'strenuousness' => 0.0, 'is_selectable' => false],
            ['name' => 'event', 'description' => 'Event', 'adventurousness' => 0.5, 'solitariness' => 0.1, 'strenuousness' => 0.2, 'is_selectable' => false],
        ]);
    }

    public function down(): void
    {
        DB::table('activity_types')->whereIn('name', ['cooking', 'event'])->delete();
    }
};
