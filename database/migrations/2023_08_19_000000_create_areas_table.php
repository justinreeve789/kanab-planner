<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAreasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('short_name');
            $table->float('latitude', 10, 7)->nullable();
            $table->float('longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        DB::table('areas')->insert([
            ['name' => 'Bryce Canyon', 'short_name' => 'Bryce', 'latitude' => 37.5930, 'longitude' => -112.1871],
            ['name' => 'Grand Canyon (North Rim)', 'short_name' => 'North Rim', 'latitude' => 36.2116, 'longitude' => -112.0581],
            ['name' => 'Kanab, Utah', 'short_name' => 'Kanab', 'latitude' => 37.0475, 'longitude' => -112.5263],
            ['name' => 'Lake Powell', 'short_name' => 'Lake Powell', 'latitude' => 36.9269, 'longitude' => -111.4747],
            ['name' => 'Page, Arizona', 'short_name' => 'Page', 'latitude' => 36.9147, 'longitude' => -111.4554],
            ['name' => 'St. George, Utah', 'short_name' => 'St. George', 'latitude' => 37.0965, 'longitude' => -  113.5684],
            ['name' => 'Zion', 'short_name' => 'Zion', 'latitude' => 37.2982, 'longitude' => -113.0263],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('areas');
    }
}
