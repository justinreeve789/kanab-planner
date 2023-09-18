<?php

namespace App\Models\Itinerary;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Take into account that days are longer in summer and shorter in winter.
 */
class Day extends Model
{
    use HasFactory;

    const DEFAULT_ARRIVAL_TIME = '16:00:00';
    const DEFAULT_DEPARTURE_TIME = '10:00:00';

    //    public $adventurousness = self::DEFAULT_ADVENTUROUSNESS;
    public $activities;
    public $areaId;
    public $date;
    public $dayNum;
//    public $solitariness = self::DEFAULT_SOLITARINESS;
//    public $strenuousness = self::DEFAULT_STRENUOUSNESS;
    public $endTime = '22:00';
    public $periods;
    public $startTime = '07:00';

    public function __construct($date, ?int $areaId, ?int $dayNum)
    {
        $this->date = $date;

        if ($areaId !== null) {
            $this->areaId = $areaId;
        }

        if ($dayNum !== null) {
            $this->dayNum = $dayNum;
        }

        $this->activities = [];
        $this->periods = [
            Period::MORNING => new Period(Period::MORNING, $this->areaId),
            Period::AFTERNOON => new Period(Period::AFTERNOON, $this->areaId),
            Period::EVENING => new Period(Period::EVENING, $this->areaId),
            Period::NIGHT => new Period(Period::NIGHT, $this->areaId),
        ];
    }

    public function getAreaId(): int
    {
        return $this->areaId;
    }

    public function setAreaId(int $areaId)
    {
        $this->areaId = $areaId;
    }
}
