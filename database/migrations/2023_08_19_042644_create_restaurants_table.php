<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRestaurantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('source_type');
            $table->string('source_id');
            $table->integer('location_id')->unsigned()->nullable();
            $table->text('description')->nullable();
            $table->smallInteger('average_price')->unsigned()->default(15)->comment('Average price of a meal per person');
            $table->decimal('deviation_percent', 3, 2)->unsigned()->default(0.2)->comment('Percentage +/- deviation in average price (e.g. an average price of $20 with a deviation of 20% would be $16-$24)');
            $table->boolean('curbside_pickup')->nullable();
            $table->boolean('dine_in')->nullable();
            $table->boolean('reservable')->nullable();
            $table->text('raw_data')->nullable();
            $table->boolean('is_usable')->default(true);
            $table->boolean('is_merged')->default(false)->comment('If true, this is an amalgamation of multiple records, recorded in resource_merges');
            $table->timestamps();
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('restaurants');
    }
}
