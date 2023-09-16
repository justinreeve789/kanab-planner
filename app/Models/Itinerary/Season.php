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
}
