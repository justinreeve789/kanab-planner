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
            $table->uuid('id')->primary();
            $table->uuid('period_id');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->text('description')->nullable();
            $table->string('phrase')->nullable();
            $table->integer('activity_type_id')->unsigned();
            $table->string('resource_table')->nullable();
            $table->integer('resource_id')->unsigned()->nullable();
            $table->json('stats')->nullable();
            $table->timestamps();
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
