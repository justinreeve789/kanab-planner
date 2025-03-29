<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('source_type');
            $table->string('source_id');
            $table->integer('location_id')->unsigned()->nullable();
            $table->text('description')->nullable();
            $table->float('latitude', 10, 7)->nullable();
            $table->float('longitude', 10, 7)->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('url')->nullable();
            $table->boolean('free')->nullable();
            $table->string('price')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->text('raw_data')->nullable();
            $table->boolean('is_usable')->default(true);
            $table->boolean('is_merged')->default(false)->comment('If true, this is an amalgamation of multiple records, recorded in resource_merges');
            $table->timestamps();
            $table->unique(['source_type', 'source_id']);
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });

        if (file_exists('./database/dumps/events.sql')) {
            DB::unprepared(file_get_contents('./database/dumps/events.sql'));
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('events');
    }
}
