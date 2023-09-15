<?php

namespace App\Repositories;

use App\Models\Event;
use Illuminate\Database\Connection;

class ResourceRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return ResourceRepository::class;
    }
}
