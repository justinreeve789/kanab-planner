<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Models\Image;
use App\Repositories\ResourceRepository;
use Illuminate\Database\Eloquent\Model;

class ResourceService
{
    private $repository;

    public function getRepository()
    {
        if (!isset($this->repository)) {
            $this->repository = new ResourceRepository();
        }
        return $this->repository;
    }
}