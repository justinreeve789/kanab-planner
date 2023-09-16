<?php

namespace App\Services;

use App\Models\Itinerary\Season;
use App\Models\Event;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Models\Image;
use App\Repositories\ItineraryRepository;
use Illuminate\Database\Eloquent\Model;

class ItineraryService
{
    private $repository;
    private $seasons;

    public function __construct()
    {
        $this->seasons = Season::all();
    }

    public function getRepository()
    {
        if (!isset($this->repository)) {
            $this->repository = new ItineraryRepository();
        }
        return $this->repository;
    }

    public function getResourcesNearAdventurousness(Model $model, float $adventurousness)
    {
        return $this->getRepository()->getResourcesNearAdventurousness(with(new $model)->getTable(), $adventurousness);
    }

    public function generateRandomDates($durationDays = 5, $season = null)
    {
        if ($season === null) {}
    }
}