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
            $table->integer('location_id')->unsigned()->nullable();
            $table->text('description')->nullable();
//            $table->integer('restaurant_type_id')->unsigned()->nullable();
            $table->string('days_open')->nullable();
            $table->string('hours_open')->nullable();
            $table->smallInteger('average_price')->unsigned()->default(15)->comment('Average price of a meal per person');
            $table->decimal('deviation_percent', 3, 2)->unsigned()->default(0.2)->comment('Percentage +/- deviation in average price (e.g. an average price of $20 with a deviation of 20% would be $16-$24)');
            $table->timestamps();
//            $table->index('restaurant_type_id');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });

        if (file_exists('./database/dumps/restaurants.sql')) {
            DB::unprepared(file_get_contents('./database/dumps/restaurants.sql'));
        }
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
