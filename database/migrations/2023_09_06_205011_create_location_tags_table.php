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
        Schema::create('location_tags', function (Blueprint $table) {
            $table->integer('location_id')->unsigned();
            $table->string('tag');
            $table->timestamps();
            $table->primary(['location_id', 'tag']);
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });

        if (file_exists('./database/dumps/location_tags.sql')) {
            DB::unprepared(file_get_contents('./database/dumps/location_tags.sql'));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_tags');
    }
};
