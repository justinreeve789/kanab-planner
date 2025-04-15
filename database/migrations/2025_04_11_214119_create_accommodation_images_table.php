<?php

use App\Traits\ImportTable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use ImportTable;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accommodation_images', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('accommodation_id')->unsigned();
            $table->string('url')->nullable();
            $table->string('local_url')->nullable();
            $table->string('medium_local_url')->nullable();
            $table->string('thumbnail_local_url')->nullable();
            $table->integer('size')->unsigned()->nullable();
            $table->integer('medium_size')->unsigned()->nullable();
            $table->integer('thumbnail_size')->unsigned()->nullable();
            $table->timestamps();
            $table->foreign('accommodation_id')->references('id')->on('accommodations')->onDelete('cascade');
        });

        $data = [];
        for ($i = 1; $i <= 3; $i++) {
            $data[] = [
                'accommodation_id' => 1,
                'url' => 'https://visitkanab.org',
                'local_url' => 'visitkanab/temp/tranquility-trails-' . $i . '.jpg',
                'medium_local_url' => 'visitkanab/temp/tranquility-trails-' . $i . '.jpg',
                'thumbnail_local_url' => 'visitkanab/temp/tranquility-trails-' . $i . '.jpg',
                'size' => 100000,
                'medium_size' => 100000,
                'thumbnail_size' => 100000,
            ];
        }

        DB::table('accommodation_images')->insert($data);

        // self::importTable('accommodation_images');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accommodation_images');
    }
};
