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
            $table->integer('period_id')->unsigned();
            $table->time('start_time');
            $table->time('end_time');
            $table->text('description')->nullable();
            $table->string('phrase')->nullable();
            $table->integer('activity_type_id')->unsigned()->nullable();
            $table->string('resource_table')->nullable();
            $table->integer('resource_id')->unsigned()->nullable();
            $table->timestamps();
            $table->unique(['itinerary_id', 'hash']);
            $table->foreign('period_id')->references('id')->on('itinerary_periods')->onDelete('cascade');
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
