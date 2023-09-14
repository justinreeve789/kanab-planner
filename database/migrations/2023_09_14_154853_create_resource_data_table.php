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
        Schema::dropIfExists('resource_data');
        Schema::create('resource_data', function (Blueprint $table) {
            $table->string('resource_table', 16);
            $table->integer('resource_id');
            $table->decimal('adventurousness', 3, 2);
            $table->decimal('solitude', 3, 2);
            $table->timestamps();
            $table->primary(['resource_table', 'resource_id']);
            $table->index('resource_table');
        });

        if (file_exists('./database/dumps/resource_data.sql')) {
            DB::unprepared(file_get_contents('./database/dumps/resource_data.sql'));
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_data');
    }
};
