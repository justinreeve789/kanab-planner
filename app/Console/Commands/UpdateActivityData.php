<?php

namespace App\Console\Commands;

use App\Models\ActivityType;
use App\Models\Event;
use App\Models\Location;
use App\Models\ResourceData;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Services\EventService;
use App\Services\OpenAIService;
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

    private $openAIService;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->openAIService = new OpenAIService();

        $doEvents = $this->option('events');
        $doRestaurants = $this->option('restaurants');
        $doTrails = $this->options('trails');

        if ($doEvents) {
            $this->eventService = new EventService();
            $this->processEvents();
        }

        if ($doRestaurants) {
//            $this->processRestaurants();
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
        if (1 === 1) {
            $events = Event::whereHas('data', function ($query) {
                $query->whereNull('activity_id');
            })->orWhereDoesntHave('data')->with('data')->get();
            foreach ($events as $item) {
                // Use AI to determine the type of event.
                $guessType =
                    $this->openAIService->identifyActivityTypeWithOpenAI($item->name . ' ' . $item->description);
                $activityType = ActivityType::where('name', $guessType)->first();
                if ($activityType) {
                    $resourceData = ResourceData::updateOrCreate(
                        ['resource_table' => 'events', 'resource_id' => $item->id],
                        ['activity_id' => $activityType->id]
                    );
                    $this->info('Assigned ' . $guessType . ' to ' . $item->name);
                } else {
                    $this->info('Cannot assign ' . $guessType . ' to ' . $item->name);
                }
            }
        }

        $resourceData = ResourceData::with('activity_type')
            ->where('resource_table', 'events')
            ->whereNotNull('activity_id')
            ->whereNull(['adventurousness', 'solitariness', 'strenuousness'])
            ->get();
        foreach ($resourceData as $item) {
            $item->adventurousness = $item->activity_type->adventurousness;
            $item->solitariness = $item->activity_type->solitariness;
            $item->strenuousness = $item->activity_type->strenuousness;
            $item->save();
        }
    }

    protected function processRestaurants()
    {
        $restaurants = Restaurant::with('data')->get();
        foreach ($restaurants as $item) {
            $item->activity_type = 'dining';
            $this->update('restaurants', $item);
        }
    }

    protected function processTrails()
    {
        $trails = Trail::with('data')->get();
        foreach ($trails as $item) {
            foreach (['hiking'] as $activityType) {
                $item->activity_type = $activityType;
                $this->update('trails', $item);
            }
        }
    }

    protected function update($resourceTable, $item)
    {
        ResourceData::updateOrCreate(['resource_table' => $resourceTable, 'resource_id' => $item->id, 'activity_type_id' => $item->activity_type_id], [
            'adventurousness' => $item->adventurousness($item->activity_type),
            'solitariness' => $item->solitariness($item->activity_type),
            'strenuousness' => $item->strenuousness($item->activity_type),
        ]);
    }
}
