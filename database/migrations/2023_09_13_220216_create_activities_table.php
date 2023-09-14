<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->decimal('adventurousness', 3, 2);
            $table->decimal('solitariness', 3, 2);
            $table->decimal('strenuousness', 3, 2);
        });

        DB::table('activities')->insert([
            ['name' => 'bicycling', 'adventurousness' => 0.5, 'solitariness' => 0.7, 'strenuousness' => 0.6],
            ['name' => 'boating', 'adventurousness' => 0.7, 'solitariness' => 0.3, 'strenuousness' => 0.4],
            ['name' => 'concert', 'adventurousness' => 0.5, 'solitariness' => 0.1, 'strenuousness' => 0.2],
            ['name' => 'dining', 'adventurousness' => 0.1, 'solitariness' => 0.2, 'strenuousness' => 0],
            ['name' => 'endurance-race', 'adventurousness' => 0.8, 'solitariness' => 0.1, 'strenuousness' => 1.0],
            ['name' => 'festivals-and-celebrations', 'adventurousness' => 0.8, 'solitariness' => 0.1, 'strenuousness' => 0.2],
            ['name' => 'hiking', 'adventurousness' => 0.6, 'solitariness' => 0.5, 'strenuousness' => 0.6],
            ['name' => 'location', 'adventurousness' => 0.4, 'solitariness' => 0.3, 'strenuousness' => 0.2],
            ['name' => 'museum', 'adventurousness' => 0.3, 'solitariness' => 0.3, 'strenuousness' => 0.1],
            ['name' => 'offroading', 'adventurousness' => 0.8, 'solitariness' => 0.3, 'strenuousness' => 0.5],
            ['name' => 'rock-climbing', 'adventurousness' => 0.6, 'solitariness' => 0.2, 'strenuousness' => 0.8],
            ['name' => 'shopping', 'adventurousness' => 0, 'solitariness' => 0.1, 'strenuousness' => 0],
            ['name' => 'sightseeing', 'adventurousness' => 0.3, 'solitariness' => 0.5, 'strenuousness' => 0],
            ['name' => 'swimming', 'adventurousness' => 0.2, 'solitariness' => 0.2, 'strenuousness' => 0.1],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
