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
        Schema::create('accommodations', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->integer('location_id')->unsigned()->nullable();
            $table->integer('accommodation_type_id')->unsigned();
            $table->string('hours')->nullable();
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->timestamps();
            $table->index('accommodation_type_id');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
        });

        DB::table('accommodations')->insert([
            ['name' => 'Tranquility Trails', 'location_id' => 1, 'accommodation_type_id' => 1],
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
