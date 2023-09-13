<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $hidden = ['raw_data', 'location_id'];

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }

    public static function between($startDateTime, $endDateTime)
    {
        return Event::where([
            'start_date', '>=', date('Y-m-d', strtotime($startDateTime)),
            'start_time', '>=', date('h:i', strtotime($startDateTime)),
            'end_date', '<=', date('Y-m-d', strtotime($endDateTime)),
            'end_time', '<=', date('h:i', strtotime($endDateTime)),
        ])->get();
    }
}
