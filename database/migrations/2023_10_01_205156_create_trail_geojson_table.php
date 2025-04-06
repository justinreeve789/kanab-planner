<?php

use App\Traits\ImportTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use ImportTable;

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

        self::importTable('trail_geojson');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trail_geojson');
    }
};
