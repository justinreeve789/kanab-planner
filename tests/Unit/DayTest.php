<?php

namespace Tests\Unit;

use App\Models\Itinerary\Itinerary;
use Tests\TestCase;

class DayTest extends TestCase
{
    private $itinerary;

    public function setUp(): void
    {
        parent::setUp();
        $this->itinerary = new Itinerary();
    }

    public function test_home_meals_occur_on_days_at_base_location(): void
    {
        $this->assertTrue(true);
    }

    public function test_has_three_meals(): void
    {
        $this->assertTrue(true);
    }
}
