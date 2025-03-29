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
        Schema::create('trail_merges', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('merged_from')->unsigned();
            $table->integer('merged_to')->unsigned();
            $table->timestamps();
        });

        if (file_exists('./database/dumps/trail_merges.sql')) {
            DB::unprepared(file_get_contents('./database/dumps/trail_merges.sql'));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trail_merges');
    }
};
