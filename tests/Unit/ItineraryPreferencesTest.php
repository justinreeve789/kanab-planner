<?php

namespace Tests\Unit;

use App\Models\Itinerary\Itinerary;
use Tests\TestCase;

/**
 * The Itinerary model's preference handling. No database: nothing here should need one.
 */
class ItineraryPreferencesTest extends TestCase
{
    public function test_defaults(): void
    {
        $itinerary = new Itinerary();
        $this->assertSame(Itinerary::DEFAULT_ADVENTUROUSNESS, $itinerary->getAdventurousness());
        $this->assertSame(Itinerary::DEFAULT_SOLITARINESS, $itinerary->getSolitariness());
        $this->assertSame(Itinerary::DEFAULT_STRENUOUSNESS, $itinerary->getStrenuousness());
        $this->assertSame(Itinerary::DEFAULT_DURATION_DAYS, $itinerary->getDurationDays());
        $this->assertSame(Itinerary::DEFAULT_BASE_AREA_ID, $itinerary->getBaseAreaId());
        $this->assertNotEmpty((string) $itinerary->id);
    }

    public function test_zero_preferences_are_respected_not_replaced_with_defaults(): void
    {
        $itinerary = new Itinerary();
        $itinerary->setAdventurousness(0.0);
        $itinerary->setSolitariness(0.0);
        $itinerary->setStrenuousness(0.0);

        $this->assertSame(0.0, $itinerary->getAdventurousness());
        $this->assertSame(0.0, $itinerary->getSolitariness());
        $this->assertSame(0.0, $itinerary->getStrenuousness());
    }

    public function test_missing_preferences_fall_back_to_defaults(): void
    {
        $itinerary = new Itinerary();
        $itinerary->setAdventurousness(null);
        $this->assertSame(Itinerary::DEFAULT_ADVENTUROUSNESS, $itinerary->getAdventurousness());
    }

    public function test_trail_distance_window_grows_with_adventurousness_and_strenuousness(): void
    {
        $timid = $this->itinerary(0.1, 0.1);
        $average = $this->itinerary(0.5, 0.5);
        $bold = $this->itinerary(0.9, 0.9);

        $this->assertLessThan($average->calculateMaximumTrailDistance(), $timid->calculateMaximumTrailDistance());
        $this->assertGreaterThanOrEqual(0.5, $timid->calculateMaximumTrailDistance(), 'Never shorter than half a mile.');

        $this->assertSame(0.1, $average->calculateMinimumTrailDistance());
        $this->assertGreaterThan($average->calculateMinimumTrailDistance(), $bold->calculateMinimumTrailDistance());

        foreach ([$timid, $average, $bold] as $itinerary) {
            $this->assertLessThan($itinerary->calculateMaximumTrailDistance(), $itinerary->calculateMinimumTrailDistance());
        }
    }

    public function test_arriving_early_uses_the_first_day(): void
    {
        $itinerary = new Itinerary();
        $itinerary->setStartDate('2027-06-01');
        $itinerary->arrival_date_time = '2027-06-01 09:00:00';
        $this->assertTrue($itinerary->getUseFirstDay());

        $itinerary->arrival_date_time = '2027-06-01 16:00:00';
        $this->assertFalse($itinerary->getUseFirstDay());
    }

    public function test_leaving_late_uses_the_last_day(): void
    {
        $itinerary = new Itinerary();
        $itinerary->setEndDate('2027-06-05');
        $itinerary->departure_date_time = '2027-06-05 18:00:00';
        $this->assertTrue($itinerary->getUseLastDay());

        $itinerary->departure_date_time = '2027-06-05 10:00:00';
        $this->assertFalse($itinerary->getUseLastDay());
    }

    public function test_default_departure_is_end_date_at_checkout(): void
    {
        $itinerary = new Itinerary();
        $itinerary->setEndDate('2027-06-05');
        $this->assertSame('2027-06-05 10:00:00', $itinerary->getDepartureDateTime());
    }

    public function test_priorities_accept_arrays_or_comma_separated_strings(): void
    {
        $itinerary = new Itinerary();
        $itinerary->setTrailPriorities('12,34');
        $this->assertSame([12, 34], $itinerary->getTrailPriorities());

        $itinerary->setTrailPriorities([5]);
        $this->assertSame([5], $itinerary->getTrailPriorities());

        $itinerary->setTrailPriorities(null);
        $this->assertSame([], $itinerary->getTrailPriorities());

        $itinerary->setActivityPriorities(null);
        $this->assertSame(['hiking'], $itinerary->getActivityPriorities());
    }

    public function test_shuffled_priorities_keep_every_value(): void
    {
        $itinerary = new Itinerary();
        $itinerary->setTrailPriorities([1, 2, 3, 4, 5, 6]);
        $shuffled = $itinerary->getTrailPriorities(true);
        sort($shuffled);
        $this->assertSame([1, 2, 3, 4, 5, 6], $shuffled);
    }

    public function test_month_must_be_a_real_month(): void
    {
        $itinerary = new Itinerary();
        $itinerary->setMonth(13);
        $this->assertNull($itinerary->getMonth());
        $itinerary->setMonth(7);
        $this->assertSame(7, $itinerary->getMonth());
    }

    public function test_season_comes_from_the_start_date_when_there_is_one(): void
    {
        $itinerary = new Itinerary();
        $itinerary->setStartDate('2027-07-04');
        $itinerary->setSeason('winter');
        $this->assertSame('summer', $itinerary->getSeason());

        $noDates = new Itinerary();
        $noDates->setSeason('fall');
        $this->assertSame('fall', $noDates->getSeason());

        $bogus = new Itinerary();
        $bogus->setSeason('monsoon');
        $this->assertContains($bogus->getSeason(), ['spring', 'summer', 'fall', 'winter']);
    }

    public function test_activity_times_are_normalized_to_time_of_day(): void
    {
        $this->assertSame('08:00:00', Itinerary::normalizeTime('2027-06-02 08:00:00'));
        $this->assertSame('08:00:00', Itinerary::normalizeTime('08:00'));
        $this->assertSame('23:59:59', Itinerary::normalizeTime('23:59:59'));
        $this->assertNull(Itinerary::normalizeTime(null));
    }

    private function itinerary(float $adventurousness, float $strenuousness): Itinerary
    {
        $itinerary = new Itinerary();
        $itinerary->setAdventurousness($adventurousness);
        $itinerary->setStrenuousness($strenuousness);
        return $itinerary;
    }
}
