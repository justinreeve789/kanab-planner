<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('source_type')->nullable();
            $table->string('source_id')->nullable();
            $table->integer('area_id')->unsigned();
            $table->text('description')->nullable();
            $table->float('latitude', 10, 7)->nullable();
            $table->float('longitude', 10, 7)->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('url')->nullable();
            $table->boolean('is_usable')->default(true);
            $table->text('raw_data')->nullable();
            $table->timestamps();
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');
        });

        if (file_exists('./database/dumps/locations.sql')) {
            DB::unprepared(file_get_contents('./database/dumps/locations.sql'));
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('locations');
    }
}
