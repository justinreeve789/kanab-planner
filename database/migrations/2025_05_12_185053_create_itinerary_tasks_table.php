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
        Schema::create('itinerary_tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('activity_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreign('activity_id')->references('id')->on('itinerary_activities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itinerary_tasks');
    }
};
