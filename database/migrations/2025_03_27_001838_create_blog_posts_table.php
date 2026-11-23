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
        Schema::create('blog_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('blog_id')->unsigned();
            $table->integer('post_id')->unsigned();
            $table->string('title');
            $table->mediumText('content')->nullable();
            $table->mediumText('raw_data')->nullable();
            $table->string('resource_table')->nullable();
            $table->integer('resource_id')->unsigned()->nullable();
            $table->dateTime('posted_at');
            $table->timestamps();
            $table->unique(['blog_id', 'post_id']);
            $table->index(['resource_table', 'resource_id']);
            $table->foreign('blog_id')->references('id')->on('blogs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_posts');
    }
};
