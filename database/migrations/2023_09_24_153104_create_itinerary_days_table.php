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
            $table->increments('id');
            $table->string('date');
            $table->integer('itineraryId')->unsigned();
            $table->integer('areaId')->unsigned();
            $table->smallInteger('dayNum')->unsigned();
            $table->time('startTime');
            $table->time('endTime');
            $table->string('formattedDate');
            $table->string('formattedDayOfWeek');
            $table->string('formattedMonthDay');
            $table->string('formattedYear');
            $table->json('stats')->nullable();
            $table->foreign('itineraryId')->references('id')->on('itineraries')->onDelete('cascade');
            $table->foreign('areaId')->references('id')->on('areas')->onDelete('cascade');
            $table->timestamps();
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
