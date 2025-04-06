<?php

use App\Traits\ImportTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use ImportTable;

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

        self::importTable('openai_prompts');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('openai_prompts');
    }
};
