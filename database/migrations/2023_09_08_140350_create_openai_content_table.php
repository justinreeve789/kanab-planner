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
        Schema::create('openai_content', function (Blueprint $table) {
            $table->increments('id');
            $table->string('resource_table', 16);
            $table->integer('resource_id');
            $table->string('data_type');
            $table->text('source');
            $table->text('data');
            $table->timestamps();
            $table->index(['resource_table', 'resource_id']);
            $table->index(['resource_table', 'resource_id', 'data_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('openai_content');
    }
};
