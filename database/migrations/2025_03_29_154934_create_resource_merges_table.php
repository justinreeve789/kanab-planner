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
        Schema::create('resource_merges', function (Blueprint $table) {
            $table->increments('id');
            $table->string('resource_table');
            $table->integer('merged_from')->unsigned();
            $table->integer('merged_to')->unsigned();
            $table->timestamps();
        });

        self::importTable('resource_merges');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_merges');
    }
};
