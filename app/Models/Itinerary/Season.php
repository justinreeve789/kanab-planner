<?php

namespace App\Models\Itinerary;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Season
{
    private $all;
    public $startDate;
    public $endDate;

    public function __construct()
    {
    }

    public function getSeasons(): array
    {
        if (!isset($this->all)) {
            // Define the starting year
            $startYear = date('Y');

            // Initialize an empty array to store the seasons
            $this->all = [];

            // Loop through 10 years
            for ($i = 0; $i < 10; $i++) {
                // Calculate the year for this iteration
                $year = $startYear + $i;

                // Calculate the start and end dates for each season
                $springStart = date('Y-m-d', strtotime("March 20, $year"));
                $springEnd = date('Y-m-d', strtotime("June 20, $year"));

                $summerStart = date('Y-m-d', strtotime("June 21, $year"));
                $summerEnd = date('Y-m-d', strtotime("September 21, $year"));

                $autumnStart = date('Y-m-d', strtotime("September 22, $year"));
                $autumnEnd = date('Y-m-d', strtotime("December 20, $year"));

                $winterStart = date('Y-m-d', strtotime("December 21, $year"));
                $winterEnd = date('Y-m-d', strtotime("March 19, " . ($year + 1)));

                // Store the seasons in an associative array indexed by year
                $this->all[$year] = [
                    'spring' => ['start' => $springStart, 'end' => $springEnd],
                    'summer' => ['start' => $summerStart, 'end' => $summerEnd],
                    'autumn' => ['start' => $autumnStart, 'end' => $autumnEnd],
                    'winter' => ['start' => $winterStart, 'end' => $winterEnd],
                ];
            }
        }
        return $this->all;
    }

    public static function all(): array {
        $seasons = new self();
        return $seasons->getSeasons();
    }

    public static function inDates() {
    }

    public const RANGES = [
        'spring' => ['start' => '03-20', 'end' => '06-20'],
        'summer' => ['start' => '06-21', 'end' => '09-21'],
        'fall' => ['start' => '09-22', 'end' => '12-20'],
        'winter' => ['start' => '12-21', 'end' => '03-19'],
    ];

    /**
     * Which season a date falls in. The boundaries are compared as month-day strings, so the year of the date
     * doesn't matter (previously the boundaries were pinned to the *current* year, so any date in a later year
     * came back as "winter", and Jan 1–Mar 19 of the current year came back as null).
     */
    public static function get(string $date = 'today +2 weeks') {
        $time = strtotime($date);
        if ($time === false) {
            return null;
        }
        $monthDay = date('m-d', $time);

        foreach (self::RANGES as $season => $range) {
            if ($range['start'] <= $range['end']) {
                if ($monthDay >= $range['start'] && $monthDay <= $range['end']) {
                    return $season;
                }
            } elseif ($monthDay >= $range['start'] || $monthDay <= $range['end']) {
                // Winter wraps around the new year.
                return $season;
            }
        }
        return null;
    }

    /**
     * A random date inside the season, at least two weeks inside its boundaries and at least two weeks from today
     * (previously it could return a date from a season that had already passed this year).
     */
    public static function randomDateInSeason(string $season, ?\DateTimeInterface $today = null): string {
        if (!isset(self::RANGES[$season])) {
            throw new \InvalidArgumentException('Unknown season: ' . $season);
        }

        $today = $today ? \DateTimeImmutable::createFromInterface($today) : new \DateTimeImmutable('today');
        $earliest = $today->add(new \DateInterval('P2W'));

        for ($year = (int) $today->format('Y') - 1; $year <= (int) $today->format('Y') + 1; $year++) {
            $start = new \DateTimeImmutable($year . '-' . self::RANGES[$season]['start']);
            $end = new \DateTimeImmutable($year . '-' . self::RANGES[$season]['end']);
            if ($end < $start) {
                $end = $end->add(new \DateInterval('P1Y'));
            }

            $minDate = max($start->add(new \DateInterval('P2W')), $earliest);
            $maxDate = $end->sub(new \DateInterval('P2W'));
            if ($minDate > $maxDate) {
                continue;
            }

            $days = (int) $minDate->diff($maxDate)->days;
            return $minDate->add(new \DateInterval('P' . mt_rand(0, $days) . 'D'))->format('Y-m-d');
        }

        throw new \Exception('Invalid date range.');
    }
}
