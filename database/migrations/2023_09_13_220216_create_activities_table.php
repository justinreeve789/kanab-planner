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
        Schema::create('activities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->decimal('adventurousness', 3, 2)->comment('The uniqueness of the experience, and how outside an average person\'s comfort zone it is');
            $table->decimal('solitariness', 3, 2)->comment('How much a person who enjoys less-crowded or non-human-interacting experiences would enjoy it');
            $table->decimal('strenuousness', 3, 2)->comment('How physically exerting the experience is');
//            $table->decimal('childfriendliness', 3, 2)->nullable()->comment('How suitable the experience is for children');
//            $table->decimal('lengthiness', 3, 2)->nullable();
//            $table->decimal('outbackiness', 3, 2)->nullable()->comment('How remote the experience is');
//            $table->decimal('weatheriness', 3, 2)->nullable()->comment('How dependents the experience is on the weather');
//            $table->decimal('noisiness', 3, 2)->nullable();
        });

        DB::table('activities')->insert([
            ['name' => 'bicycling', 'adventurousness' => 0.5, 'solitariness' => 0.7, 'strenuousness' => 0.6],
            ['name' => 'boating', 'adventurousness' => 0.7, 'solitariness' => 0.3, 'strenuousness' => 0.4],
            ['name' => 'concert', 'adventurousness' => 0.5, 'solitariness' => 0.1, 'strenuousness' => 0.2],
            ['name' => 'conferences-and-conventions', 'adventurousness' => 0.0, 'solitariness' => 0.0, 'strenuousness' => 0.0],
            ['name' => 'dining', 'adventurousness' => 0.1, 'solitariness' => 0.2, 'strenuousness' => 0],
            ['name' => 'educational-fun', 'adventurousness' => 0.5, 'solitariness' => 0.1, 'strenuousness' => 0.2],
            ['name' => 'endurance-race', 'adventurousness' => 0.8, 'solitariness' => 0.1, 'strenuousness' => 1.0],
//            ['name' => 'event', 'adventurousness' => 0.0, 'solitariness' => 0.0, 'strenuousness' => 0.0],
            ['name' => 'festivals-and-celebrations', 'adventurousness' => 0.8, 'solitariness' => 0.1, 'strenuousness' => 0.2],
            ['name' => 'hiking', 'adventurousness' => 0.6, 'solitariness' => 0.5, 'strenuousness' => 0.6],
            ['name' => 'museum', 'adventurousness' => 0.3, 'solitariness' => 0.3, 'strenuousness' => 0.1],
            ['name' => 'offroading', 'adventurousness' => 0.8, 'solitariness' => 0.3, 'strenuousness' => 0.4],
            ['name' => 'rock-climbing', 'adventurousness' => 0.6, 'solitariness' => 0.2, 'strenuousness' => 0.8],
            ['name' => 'rodeo', 'adventurousness' => 0.6, 'solitariness' => 0.1, 'strenuousness' => 0.3],
            ['name' => 'shopping', 'adventurousness' => 0, 'solitariness' => 0.1, 'strenuousness' => 0],
            ['name' => 'sightseeing', 'adventurousness' => 0.3, 'solitariness' => 0.5, 'strenuousness' => 0],
            ['name' => 'stargazing', 'adventurousness' => 0.1, 'solitariness' => 0.7, 'strenuousness' => 0],
            ['name' => 'swimming', 'adventurousness' => 0.2, 'solitariness' => 0.2, 'strenuousness' => 0.1],
            ['name' => 'tour', 'adventurousness' => 0.5, 'solitariness' => 0.1, 'strenuousness' => 0.2],
            ['name' => 'unknown', 'adventurousness' => 0.4, 'solitariness' => 0.3, 'strenuousness' => 0.2],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
