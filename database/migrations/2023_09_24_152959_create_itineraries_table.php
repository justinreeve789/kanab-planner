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
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->integer('base_area_id')->unsigned()->nullable();
            $table->dateTime('arrival_date_time')->nullable();
            $table->json('activity_priorities')->nullable();
            $table->decimal('adventurousness', 3, 2)->nullable();
            $table->decimal('average_hiking_speed', 3, 1)->nullable();
            $table->time('earliest_day_time')->nullable();
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->dateTime('departure_date_time')->nullable();
            $table->smallInteger('duration_days')->unsigned()->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->json('event_ids')->nullable();
            $table->integer('home_location_id')->unsigned()->nullable();
            $table->json('interests')->nullable();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->json('location_ids')->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->integer('minimum_trail_days')->nullable();
            $table->string('month')->nullable();
            $table->json('priorities')->nullable();
            $table->json('restaurant_ids')->nullable();
            $table->string('season')->nullable();
            $table->decimal('solitariness', 3, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->decimal('strenuousness', 3, 2)->nullable();
            $table->decimal('thriftiness', 3, 2)->nullable();
            $table->integer('total_travel_distance')->nullable();
            $table->integer('total_possible_trail_days')->nullable();
            $table->json('trail_ids')->nullable();
            $table->json('trail_priorities')->nullable();
            $table->json('trip_preference')->nullable();
            $table->boolean('use_first_day')->nullable();
            $table->boolean('use_last_day')->nullable();
            $table->timestamps();
            $table->foreign('base_area_id')->references('id')->on('areas')->onDelete('set null');
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
