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
        Schema::create('accommodation_images', function (Blueprint $table) {
            $table-increments('id');
            $table->integer('accommodation_id')->unsigned();
            $table->string('url')->nullable();
            $table->string('local_url')->nullable();
            $table->string('medium_local_url')->nullable();
            $table->string('thumbnail_local_url')->nullable();
            $table->integer('size')->unsigned()->nullable();
            $table->integer('medium_size')->unsigned()->nullable();
            $table->integer('thumbnail_size')->unsigned()->nullable();
            $table->timestamps();
            $table->foreign('accommodation_id')->references('id')->on('accommodations')->onDelete('cascade');
        });

        self::importTable('accommodation_images');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accommodation_images');
    }
};
