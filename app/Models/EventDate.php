<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One occurrence of an event.
 *
 * Some events have no end. Those are assumed to end at 22:00 or four hours after they start, whichever is earlier,
 * unless they start at 22:00 or later, in which case they end at midnight.
 */
class EventDate extends Model
{
    use HasFactory;

    public const EFFECTIVE_END_SQL = "COALESCE(event_dates.end_date, CASE
        WHEN TIME(event_dates.start_date) >= '22:00:00' THEN DATE_FORMAT(event_dates.start_date, '%Y-%m-%d 23:59:59')
        ELSE LEAST(DATE_FORMAT(event_dates.start_date, '%Y-%m-%d 22:00:00'), ADDTIME(event_dates.start_date, '04:00:00'))
    END)";

    protected $guarded = ['id'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function scopeOverlapping(Builder $query, string $startDateTime, string $endDateTime): Builder
    {
        return $query
            ->whereRaw('event_dates.start_date < CAST(? AS DATETIME)', [$endDateTime])
            ->whereRaw(self::EFFECTIVE_END_SQL . ' > CAST(? AS DATETIME)', [$startDateTime]);
    }

    public function getEffectiveEndDateAttribute(): string
    {
        if ($this->end_date) {
            return (string) $this->end_date;
        }
        $start = strtotime($this->start_date);
        if (date('H:i:s', $start) >= '22:00:00') {
            return date('Y-m-d 23:59:59', $start);
        }
        return date('Y-m-d H:i:s', min($start + 4 * 3600, strtotime(date('Y-m-d 22:00:00', $start))));
    }
}
