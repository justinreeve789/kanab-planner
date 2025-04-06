<?php

namespace App\Traits;

use App\Models\Location;
use Laravel\Scout\Searchable;

trait SearchableResource
{
    use Searchable;

    public function searchableAs(): string
    {
        return $this->getTable();
    }
}
