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
        Schema::create('resource_busy', function (Blueprint $table) {
            $table->increments('id');
            $table->string('resource_table');
            $table->integer('resource_id')->unsigned();
            $table->date('collected_date');
            $table->string('day')->nullable();
            $table->tinyInteger('day_of_week')->unsigned();
            $table->tinyInteger('hour')->unsigned();
            $table->tinyInteger('percentage')->unsigned();
            $table->timestamps();
            $table->index(['resource_table', 'resource_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_busy');
    }
};
