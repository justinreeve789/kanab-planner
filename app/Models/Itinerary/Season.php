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

    public static function get(string $date = 'today +2 weeks') {
        // Convert the date to a timestamp.
        $time = strtotime($date);

        // Define the start and end dates for each season.
        $seasons = [
            'spring' => ['start' => '03-20', 'end' => '06-20'],
            'summer' => ['start' => '06-21', 'end' => '09-21'],
            'fall' => ['start' => '09-22', 'end' => '12-20'],
            'winter' => ['start' => '12-21', 'end' => '03-19'],
        ];

        // Determine the current season.
        foreach ($seasons as $season => $dates) {
            $startDate = date('Y') . '-' . $dates['start'];
            $endDate = date('Y') . '-' . $dates['end'];

            if ($time >= strtotime($startDate) && $time <= strtotime($endDate)) {
                return $season;
            }

            // Check if it's winter extending into the next year.
            if ($season === 'winter' && $time >= strtotime($startDate)) {
                $nextYearStartDate = (date('Y') + 1) . '-' . $dates['start'];
                if ($time <= strtotime($nextYearStartDate)) {
                    return $season;
                }
            }
        }
        return null;
    }

    public static function randomDateInSeason(string $season): string {
        // Define the start and end dates for each season.
        $seasons = [
            'spring' => ['start' => '03-20', 'end' => '06-20'],
            'summer' => ['start' => '06-21', 'end' => '09-21'],
            'fall' => ['start' => '09-22', 'end' => '12-20'],
            'winter' => ['start' => '12-21', 'end' => '03-19'],
        ];

        // Get the current year.
        $currentYear = date('Y');

        // Get the start and end dates for the specified season.
        $start = new \DateTime($currentYear . '-' . $seasons[$season]['start']);
        $end = new \DateTime($currentYear . '-' . $seasons[$season]['end']);

        if ($end < $start) {
            $end = new \DateTime($currentYear + 1 . '-' . $seasons[$season]['end']);
        }

        // Calculate the minimum date (2 weeks from the start of the season).
        $minDate = clone $start;
        $minDate->add(new \DateInterval('P2W'));

        // Calculate the maximum date (2 weeks before the end of the season).
        $maxDate = clone $end;
        $maxDate->sub(new \DateInterval('P2W'));

        // Check if the minimum date is after the maximum date.
        if ($minDate > $maxDate) {
            throw new \Exception("Invalid date range.");
        }

        // Generate a random timestamp within the season's date range.
        $randomTimestamp = mt_rand($minDate->getTimestamp(), $maxDate->getTimestamp());

        // Create a random DateTime object from the generated timestamp.
        $randomDate = new \DateTime();
        $randomDate->setTimestamp($randomTimestamp);

        return $randomDate->format('Y-m-d');
    }
}
