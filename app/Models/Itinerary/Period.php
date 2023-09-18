<?php

namespace App\Models\Itinerary;

use App\Traits\ReturnAllProperties;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    use HasFactory;
    use ReturnAllProperties;

    public const MORNING = 'morning';
    public const AFTERNOON = 'afternoon';
    public const EVENING = 'evening';
    public const NIGHT = 'night';

    protected $guarded = [];

    public $activities = [];
    public $areaId;
    public $type;

    public function __construct(?string $type, ?int $areaId) {
        if ($type !== null) {
            $this->setType($type);
        }
        if ($areaId !== null) {
            $this->setAreaId($areaId);
        }
    }

    public function addActivity($resource, $activityType = null, $startTime = null, $endTime = null)
    {
        echo '<pre>'; print_r($resource); echo '</pre>';
        $this->activities[] = new Activity($resource, $activityType, $startTime, $endTime);
    }

    public function getActivities(): array
    {
        return $this->activities;
    }

    public function getAreaId(): int
    {
        return $this->areaId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setAreaId(int $areaId) {
        $this->areaId = $areaId;
    }

    public function setType(string $type) {
        $this->type = $type;
    }
}
