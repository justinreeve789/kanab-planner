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
        Schema::create('openai_prompts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('source_table');
            $table->integer('source_id')->unsigned();
            $table->string('prompt');
            $table->text('answer');
            $table->timestamps();
            $table->index(['source_table', 'source_id']);
        });

        if (file_exists('./database/dumps/openai_prompts.sql')) {
            DB::unprepared(file_get_contents('./database/dumps/openai_prompts.sql'));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('openai_prompts');
    }
};
