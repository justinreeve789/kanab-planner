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
        Schema::create('trail_geojson', function (Blueprint $table) {
            $table->integer('trail_id')->unsigned();
            $table->text('geojson');
            $table->timestamps();
            $table->primary(['trail_id']);
            $table->foreign('trail_id')->references('id')->on('trails')->onDelete('cascade');
        });

        if (file_exists('./database/dumps/trail_geojson.sql')) {
            DB::unprepared(file_get_contents('./database/dumps/trail_geojson.sql'));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trail_geojson');
    }
};
