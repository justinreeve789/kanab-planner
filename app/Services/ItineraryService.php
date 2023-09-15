<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Models\Image;
use App\Repositories\ItineraryRepository;

class ItineraryService
{
    private $repository;

    public function getRepository()
    {
        if (!isset($this->repository)) {
            $this->repository = new ItineraryRepository();
        }
        return $this->repository;
    }

    public function getEventsNearAdventurousness(float $adventurousness)
    {
        return $this->getRepository()->getEventsNearAdventurousness(Event::class, $adventurousness);
    }
}