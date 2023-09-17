<?php

namespace App\Models\Itinerary;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    use HasFactory;

    public const MORNING = 'morning';
    public const AFTERNOON = 'afternoon';
    public const EVENING = 'evening';
    public const NIGHT = 'night';

    private $activities = [];
    private $areaId;
    private $type;

    public function __construct(?string $type, ?int $areaId) {
        if ($type !== null) {
            $this->setType($type);
        }
        if ($areaId !== null) {
            $this->setAreaId($areaId);
        }
    }

    public function addActivity($activity)
    {
        $this->activities[] = $activity;
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
