<?php

namespace Tests\Unit;

use App\Models\Itinerary\Season;
use App\Services\ItineraryService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The parts of ItineraryService that are pure date/number logic. Generation end-to-end lives in
 * tests/Feature/ItineraryGenerationTest.
 */
class ItineraryServiceTest extends TestCase
{
    private ItineraryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ItineraryService();
    }

    public function test_start_and_end_date_set_an_inclusive_duration(): void
    {
        $itinerary = $this->service->getItinerary();
        $itinerary->setStartDate('2027-06-01');
        $itinerary->setEndDate('2027-06-05');
        $this->service->determineAndSetMissingDates();
        $this->assertSame(5, $itinerary->getDurationDays());
    }

    public function test_start_date_and_duration_set_an_inclusive_end_date(): void
    {
        $itinerary = $this->service->getItinerary();
        $itinerary->setStartDate('2027-06-01');
        $itinerary->setDurationDays(5);
        $this->service->determineAndSetMissingDates();
        $this->assertSame('2027-06-05', $itinerary->getEndDate());
    }

    public function test_end_date_and_duration_round_trip(): void
    {
        foreach ([1, 2, 5, 14] as $duration) {
            $end = $this->service->calculateEndDate('2027-02-27', $duration);
            $days = (new \DateTime('2027-02-27'))->diff(new \DateTime($end))->days + 1;
            $this->assertSame($duration, $days);
        }
    }

    public function test_month_only_picks_a_date_in_that_month(): void
    {
        $itinerary = $this->service->getItinerary();
        $itinerary->setMonth(9);
        $itinerary->setDurationDays(4);
        $this->service->determineAndSetMissingDates();

        $this->assertSame('09', date('m', strtotime($itinerary->getStartDate())));
        $this->assertGreaterThanOrEqual(date('Y-m-d', strtotime('+2 weeks')), $itinerary->getStartDate());
        $this->assertSame($this->service->calculateEndDate($itinerary->getStartDate(), 4), $itinerary->getEndDate());
    }

    public function test_season_only_picks_a_future_date_in_that_season(): void
    {
        $itinerary = $this->service->getItinerary();
        $itinerary->setSeason('summer');
        $itinerary->setDurationDays(3);
        $this->service->determineAndSetMissingDates();

        $this->assertSame('summer', Season::get($itinerary->getStartDate()));
        $this->assertGreaterThan(date('Y-m-d'), $itinerary->getStartDate());
    }

    public static function months(): array
    {
        return array_map(fn($m) => [$m], range(1, 12));
    }

    #[DataProvider('months')]
    public function test_random_date_in_month_is_in_that_month_and_two_weeks_out(int $month): void
    {
        $now = new \DateTime('2026-11-22');
        for ($i = 0; $i < 15; $i++) {
            $date = $this->service->getRandomDateInMonth($month, null, $now);
            $this->assertSame($month, (int) date('n', strtotime($date)), $date);
            $this->assertGreaterThanOrEqual('2026-12-06', $date);
            $this->assertLessThanOrEqual('2028-01-01', $date, 'Should not skip ahead more than a year.');
        }
    }

    public function test_month_string_from_request_is_accepted(): void
    {
        $date = $this->service->getRandomDateInMonth('6', null, new \DateTime('2026-11-22'));
        $this->assertStringStartsWith('2027-06-', $date);
    }

    public static function trailDayCases(): array
    {
        // duration, adventurousness, expected trail days. Middle days = duration - 2.
        return [
            'short trip, timid' => [3, 0.0, 1],
            'week, timid' => [7, 0.1, 2],
            'week, average' => [7, 0.5, 3],
            'week, bold' => [7, 1.0, 5],
            'fortnight, bold' => [14, 1.0, 12],
        ];
    }

    #[DataProvider('trailDayCases')]
    public function test_trail_days_scale_with_adventurousness(int $duration, float $adventurousness, int $expected): void
    {
        $itinerary = $this->service->getItinerary();
        $itinerary->setDurationDays($duration);
        $itinerary->setAdventurousness($adventurousness);
        $this->assertSame($expected, $this->service->calculateTotalPossibleTrailDays());
    }

    public function test_trail_days_never_exceed_middle_days_and_never_drop_below_one(): void
    {
        $itinerary = $this->service->getItinerary();
        $previous = 0;
        foreach (range(0, 10) as $step) {
            $itinerary->setDurationDays(10);
            $itinerary->setAdventurousness($step / 10);
            $days = $this->service->calculateTotalPossibleTrailDays();
            $this->assertGreaterThanOrEqual(1, $days);
            $this->assertLessThanOrEqual(8, $days);
            $this->assertGreaterThanOrEqual($previous, $days, 'Monotonic in adventurousness.');
            $previous = $days;
        }
    }
}
