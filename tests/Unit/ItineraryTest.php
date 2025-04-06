<?php

namespace Tests\Unit;

use Tests\TestCase;

class ItineraryTest extends TestCase
{
    public function test_starts_and_ends_at_base_location_when_set(): void
    {
        $this->assertTrue(true);
    }

    public function test_doesnt_always_start_and_end_at_base_location_when_set(): void
    {
        $this->assertTrue(true);
    }

    public function test_activity_start_time_accounts_for_long_travel(): void
    {
        // For example, base area is Grand Canyon, traveling to Bryce Canyon for a morning hike. This shouldn't happen.
        $this->assertTrue(true);
    }

    public function test_total_driving_distance_matches_adventurousness(): void
    {
        // Track the average for different adventurousness levels.
        $this->assertTrue(true);
    }

    public function test_assortment_of_areas(): void
    {
        // Try different adventurousness, solitariness, and strenuousness settings and assign each area as a base area,
        // to make sure sensible areas come up on the itinerary.
        $this->assertTrue(true);
    }

    public function test_days_are_in_order(): void
    {
        $this->assertTrue(true);
    }

    public function test_merged_trails_arent_being_used(): void
    {
        // If trails have been merged into a new trail, make sure the old trails aren't being used in any itineraries.
        $this->assertTrue(true);
    }

    public function test_total_days_matches_duration_of_trip(): void
    {
        $this->assertTrue(true);
    }
}
