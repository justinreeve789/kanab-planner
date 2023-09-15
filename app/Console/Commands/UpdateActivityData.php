<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\Event;
use App\Models\Location;
use App\Models\ResourceData;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Services\EventService;
use App\Services\RestaurantService;
use Illuminate\Console\Command;

class UpdateActivityData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:data {--events} {--restaurants} {--trails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates activity data information';

    private $restaurantService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $doEvents = $this->option('events');
        $doRestaurants = $this->option('restaurants');
        $doTrails = $this->options('trails');

        if ($doEvents) {
            $this->eventService = new EventService();
            $this->processEvents();
        }

        if ($doRestaurants) {
            $this->processRestaurants();
        }

        if ($doTrails) {
            $this->processTrails();
        }
    }

    /**
     * TODO: Figure out how we're going to handle it when we rename the activity type of an event. By default, they're
     * just "event" but they could be later updated to "endurance-race" or "festivals-and-celebrations." Is it possible
     * an event could have more than one activity type? Probably not, but for now we'll just solve this problem by
     * assuming the first activity type in an event's data is the only activity type.
     */
    protected function processEvents()
    {
        $defaultActivityId = Activity::where('name', 'event')->pluck('id')->first();
        $events = Event::with('data')->get();
        foreach ($events as $item) {
            if (!isset($item->data[0])) {
                $data = $this->eventService->calculateResourceData($item);
                if ($data) {
                    echo 'Processing ' . $item->id . ' - ' . $item->name . ' - ' . implode(', ', $data) . '
';
                    ResourceData::updateOrCreate(['resource_table' => 'events', 'resource_id' => $item->id, 'activity_id' => $defaultActivityId], [
                        'adventurousness' => $data['adventurousness'],
                        'solitariness' => $data['solitariness'],
                        'strenuousness' => $data['strenuousness'],
                    ]);
                }
            }
        }
    }

    protected function processRestaurants()
    {
        $restaurants = Restaurant::with('data')->get();
        foreach ($restaurants as $item) {
            $item->setActivityType('dining');
            $this->update('restaurants', $item);
        }
    }

    protected function processTrails()
    {
        $trails = Trail::with('data')->get();
        foreach ($trails as $item) {
            foreach (['bicycling', 'hiking', 'offroading'] as $activityType) {
                $item->setActivityType($activityType);
                $this->update('trails', $item);
            }
        }
    }

    protected function update($resourceTable, $item)
    {
        ResourceData::updateOrCreate(['resource_table' => $resourceTable, 'resource_id' => $item->id, 'activity_id' => $item->activity($item->getActivityType())->id], [
            'adventurousness' => $item->adventurousness($item->getActivityType()),
            'solitariness' => $item->solitariness($item->getActivityType()),
            'strenuousness' => $item->strenuousness($item->getActivityType()),
        ]);
    }
}
