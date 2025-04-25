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
        Schema::create('itinerary_days', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('itinerary_id');
            $table->integer('area_id')->unsigned();
            $table->string('date');
            $table->smallInteger('day_num')->unsigned();
            $table->time('start_time');
            $table->time('end_time');
//            $table->string('formatted_date');
//            $table->string('formatted_day_of_week');
//            $table->string('formatted_month_day');
//            $table->string('formatted_year');
            $table->json('stats')->nullable();
            $table->timestamps();
            $table->unique(['itinerary_id', 'date']);
            $table->unique(['itinerary_id', 'day_num']);
            $table->foreign('itinerary_id')->references('id')->on('itineraries')->onDelete('cascade');
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itinerary_days');
    }
};
