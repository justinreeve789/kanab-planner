<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trails', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('name_alt')->nullable();
            $table->string('source_type');
            $table->string('source_id');
            $table->integer('area_id')->unsigned();
            $table->text('description')->nullable();
            $table->text('description_alt')->nullable();
            $table->text('directions')->nullable();
            $table->text('accessibility')->nullable();
            $table->string('duration')->nullable();
            $table->string('pets_allowed')->nullable();
            $table->float('latitude', 10, 7)->nullable();
            $table->float('longitude', 10, 7)->nullable();
            $table->string('features')->nullable();
            $table->string('url')->nullable();
            $table->string('contact_url')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('season')->nullable();
            $table->string('difficulty')->nullable();
            $table->string('elevation_high')->nullable();
            $table->string('elevation_low')->nullable();
            $table->string('vehicle')->nullable();
            $table->string('distance_miles')->nullable();
            $table->string('distance_type')->nullable();
            $table->string('incline')->nullable();
            $table->string('decline')->nullable();
            $table->string('grade')->nullable();
            $table->boolean('reservation')->nullable();
            $table->boolean('fee')->nullable();
            $table->string('gpx_url')->nullable();
            $table->text('raw_data')->nullable();
            $table->timestamps();
        });

        if (file_exists('./database/dumps/trails.sql')) {
            DB::unprepared(file_get_contents('./database/dumps/trails.sql'));
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trails');
    }
}
