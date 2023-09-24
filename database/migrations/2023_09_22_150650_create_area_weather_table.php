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
        Schema::create('area_weather', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('area_id');
            $table->string('source_type');
            $table->dateTime('start');
            $table->dateTime('end');
            $table->smallInteger('temperature');
            $table->char('temperature_unit', 1);
            $table->string('temperature_trend')->nullable();
            $table->string('short_forecast')->nullable();
            $table->string('detailed_forecast')->nullable();
            $table->string('wind_speed')->nullable();
            $table->string('wind_direction')->nullable();
            $table->float('probability_of_precipitation_pt')->nullable();
            $table->float('dewpoint')->nullable();
            $table->float('relative_humidity_pt')->nullable();
            $table->string('icon_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('area_weather');
    }
};
