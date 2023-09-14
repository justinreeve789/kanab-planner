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
        Schema::dropIfExists('resource_activities');
        Schema::create('resource_activities', function (Blueprint $table) {
            $table->string('resource_table', 16);
            $table->integer('resource_id');
            $table->integer('activity_id');
            $table->timestamps();
            $table->index(['resource_table', 'resource_id']);
            $table->index('activity_id');
            $table->unique(['resource_table', 'resource_id', 'activity_id'], 'rt_ri_ai');
        });

        if (file_exists('./database/dumps/resource_activities.sql')) {
            DB::unprepared(file_get_contents('./database/dumps/resource_activities.sql'));
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
