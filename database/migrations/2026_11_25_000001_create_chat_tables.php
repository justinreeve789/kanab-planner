<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storage for the trip assistant (app/Chat):
 *  - trail_profiles: grades and steep sections computed from each GPS track (php artisan trails:profile)
 *  - resource_facts: short claims pulled from blogs and reviews, by topic (php artisan chat:extract-facts)
 *  - the conversation transcript on each thread, and the sources behind each assistant message
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trail_profiles', function (Blueprint $table) {
            $table->integer('trail_id')->unsigned()->primary();
            $table->decimal('track_miles', 6, 2);
            $table->integer('gain_ft');
            $table->integer('loss_ft');
            $table->integer('min_elevation_ft');
            $table->integer('max_elevation_ft');
            $table->decimal('steepest_sustained_pct', 5, 1)->nullable()->comment('Steepest 0.1 mile, either direction');
            $table->decimal('steepest_pitch_pct', 5, 1)->nullable()->comment('Steepest 0.05 mile, either direction');
            $table->decimal('gentle_share', 3, 2)->comment('Share of the track under the gentle grade');
            $table->json('data')->comment('Full TrailProfiler output: sections, longest climb, elevation series');
            $table->string('source_hash', 64)->comment('sha256 of the GeoJSON it was computed from');
            $table->timestamps();
            $table->foreign('trail_id')->references('id')->on('trails')->onDelete('cascade');
        });

        Schema::create('resource_facts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('resource_table', 16);
            $table->integer('resource_id')->unsigned();
            $table->string('topic', 24)->comment('See App\Chat\Data\Knowledge::TOPICS');
            $table->string('claim', 400);
            $table->string('source_type', 16)->comment('blog_post, review or official');
            $table->integer('source_id')->unsigned()->nullable();
            $table->string('source_url')->nullable();
            $table->date('source_date')->nullable();
            $table->decimal('confidence', 3, 2)->default(0.7);
            $table->timestamps();
            $table->index(['resource_table', 'resource_id', 'topic']);
            $table->index(['source_type', 'source_id']);
        });

        Schema::table('assistant_threads', function (Blueprint $table) {
            $table->longText('transcript')->nullable()->comment('Provider-neutral chat transcript (App\Chat\Models\ChatModel)');
            $table->uuid('itinerary_id')->nullable()->index();
        });

        Schema::table('assistant_messages', function (Blueprint $table) {
            $table->json('sources')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('assistant_messages', fn(Blueprint $table) => $table->dropColumn('sources'));
        Schema::table('assistant_threads', function (Blueprint $table) {
            $table->dropIndex(['itinerary_id']);
            $table->dropColumn(['transcript', 'itinerary_id']);
        });
        Schema::dropIfExists('resource_facts');
        Schema::dropIfExists('trail_profiles');
    }
};
