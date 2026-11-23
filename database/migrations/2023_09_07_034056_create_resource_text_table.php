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
        Schema::create('resource_text', function (Blueprint $table) {
            $table->increments('id');
            $table->string('resource_table', 16);
            $table->integer('resource_id');
            $table->string('type', 16);
            $table->string('source_url')->nullable();
            $table->mediumText('raw')->nullable();
            $table->mediumText('clean')->nullable();
            $table->timestamps();
            $table->index(['resource_table', 'resource_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_text');
    }
};
