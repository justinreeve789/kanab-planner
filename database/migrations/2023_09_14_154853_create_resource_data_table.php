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
        Schema::dropIfExists('resource_data');
        Schema::create('resource_data', function (Blueprint $table) {
            $table->increments('id');
            $table->string('resource_table', 16);
            $table->integer('resource_id')->unsigned();
            $table->integer('activity_type_id')->unsigned()->nullable();
            $table->decimal('adventurousness', 3, 2)->nullable();
            $table->decimal('solitariness', 3, 2)->nullable();
            $table->decimal('strenuousness', 3, 2)->nullable();
            $table->timestamps();
//            $table->unique(['resource_table', 'resource_id', 'activity_id']);
            $table->index('resource_table');
            $table->index('activity_type_id');
            $table->index(['resource_table', 'activity_type_id']);
            $table->foreign('activity_type_id')->references('id')->on('activity_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_data');
    }
};
