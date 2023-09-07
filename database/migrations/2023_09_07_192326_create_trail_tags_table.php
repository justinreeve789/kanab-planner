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
        Schema::create('trail_tags', function (Blueprint $table) {
            $table->integer('trail_id')->unsigned();
            $table->string('tag');
            $table->timestamps();
            $table->primary(['trail_id', 'tag']);
            $table->foreign('trail_id')->references('id')->on('trails')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trail_tags');
    }
};
