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
        Schema::create('resource_reviews', function (Blueprint $table) {
            $table->increments('id');
            $table->string('source_type');
            $table->string('source_id');
            $table->string('resource_table');
            $table->integer('resource_id')->unsigned();
            $table->string('title')->nullable();
            $table->string('author')->nullable();
            $table->date('posted_at')->nullable();
            $table->text('comment');
            $table->text('raw_data')->nullable();
            $table->boolean('is_usable')->default(true);
            $table->timestamps();
        });

        if (file_exists('./database/dumps/resource_reviews.sql')) {
            DB::unprepared(file_get_contents('./database/dumps/resource_reviews.sql'));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_reviews');
    }
};
