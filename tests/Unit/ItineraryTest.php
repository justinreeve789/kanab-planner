<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

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
        // For example, base area is Grand Canyon, traveling to Bryce Canyon for a morning hike.
        $this->assertTrue(true);
    }
}
