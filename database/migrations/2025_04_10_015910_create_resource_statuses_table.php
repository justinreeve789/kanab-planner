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
        Schema::create('resource_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('source_type');
            $table->string('source_id');
            $table->string('resource_table')->nullable();
            $table->integer('resource_id')->nullable();
            $table->boolean('is_open')->default(true);
            $table->string('message')->nullable();
            $table->string('info_url')->nullable();
            $table->text('raw_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_statuses');
    }
};
