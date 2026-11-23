<?php

namespace Tests\Unit;

use App\Models\Itinerary\Season;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SeasonTest extends TestCase
{
    public static function datesAndSeasons(): array
    {
        return [
            'spring start' => ['2027-03-20', 'spring'],
            'spring end' => ['2027-06-20', 'spring'],
            'summer start' => ['2027-06-21', 'summer'],
            'mid summer, next year' => ['2027-07-15', 'summer'],
            'summer two years out' => ['2028-08-01', 'summer'],
            'fall start' => ['2026-09-22', 'fall'],
            'fall end' => ['2026-12-20', 'fall'],
            'winter start' => ['2026-12-21', 'winter'],
            'new year' => ['2027-01-01', 'winter'],
            'early this year' => [date('Y') . '-02-01', 'winter'],
            'winter end' => ['2027-03-19', 'winter'],
        ];
    }

    #[DataProvider('datesAndSeasons')]
    public function test_season_for_date_is_independent_of_the_current_year(string $date, string $expected): void
    {
        $this->assertSame($expected, Season::get($date));
    }

    public function test_unparseable_date_has_no_season(): void
    {
        $this->assertNull(Season::get('not a date'));
    }

    public static function seasons(): array
    {
        return [['spring'], ['summer'], ['fall'], ['winter']];
    }

    #[DataProvider('seasons')]
    public function test_random_date_is_inside_the_season_and_at_least_two_weeks_out(string $season): void
    {
        $today = new \DateTimeImmutable('2026-11-22');
        for ($i = 0; $i < 25; $i++) {
            $date = Season::randomDateInSeason($season, $today);
            $this->assertSame($season, Season::get($date), "$date should be in $season");
            $this->assertGreaterThanOrEqual('2026-12-06', $date, 'Dates must be at least two weeks from today.');
        }
    }

    public function test_random_date_rolls_to_next_year_once_this_years_season_has_passed(): void
    {
        $date = Season::randomDateInSeason('summer', new \DateTimeImmutable('2026-11-22'));
        $this->assertStringStartsWith('2027-', $date);
    }

    public function test_random_date_rejects_unknown_season(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Season::randomDateInSeason('monsoon');
    }
}
