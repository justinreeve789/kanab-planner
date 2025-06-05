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
        Schema::create('trail_images', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('trail_id')->unsigned();
            $table->string('url')->nullable();
            $table->string('local_url')->nullable();
            $table->string('medium_local_url')->nullable();
            $table->string('thumbnail_local_url')->nullable();
            $table->integer('size')->unsigned()->nullable();
            $table->integer('medium_size')->unsigned()->nullable();
            $table->integer('thumbnail_size')->unsigned()->nullable();
            $table->string('latitude', 16)->nullable();
            $table->string('longitude', 16)->nullable();
            $table->timestamps();
            $table->unique(['trail_id', 'url']);
            $table->unique(['trail_id', 'local_url']);
            $table->foreign('trail_id')->references('id')->on('trails')->onDelete('cascade');
        });

        self::importTable('trail_images');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trail_images');
    }
};
