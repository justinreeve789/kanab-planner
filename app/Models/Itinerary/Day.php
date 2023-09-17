<?php

namespace App\Models\Itinerary;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Day extends Model
{
    use HasFactory;

    public $adventurousness = self::DEFAULT_ADVENTUROUSNESS;
    public $date;
    public $solitariness = self::DEFAULT_SOLITARINESS;
    public $strenuousness = self::DEFAULT_STRENUOUSNESS;
    public $periods;

    public function __construct()
    {
        $this->periods = [];
    }
}
