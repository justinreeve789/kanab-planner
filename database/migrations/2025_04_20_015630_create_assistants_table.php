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
        Schema::create('assistants', function (Blueprint $table) {
            $table->increments('id');
            $table->string('assistant_id');
            $table->string('assistant_type')->default('openai_assistant');
            $table->string('resource_table')->nullable();
            $table->integer('resource_id')->unsigned()->nullable();
            $table->timestamps();
            $table->unique(['assistant_id', 'assistant_type']);
            $table->unique(['assistant_type', 'resource_table', 'resource_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assistants');
    }
};
