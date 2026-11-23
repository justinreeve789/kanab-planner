<?php

use App\Models\Area;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Areas that close for part of the year, as month-day boundaries (inclusive; may wrap the new year).
 * The North Rim of the Grand Canyon is closed December 1 – May 14.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->char('closed_from', 5)->nullable()->comment('MM-DD, inclusive')->after('longitude');
            $table->char('closed_until', 5)->nullable()->comment('MM-DD, inclusive')->after('closed_from');
        });
        DB::table('areas')->where('id', Area::NORTH_RIM)->update(['closed_from' => '12-01', 'closed_until' => '05-14']);
    }

    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropColumn(['closed_from', 'closed_until']);
        });
    }
};
