<?php

namespace App\Models\Itinerary;

/**
 * The booked time ranges of one day, so activities can be placed without overlapping.
 *
 * Times are "H:i" / "H:i:s" strings in and out; internally minutes since midnight. A zero-length activity (a note
 * such as "pack a lunch") never occupies the timeline.
 */
class Timeline
{
    /** @var array<int, array{0: int, 1: int}> sorted [start, end) ranges */
    private array $busy = [];

    public function __construct(private string $dayStart = '06:00', private string $dayEnd = '22:00')
    {
    }

    public static function fromDay(Day $day, string $dayStart = '06:00', string $dayEnd = '22:00'): self
    {
        $timeline = new self($dayStart, $dayEnd);
        foreach ($day->getActivities() as $activity) {
            if ($activity->start_time && $activity->end_time) {
                $timeline->reserve($activity->start_time, $activity->end_time);
            }
        }
        return $timeline;
    }

    public static function toMinutes(string $time): int
    {
        [$h, $m] = array_map('intval', array_pad(explode(':', $time), 2, 0));
        return $h * 60 + $m;
    }

    public static function toTime(int $minutes): string
    {
        $minutes = max(0, min(24 * 60 - 1, $minutes));
        return sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);
    }

    public static function addMinutes(string $time, int $minutes): string
    {
        return self::toTime(self::toMinutes($time) + $minutes);
    }

    public static function floorToQuarter(string $time): string
    {
        return self::toTime(intdiv(self::toMinutes($time), 15) * 15);
    }

    public function isFree(string $start, string $end): bool
    {
        $s = self::toMinutes($start);
        $e = self::toMinutes($end);
        if ($s < self::toMinutes($this->dayStart) || $e > self::toMinutes($this->dayEnd)) {
            return false;
        }
        foreach ($this->busy as [$bs, $be]) {
            if ($s < $be && $e > $bs) {
                return false;
            }
        }
        return true;
    }

    public function reserve(string $start, string $end): void
    {
        $s = self::toMinutes($start);
        $e = self::toMinutes($end);
        if ($e <= $s) {
            return;
        }
        $this->busy[] = [$s, $e];
        usort($this->busy, fn($a, $b) => $a[0] <=> $b[0]);
    }

    /**
     * The earliest start in [$earliest, $latestStart] where $minutes fit, stepping in $step-minute increments and
     * jumping past busy ranges. Null when nothing fits.
     */
    public function findSlot(int $minutes, string $earliest, string $latestStart, int $step = 15): ?string
    {
        $start = self::toMinutes($earliest);
        $latest = self::toMinutes($latestStart);
        // Round up to the next step so times look like 08:15, not 08:07.
        $start = (int) (ceil($start / $step) * $step);

        while ($start <= $latest) {
            $end = $start + $minutes;
            $conflict = null;
            foreach ($this->busy as [$bs, $be]) {
                if ($start < $be && $end > $bs) {
                    $conflict = $be;
                    break;
                }
            }
            if ($conflict === null) {
                $startTime = self::toTime($start);
                return $this->isFree($startTime, self::toTime($end)) ? $startTime : null;
            }
            $start = (int) (ceil($conflict / $step) * $step);
        }
        return null;
    }

    /** End of the last booked range, or null for an empty day. */
    public function lastEnd(): ?string
    {
        if (empty($this->busy)) {
            return null;
        }
        return self::toTime(max(array_column($this->busy, 1)));
    }

    /** Whether anything is booked across $time (e.g. a hike running through lunch). */
    public function isBusyAt(string $time): bool
    {
        $t = self::toMinutes($time);
        foreach ($this->busy as [$bs, $be]) {
            if ($t >= $bs && $t < $be) {
                return true;
            }
        }
        return false;
    }
}
