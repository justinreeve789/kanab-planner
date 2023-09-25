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
        Schema::create('itineraries', function (Blueprint $table) {
            $table->increments('id');
            $table->string('key')->nullable();
            $table->integer('userId')->unsigned()->default(0);
            $table->integer('baseAreaId')->unsigned()->nullable();
            $table->dateTime('arrivalDateTime')->nullable();
            $table->json('activityPriorities')->nullable();
            $table->decimal('adventurousness', 3, 2)->nullable();
            $table->decimal('averageHikingSpeed', 3, 1)->nullable();
            $table->time('checkInTime')->nullable();
            $table->time('checkOutTime')->nullable();
            $table->dateTime('departureDateTime')->nullable();
            $table->smallInteger('durationDays')->unsigned()->nullable();
            $table->date('endDate')->nullable();
            $table->decimal('estimatedCost', 10, 2)->nullable();
            $table->json('eventIds')->nullable();
            $table->integer('homeLocationId')->unsigned()->nullable();
            $table->json('interests')->nullable();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->json('locationIds')->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->string('month')->nullable();
            $table->json('priorities')->nullable();
            $table->json('restaurantIds')->nullable();
            $table->string('season')->nullable();
            $table->json('solitariness')->nullable();
            $table->date('startDate')->nullable();
            $table->json('strenuousness')->nullable();
            $table->integer('totalPossibleTrails')->nullable();
            $table->json('trailIds')->nullable();
            $table->json('trailPriorities')->nullable();
            $table->json('tripPreference')->nullable();
            $table->boolean('useFirstDay')->nullable();
            $table->boolean('useLastDay')->nullable();
            $table->timestamps();
            $table->unique('key');
            $table->foreign('baseAreaId')->references('id')->on('areas')->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itineraries');
    }
};
