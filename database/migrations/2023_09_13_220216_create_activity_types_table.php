<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('description');
            $table->decimal('adventurousness', 3, 2)->comment('The uniqueness of the experience, and how outside an average person\'s comfort zone it is');
            $table->decimal('solitariness', 3, 2)->comment('How much a person who enjoys less-crowded or non-human-interacting experiences would enjoy it');
            $table->decimal('strenuousness', 3, 2)->comment('How physically exerting the experience is');
//            $table->decimal('childfriendliness', 3, 2)->nullable()->comment('How suitable the experience is for children');
//            $table->decimal('lengthiness', 3, 2)->nullable();
//            $table->decimal('outbackiness', 3, 2)->nullable()->comment('How remote the experience is');
//            $table->decimal('weatheriness', 3, 2)->nullable()->comment('How dependents the experience is on the weather');
//            $table->decimal('noisiness', 3, 2)->nullable();
            $table->boolean('is_selectable')->default(true);
            $table->unique('name');
        });

        DB::table('activity_types')->insert([
            ['name' => 'bicycling', 'description' => 'Bicycling', 'adventurousness' => 0.5, 'solitariness' => 0.7, 'strenuousness' => 0.6, 'is_selectable' => true],
            ['name' => 'watersports', 'description' => 'Water Sports', 'adventurousness' => 0.7, 'solitariness' => 0.3, 'strenuousness' => 0.4, 'is_selectable' => true],
            ['name' => 'concert', 'description' => 'Music & Concerts', 'adventurousness' => 0.5, 'solitariness' => 0.1, 'strenuousness' => 0.2, 'is_selectable' => false],
            ['name' => 'conferences-and-conventions', 'description' => 'Conferences & Conventions', 'adventurousness' => 0.0, 'solitariness' => 0.0, 'strenuousness' => 0.0, 'is_selectable' => false],
            ['name' => 'dining', 'description' => 'Dining', 'adventurousness' => 0.1, 'solitariness' => 0.2, 'strenuousness' => 0, 'is_selectable' => true],
            ['name' => 'educational-fun', 'description' => 'Educational & Fun', 'adventurousness' => 0.5, 'solitariness' => 0.1, 'strenuousness' => 0.2, 'is_selectable' => true],
            ['name' => 'endurance-race', 'description' => 'Marathons and Races', 'adventurousness' => 0.8, 'solitariness' => 0.1, 'strenuousness' => 1.0, 'is_selectable' => false],
            ['name' => 'festivals-and-celebrations', 'description' => 'Festivals & Celebrations', 'adventurousness' => 0.6, 'solitariness' => 0.1, 'strenuousness' => 0.2, 'is_selectable' => true],
            ['name' => 'hiking', 'description' => 'Hiking', 'adventurousness' => 0.6, 'solitariness' => 0.5, 'strenuousness' => 0.6, 'is_selectable' => true],
            ['name' => 'museum', 'description' => 'Museums & Culture', 'adventurousness' => 0.3, 'solitariness' => 0.3, 'strenuousness' => 0.1, 'is_selectable' => true],
            ['name' => 'night-life', 'description' => 'Night-Life', 'adventurousness' => 0.3, 'solitariness' => 0.1, 'strenuousness' => 0.1, 'is_selectable' => true],
            ['name' => 'offroading', 'description' => 'Offroading', 'adventurousness' => 0.8, 'solitariness' => 0.3, 'strenuousness' => 0.4, 'is_selectable' => true],
            ['name' => 'rock-climbing', 'description' => 'Rock Climbing & Canyoneering', 'adventurousness' => 0.6, 'solitariness' => 0.2, 'strenuousness' => 0.8, 'is_selectable' => true],
            ['name' => 'rodeo', 'description' => 'Rodeos', 'adventurousness' => 0.6, 'solitariness' => 0.1, 'strenuousness' => 0.3, 'is_selectable' => false],
            ['name' => 'shopping', 'description' => 'Shopping', 'adventurousness' => 0, 'solitariness' => 0.1, 'strenuousness' => 0, 'is_selectable' => false],
            ['name' => 'sightseeing', 'description' => 'Sightseeing', 'adventurousness' => 0.3, 'solitariness' => 0.5, 'strenuousness' => 0, 'is_selectable' => false],
            ['name' => 'stargazing', 'description' => 'Stargazing', 'adventurousness' => 0.1, 'solitariness' => 0.7, 'strenuousness' => 0, 'is_selectable' => true],
            ['name' => 'swimming', 'description' => 'Swimming', 'adventurousness' => 0.2, 'solitariness' => 0.2, 'strenuousness' => 0.1, 'is_selectable' => false],
            ['name' => 'tour', 'description' => 'Tours', 'adventurousness' => 0.4, 'solitariness' => 0.2, 'strenuousness' => 0.2, 'is_selectable' => true],
            ['name' => 'fishing', 'description' => 'Fishing', 'adventurousness' => 0.2, 'solitariness' => 0.9, 'strenuousness' => 0.1, 'is_selectable' => false],
            ['name' => 'games-and-entertainment', 'description' => 'Games & Entertainment', 'adventurousness' => 0.4, 'solitariness' => 0.1, 'strenuousness' => 0.1, 'is_selectable' => false],
            ['name' => 'checking-in', 'description' => 'Checking In', 'adventurousness' => 0.0, 'solitariness' => 0.0, 'strenuousness' => 0.0, 'is_selectable' => false],
            ['name' => 'checking-out', 'description' => 'Checking Out', 'adventurousness' => 0.0, 'solitariness' => 0.0, 'strenuousness' => 0.0, 'is_selectable' => false],
            ['name' => 'travel', 'description' => 'Travel', 'adventurousness' => 0.0, 'solitariness' => 0.0, 'strenuousness' => 0.0, 'is_selectable' => false],
            ['name' => 'unknown', 'description' => 'Unknown', 'adventurousness' => 0.4, 'solitariness' => 0.3, 'strenuousness' => 0.2, 'is_selectable' => false],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_types');
    }
};
