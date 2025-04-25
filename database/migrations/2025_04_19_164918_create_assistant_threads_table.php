<?php

use App\Traits\ImportTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use ImportTable;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assistant_threads', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('assistant_session_id')->unsigned();
            $table->string('thread_key')->nullable()->comment('For the OpenAI Responses API, this is the response_id, and should be updated after each query. It can be sent to the next query as the previous_response_id parameter.');
            $table->string('thread_type')->nullable();
            $table->string('resource_table')->nullable();
            $table->integer('resource_id')->unsigned()->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['thread_key', 'thread_type']);
            $table->index(['resource_table', 'resource_id']);
            $table->foreign('assistant_session_id')->references('id')->on('assistant_sessions')->onDelete('cascade');
        });

        self::importTable('assistant_messages');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assistant_threads');
    }
};
