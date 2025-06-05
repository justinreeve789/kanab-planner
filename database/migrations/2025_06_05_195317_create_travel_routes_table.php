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
        Schema::create('travel_routes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('lat_origin', 16);
            $table->string('lng_origin', 16);
            $table->string('lat_dest', 16);
            $table->string('lng_dest', 16);
            $table->float('distance_miles', 10);
            $table->float('duration_hours', 10);
            $table->index(['lat_origin', 'lng_origin']);
            $table->index(['lat_dest', 'lng_dest']);
            $table->unique(['lat_origin', 'lng_origin', 'lat_dest', 'lng_dest']);
            $table->timestamps();
        });

        self::importTable('travel_routes');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_routes');
    }
};
