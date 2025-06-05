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
        Schema::create('event_images', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('event_id')->unsigned();
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
            $table->unique(['event_id', 'url']);
            $table->unique(['event_id', 'local_url']);
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
        });

        self::importTable('event_images');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_images');
    }
};
