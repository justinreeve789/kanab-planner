<?php

use App\Models\Itinerary\Period;
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
        Schema::create('itinerary_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('day_id');
            $table->enum('type', [Period::MORNING, Period::AFTERNOON, Period::EVENING, Period::NIGHT]);
            $table->timestamps();
            $table->index('type');
            $table->unique(['day_id', 'type']);
            $table->foreign('day_id')->references('id')->on('itinerary_days')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itinerary_periods');
    }
};
