<?php

namespace App\Console\Commands;

use App\Models\ActivityType;
use App\Models\Event;
use App\Models\ResourceData;
use App\Models\Restaurant;
use App\Models\Trail;
use App\Services\DataService;
use App\Services\OpenAIService;
use Illuminate\Console\Command;

/**
 * Recompute the adventurousness / solitariness / strenuousness rows in resource_data.
 *
 *   php artisan update:data --trails --restaurants   # deterministic, from DataService
 *   php artisan update:data --events                 # classifies untyped events with OpenAI first
 */
class UpdateActivityData extends Command
{
    protected $signature = 'update:data {--events} {--restaurants} {--trails}';

    protected $description = 'Recompute resource_data scores for trails, restaurants and/or events';

    private DataService $dataService;

    public function handle()
    {
        $this->dataService = new DataService();

        if ($this->option('trails')) {
            $this->processResources('trails', Trail::query(), ActivityType::HIKING);
        }
        if ($this->option('restaurants')) {
            $this->processResources('restaurants', Restaurant::query(), ActivityType::DINING);
        }
        if ($this->option('events')) {
            $this->processEvents();
        }
        if (!$this->option('trails') && !$this->option('restaurants') && !$this->option('events')) {
            $this->warn('Nothing to do: pass --trails, --restaurants and/or --events.');
        }
    }

    private function processResources(string $table, $query, string $activityTypeName): void
    {
        $activityTypeId = ActivityType::idFor($activityTypeName);
        $count = 0;
        $query->chunkById(200, function($resources) use ($table, $activityTypeId, &$count) {
            foreach ($resources as $resource) {
                $this->store($table, $resource, $activityTypeId);
                $count++;
            }
        });
        $this->info("Updated {$count} {$table}.");
    }

    /**
     * Classify events that have no activity type with OpenAI, then score every typed event from its type.
     *
     * TODO: An event could be renamed from the default "event" type to something more specific later; for now the
     * first resource_data row's type is treated as the event's only type.
     */
    private function processEvents(): void
    {
        $openAIService = new OpenAIService();
        $untyped = Event::whereDoesntHave('data', fn($q) => $q->whereNotNull('activity_type_id'))->get();
        foreach ($untyped as $event) {
            $guess = $openAIService->identifyActivityTypeWithOpenAI($event->name . ' ' . $event->description);
            $activityTypeId = ActivityType::idFor($guess);
            if ($activityTypeId) {
                $this->store('events', $event, $activityTypeId);
                $this->info("Assigned {$guess} to {$event->name}");
            } else {
                $this->warn("Cannot assign {$guess} to {$event->name}");
            }
        }

        ResourceData::with('activity_type')->where('resource_table', 'events')->whereNotNull('activity_type_id')
            ->whereNull('strenuousness')->each(function(ResourceData $row) {
                $row->update([
                    'adventurousness' => $row->activity_type->adventurousness,
                    'solitariness' => $row->activity_type->solitariness,
                    'strenuousness' => $row->activity_type->strenuousness,
                ]);
            });
    }

    private function store(string $table, $resource, int $activityTypeId): void
    {
        ResourceData::updateOrCreate(
            ['resource_table' => $table, 'resource_id' => $resource->id, 'activity_type_id' => $activityTypeId],
            [
                'adventurousness' => $this->dataService->calculateAdventurousness($resource, $activityTypeId),
                'solitariness' => $this->dataService->calculateSolitariness($resource, $activityTypeId),
                'strenuousness' => round($this->dataService->calculateStrenuousness($resource, $activityTypeId), 2),
            ]
        );
    }
}
