<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accommodations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->integer('location_id')->unsigned()->nullable();
            $table->integer('accommodation_type_id')->unsigned();
            $table->text('description')->nullable();
            $table->string('url')->nullable();
            $table->string('hours')->nullable();
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->boolean('is_usable')->default(true);
            $table->timestamps();
            $table->index('accommodation_type_id');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });

        DB::table('accommodations')->insert([
            [
                'name' => 'Tranquility Trails',
                // Location #1 only exists when the dumps are imported (see ImportTable / DB_IMPORT_DUMPS).
                'location_id' => DB::table('locations')->where('id', 1)->exists() ? 1 : null,
                'accommodation_type_id' => 1,
                'description' => 'At Tranquility Trails, you\'re sure to notice the genuine care and attention to detail when you are a guest! With 90+ amenities, this heartfelt owner-managed home is perfect to explore Zion, Bryce and Grand Canyon National Parks and more! This 2-story, 3-bed/2-bath townhome can keep 6 guests comfortable. One of the few spots in Kanab with a pool and hot tub! It has a full-use kitchen with dishwasher, washer/dryer and TVs in the front room and main bedroom. The patio has a BBQ, seating and hammock. Enjoy free access to Disney+, Hulu, and HBO Max.',
                'url' => 'https://visitkanab.org',
                'check_in' => '16:00',
                'check_out' => '10:00',
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accommodations');
    }
};
