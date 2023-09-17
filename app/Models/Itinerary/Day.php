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

//    public $adventurousness = self::DEFAULT_ADVENTUROUSNESS;
    public $areaId;
    public $date;
//    public $solitariness = self::DEFAULT_SOLITARINESS;
//    public $strenuousness = self::DEFAULT_STRENUOUSNESS;
    public $periods;

    public function __construct($date, ?int $areaId)
    {
        $this->date = $date;

        if ($areaId !== null) {
            $this->areaId = $areaId;
        }

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
