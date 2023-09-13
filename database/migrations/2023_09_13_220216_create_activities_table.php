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
            $table->decimal('adventurousness', 2, 1);
            $table->decimal('solitude', 2, 1);
        });

        DB::table('activities')->insert([
            ['name' => 'bicycling', 'adventurousness' => 0.8, 'solitude' => 0.7],
            ['name' => 'boating', 'adventurousness' => 0.7, 'solitude' => 0.3],
            ['name' => 'concert', 'adventurousness' => 0.5, 'solitude' => 0.1],
            ['name' => 'dining', 'adventurousness' => 0.1, 'solitude' => 0.2],
            ['name' => 'endurance-race', 'adventurousness' => 1.0, 'solitude' => 0.1],
            ['name' => 'festivals-and-celebrations', 'adventurousness' => 0.4, 'solitude' => 0.1],
            ['name' => 'hiking', 'adventurousness' => 0.4, 'solitude' => 0.5],
            ['name' => 'location', 'adventurousness' => 0.4, 'solitude' => 0.3],
            ['name' => 'museum', 'adventurousness' => 0.3, 'solitude' => 0.3],
            ['name' => 'offroading', 'adventurousness' => 0.8, 'solitude' => 0.3],
            ['name' => 'rock-climbing', 'adventurousness' => 0.9, 'solitude' => 0.2],
            ['name' => 'shopping', 'adventurousness' => 0.0, 'solitude' => 0.1],
            ['name' => 'sightseeing', 'adventurousness' => 0.3, 'solitude' => 0.5],
            ['name' => 'swimming', 'adventurousness' => 0.2, 'solitude' => 0.2],
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
