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
        Schema::create('assistant_messages', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('assistant_session_id')->unsigned();
            $table->boolean('is_assistant');
            $table->text('content');
            $table->string('resource_table')->nullable();
            $table->integer('resource_id')->unsigned()->nullable();
            $table->boolean('show_in_chat_bubble')->default(true);
            $table->string('method_executed', 32)->nullable();
            $table->string('method_params')->nullable();
            $table->timestamps();
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
        Schema::dropIfExists('assistant_messages');
    }
};
