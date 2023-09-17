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
            $table->smallInteger('elevation_high_ft')->nullable();
            $table->smallInteger('elevation_low_ft')->nullable();
            $table->string('vehicle')->nullable();
            $table->decimal('distance_miles', 4, 1)->nullable();
            $table->string('distance_type')->nullable();
            $table->decimal('average_hours', 3, 1)->nullable();
            $table->smallInteger('incline_ft')->nullable();
            $table->smallInteger('decline_ft')->nullable();
            $table->decimal('average_grade_pt', 3, 2)->nullable()->comment('Average grade as a percentage');
            $table->decimal('max_grade_pt', 3, 2)->nullable()->comment('Maximum grade as a percentage');
            $table->boolean('reservation')->nullable();
            $table->boolean('fee')->nullable();
            $table->string('gpx_url')->nullable();
            $table->text('raw_data')->nullable();
            $table->timestamps();
            $table->foreign('area_id')->references('id')->on('areas')->onDelete('cascade');
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
