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
        Schema::create('location_images', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('location_id')->unsigned();
            $table->string('url')->nullable();
            $table->string('local_url')->nullable();
            $table->string('size')->nullable();
            $table->timestamps();
            $table->unique(['location_id', 'url']);
            $table->unique(['location_id', 'local_url']);
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });

        if (file_exists('./database/dumps/location_images.sql')) {
            DB::unprepared(file_get_contents('./database/dumps/location_images.sql'));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_images');
    }
};
