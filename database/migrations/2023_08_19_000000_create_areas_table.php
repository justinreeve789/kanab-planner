<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->float('latitude', 10, 7)->nullable();
            $table->float('longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        DB::table('areas')->insert([
            ['name' => 'Bryce Canyon'],
            ['name' => 'Grand Canyon (North Rim)'],
            ['name' => 'Kanab, Utah'],
            ['name' => 'Lake Powell'],
            ['name' => 'Page, Arizona'],
            ['name' => 'St. George, Utah'],
            ['name' => 'Zion'],
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
