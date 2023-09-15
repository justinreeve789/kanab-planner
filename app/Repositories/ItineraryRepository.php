<?php

namespace App\Repositories;

use App\Models\Event;
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

    public function getEventsNearAdventurousness($class, float $adventurousness, int $limit = 20)
    {
//        $events = Event::with(['data' => function($query) use ($adventurousness) {
//            $query->orderByRaw('ABS(resource_data.adventurousness - ' . $adventurousness . ')');
//        }])->get();
//        $events->sortBy(function ($event) use ($adventurousness) {
//            if (isset($event->data[0])) {
//                $resData = $event->data[0]->toArray();
//                return abs($resData['adventurousness'] - $adventurousness);
//            }
//        });
        $table = 'events';
        $q = \DB::table($table)
            ->select([$table . '.*', 'resource_data.*'])
            ->join('resource_data', function($join) use ($table) {
                $join->on($table . '.id', '=', 'resource_data.resource_id');
                $join->on('resource_data.resource_table', '=', \DB::raw("'" . $table . "'"));
            })->orderByRaw('ABS(resource_data.adventurousness - ?)')
            ->limit($limit)
            ->setBindings([$adventurousness]);
//        $sql = "SELECT e.*, rd.*
//                FROM :resourceTable
//                JOIN resource_data rd ON e.id = rd.resource_id AND rd.resource_table = 'events'
//                ORDER BY ABS(rd.adventurousness - :adventurousness)
//                LIMIT 0," . $limit;
//        $events = \DB::select($sql, ['adventurousness' => $adventurousness]);
        $events = $q->get();
        $events = Event::hydrate($events->toArray());
        return $events;
    }
}
