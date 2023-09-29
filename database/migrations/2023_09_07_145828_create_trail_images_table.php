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
        Schema::create('trail_images', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('trail_id')->unsigned();
            $table->string('url')->nullable();
            $table->string('local_url')->nullable();
            $table->string('size')->nullable();
            $table->timestamps();
            $table->unique(['trail_id', 'url']);
            $table->unique(['trail_id', 'local_url']);
            $table->foreign('trail_id')->references('id')->on('trails')->onDelete('cascade');
        });

        if (file_exists('./database/dumps/trail_images.sql')) {
            DB::unprepared(file_get_contents('./database/dumps/trail_images.sql'));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trail_images');
    }
};
