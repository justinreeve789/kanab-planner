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
        Schema::create('area_distances', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('from_area_id')->unsigned();
            $table->integer('to_area_id')->unsigned();
            $table->float('direct_miles')->nullable();
            $table->float('backpacking_hours')->nullable();
            $table->float('backpacking_miles')->nullable();
            $table->float('bicycling_hours')->nullable();
            $table->float('bicycling_miles')->nullable();
            $table->float('driving_hours')->nullable();
            $table->float('driving_miles')->nullable();
            $table->float('utv_hours')->nullable();
            $table->float('utv_miles')->nullable();
            $table->text('raw_data')->nullable();
            $table->timestamps();
            $table->unique(['from_area_id', 'to_area_id']);
            $table->foreign('from_area_id')->references('id')->on('areas')->onDelete('cascade');
            $table->foreign('to_area_id')->references('id')->on('areas')->onDelete('cascade');
        });

        if (file_exists('./database/dumps/area_distances.sql')) {
            DB::unprepared(file_get_contents('./database/dumps/area_distances.sql'));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('area_distances');
    }
};
