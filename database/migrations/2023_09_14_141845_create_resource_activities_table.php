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
        Schema::dropIfExists('resource_activity_types');
        Schema::create('resource_activity_types', function (Blueprint $table) {
            $table->string('resource_table', 16);
            $table->integer('resource_id')->unsigned();
            $table->integer('activity_type_id')->unsigned();
            $table->timestamps();
            $table->index(['resource_table', 'resource_id']);
            $table->index('activity_type_id');
            $table->unique(['resource_table', 'resource_id', 'activity_type_id'], 'rt_ri_ati');
//            $table->foreign('activity_type_id')->references('id')->on('activity_types')->onDelete('cascade');
//            $table->foreign('resource_id')->references('id')->on('resources')->onDelete('cascade');
        });

        if (file_exists('./database/dumps/resource_activity_types.sql')) {
            DB::unprepared(file_get_contents('./database/dumps/resource_activity_types.sql'));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_activities');
    }
};
