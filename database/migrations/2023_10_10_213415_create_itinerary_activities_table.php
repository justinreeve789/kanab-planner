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
        Schema::create('itinerary_activities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('itinerary_day_id')->unsigned();
            $table->integer('itinerary_id')->unsigned();
            $table->integer('activity_type_id')->unsigned();
            $table->string('hash');
            $table->text('description')->nullable();
            $table->string('phrase')->nullable();
            $table->time('start_time');
            $table->time('end_time');
            $table->string('period')->nullable();
            $table->string('resource_table')->nullable();
            $table->integer('resource_id')->unsigned()->nullable();
            $table->timestamps();
            $table->unique(['itinerary_id', 'hash']);
            $table->foreign('itinerary_day_id')->references('id')->on('itinerary_days')->onDelete('cascade');
            $table->foreign('itinerary_id')->references('id')->on('itineraries')->onDelete('cascade');
            $table->foreign('activity_type_id')->references('id')->on('activity_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itinerary_activities');
    }
};
