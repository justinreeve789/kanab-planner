<?php

namespace App\Repositories;

use Illuminate\Database\Connection;

class ItineraryRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return ItineraryRepository::class;
    }

    public function getEventsByBaseActivityData()
    {
        $sql = "SELECT e.*, rd.*
                FROM events e
                LEFT JOIN resource_data rd ON e.event_id = rd.resource_id AND rd.resource_table = 'events'";
        $sql .= " ORDER BY ABS(mood - AVG(mood) OVER(PARTITION BY NULL)) ";
    }
}
