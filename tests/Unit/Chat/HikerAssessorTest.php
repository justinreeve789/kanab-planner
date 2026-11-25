<?php

namespace Tests\Unit\Chat;

use App\Chat\Data\HikerAssessor;
use Tests\TestCase;

class HikerAssessorTest extends TestCase
{
    private function profile(array $sections = [], float $sustained = 8, float $pitch = 10, float $at = 1.0): array
    {
        return [
            'steepest_sustained' => ['grade_pct' => $sustained, 'direction' => 'up', 'starts_at_mile' => $at, 'length_miles' => 0.1],
            'steepest_pitch' => ['grade_pct' => $pitch, 'direction' => 'up', 'starts_at_mile' => $at, 'length_miles' => 0.05],
            'steep_sections' => $sections,
            'very_steep_sections' => array_values(array_filter($sections, fn($s) => $s['max_grade_pct'] >= 25)),
        ];
    }

    private function trip(float $miles, int $gain, float $hours): array
    {
        return ['miles' => $miles, 'gain_ft' => $gain, 'hours' => $hours, 'distance_note' => 'test'];
    }

    public function test_picks_bands_by_age_and_fitness(): void
    {
        $a = HikerAssessor::fromConfig();
        $this->assertSame('carried', $a->bandFor(2, null));
        $this->assertSame('age_4_6', $a->bandFor(5, null));
        $this->assertSame('age_7_9', $a->bandFor(8, null));
        $this->assertSame('age_10_12', $a->bandFor(11, null));
        $this->assertSame('teen', $a->bandFor(15, null));
        $this->assertSame('moderate', $a->bandFor(35, null));
        $this->assertSame('fit', $a->bandFor(35, 'fit'));
        $this->assertSame('beginner', $a->bandFor(70, null));
        $this->assertSame('limited', $a->bandFor(null, 'limited'));
        // Fitness doesn't override a child's age band.
        $this->assertSame('age_4_6', $a->bandFor(5, 'fit'));
    }

    public function test_easy_trail_is_good_for_a_five_year_old(): void
    {
        $result = HikerAssessor::fromConfig()->assess('age_4_6', $this->trip(1.5, 150, 1.6), $this->profile());
        $this->assertSame(HikerAssessor::GOOD, $result['verdict']);
        $this->assertSame([], $result['issues']);
        $this->assertSame('high', $result['confidence']);
    }

    public function test_one_steep_hill_gives_caveats_and_a_turnaround(): void
    {
        $hill = ['from_mile' => 1.0, 'to_mile' => 1.2, 'length_miles' => 0.2, 'direction' => 'up', 'elevation_change_ft' => 317, 'average_grade_pct' => 30, 'max_grade_pct' => 34];
        $result = HikerAssessor::fromConfig()->assess('age_4_6', $this->trip(2.4, 340, 2.7), $this->profile([$hill], 30, 34, 1.0));

        $this->assertSame(HikerAssessor::CAVEATS, $result['verdict']);
        $this->assertEqualsCanonicalizing(['steepest_tenth_mile', 'steepest_pitch'], array_column($result['issues'], 'check'));
        $this->assertSame(1.0, $result['turnaround']['turn_around_at_mile']);
        $this->assertSame(2.0, $result['turnaround']['round_trip_miles']);
        $this->assertStringContainsString('uphill section at miles 1–1.2', $result['turnaround']['reason']);
    }

    public function test_steep_from_the_start_is_not_recommended(): void
    {
        $hill = ['from_mile' => 0.05, 'to_mile' => 0.6, 'length_miles' => 0.55, 'direction' => 'up', 'elevation_change_ft' => 900, 'average_grade_pct' => 31, 'max_grade_pct' => 40];
        $result = HikerAssessor::fromConfig()->assess('age_4_6', $this->trip(1.2, 900, 2), $this->profile([$hill], 35, 40, 0.1));
        $this->assertSame(HikerAssessor::NO, $result['verdict']);
        $this->assertNull($result['turnaround']);
    }

    public function test_reported_exposure_rules_out_kids_but_not_fit_adults(): void
    {
        $flags = ['exposure' => ['Sheer drop-offs on both sides of the ridge.']];
        $assessor = HikerAssessor::fromConfig();
        $kid = $assessor->assess('age_7_9', $this->trip(2, 400, 2), $this->profile(), $flags);
        $adult = $assessor->assess('fit', $this->trip(2, 400, 2), $this->profile(), $flags);

        $this->assertSame(HikerAssessor::NO, $kid['verdict']);
        $this->assertSame('exposure', $kid['issues'][0]['check']);
        $this->assertSame(HikerAssessor::GOOD, $adult['verdict']);
    }

    public function test_without_a_track_it_says_what_is_missing(): void
    {
        $result = HikerAssessor::fromConfig()->assess('moderate', $this->trip(4, 600, 2.5), null);
        $this->assertSame(HikerAssessor::GOOD, $result['verdict']);
        $this->assertSame('medium', $result['confidence']);
        $this->assertNotEmpty($result['missing']);
    }

    public function test_too_long_is_a_major_issue(): void
    {
        $result = HikerAssessor::fromConfig()->assess('age_4_6', $this->trip(8, 300, 7), $this->profile());
        $this->assertSame(HikerAssessor::NO, $result['verdict']);
        $this->assertSame('major', collect($result['issues'])->firstWhere('check', 'distance')['severity']);
    }
}
