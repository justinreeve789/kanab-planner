<?php

namespace App\Chat\Tools;

use App\Chat\ChatContext;
use App\Http\Controllers\ItineraryController;
use App\Models\Itinerary\Activity;
use App\Models\Itinerary\Day;
use App\Policies\ItineraryPolicy;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Changes the traveler's itinerary through the same controller actions the app's editor uses, so authorization,
 * validation, drive re-timing and gap flags all behave the same.
 */
class EditItinerary extends BaseTool
{
    public function name(): string
    {
        return 'edit_itinerary';
    }

    public function description(): string
    {
        return 'Change the traveler\'s itinerary, only when they asked for that change: add a trail, restaurant, '
            . 'place, event or lodging record (or a custom item) to a day at a time; move an activity to another time '
            . 'or day; remove an activity; rebuild a whole day; or set a day\'s note. Get ids from get_itinerary first. '
            . 'Reports back drive-time conflicts the change created.';
    }

    public function parameters(): array
    {
        return self::object([
            'action' => ['type' => 'string', 'enum' => ['add', 'move', 'remove', 'rebuild_day', 'set_note']],
            'day_id' => ['type' => 'string', 'description' => 'Target day (add, move to another day, rebuild_day, set_note).'],
            'activity_id' => ['type' => 'string', 'description' => 'For move and remove.'],
            'resource_table' => ['type' => 'string', 'enum' => ['trails', 'restaurants', 'locations', 'events', 'accommodations']],
            'resource_id' => ['type' => 'integer'],
            'name' => ['type' => 'string', 'description' => 'Custom item (add without a record).'],
            'start_time' => ['type' => 'string', 'description' => 'HH:MM, 24-hour.'],
            'end_time' => ['type' => 'string', 'description' => 'HH:MM, 24-hour.'],
            'note' => ['type' => 'string'],
        ], ['action']);
    }

    public function handle(array $args, ChatContext $context): ToolResult
    {
        $args = $this->validate($args, [
            'action' => 'required|in:add,move,remove,rebuild_day,set_note',
            'day_id' => 'nullable|string|max:40',
            'activity_id' => 'nullable|string|max:40',
            'resource_table' => 'nullable|in:trails,restaurants,locations,events,accommodations',
            'resource_id' => 'nullable|integer',
            'name' => 'nullable|string|max:255',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'note' => 'nullable|string|max:2000',
        ]);
        if (!$context->itinerary) {
            return ToolResult::unavailable('No itinerary is open in this chat.');
        }
        if (!$context->canEditItinerary) {
            return ToolResult::unavailable('The traveler can view this itinerary but not edit it from here (they need to own it or have its edit link). Describe the change for them to make instead.');
        }

        $controller = app(ItineraryController::class);
        try {
            $result = match ($args['action']) {
                'add' => $this->add($controller, $args, $context),
                'move' => $this->move($controller, $args, $context),
                'remove' => $this->remove($controller, $args, $context),
                'rebuild_day' => $controller->regenerateDay($this->request($context), $this->day($args, $context)->id),
                'set_note' => $controller->updateDay($this->request($context, ['preamble' => $args['note'] ?? null]), $this->day($args, $context)->id),
            };
        } catch (ValidationException $e) {
            return new ToolResult(['error' => 'The itinerary rejected that change.', 'details' => $e->errors()]);
        } catch (HttpException $e) {
            return new ToolResult(['error' => 'The itinerary rejected that change: ' . ($e->getMessage() ?: $e->getStatusCode())]);
        }

        $dayId = $args['day_id'] ?? (isset($args['activity_id']) ? optional(Activity::find($args['activity_id'])?->period)->day_id : null);
        $day = $dayId ? Day::with('periods.activities')->find($dayId) : null;
        $flags = [];
        foreach ($day?->periods ?? [] as $period) {
            foreach ($period->activities as $activity) {
                foreach ((array) $activity->travel_notes as $note) {
                    $flags[] = is_array($note) ? ($note['text'] ?? json_encode($note)) : (string) $note;
                }
            }
        }

        return ToolResult::ok([
            'done' => $args['action'],
            'result' => is_object($result) && method_exists($result, 'toArray') ? array_intersect_key($result->toArray(), array_flip(['id', 'name', 'start_time', 'end_time', 'date'])) : null,
            'drive_and_timing_notes' => array_values(array_unique($flags)) ?: null,
            'next' => 'Tell the traveler what changed. Call get_itinerary if you need the updated day.',
        ], [ToolResult::source('Your itinerary', rtrim((string) config('app.url'), '/') . '/' . $context->itinerary->id, ToolResult::OUR_DATA)]);
    }

    private function add(ItineraryController $controller, array $args, ChatContext $context)
    {
        $day = $this->day($args, $context);
        $response = $controller->createActivity($this->request($context, array_filter([
            'resource_table' => $args['resource_table'] ?? null,
            'resource_id' => $args['resource_id'] ?? null,
            'name' => $args['name'] ?? null,
            'start_time' => $args['start_time'] ?? null,
            'end_time' => $args['end_time'] ?? null,
        ], fn($v) => $v !== null)), $day->id);
        return $response->getData();
    }

    private function move(ItineraryController $controller, array $args, ChatContext $context)
    {
        $activity = $this->activity($args, $context);
        if (!empty($args['day_id']) && $args['day_id'] !== (string) $activity->period->day_id) {
            // Another day: re-create it there, then remove the original.
            $target = $this->day($args, $context);
            $created = $controller->createActivity($this->request($context, array_filter([
                'resource_table' => $activity->resource_table,
                'resource_id' => $activity->resource_id,
                'name' => $activity->resource_table ? null : $activity->name,
                'start_time' => $args['start_time'] ?? substr((string) $activity->start_time, 0, 5),
            ], fn($v) => $v !== null)), $target->id);
            $controller->deleteActivity($this->request($context), $activity->id);
            return $created->getData();
        }
        return $controller->updateActivity($this->request($context, array_filter([
            'start_time' => $args['start_time'] ?? null,
            'end_time' => $args['end_time'] ?? null,
        ], fn($v) => $v !== null)), $activity->id);
    }

    private function remove(ItineraryController $controller, array $args, ChatContext $context)
    {
        $activity = $this->activity($args, $context);
        $controller->deleteActivity($this->request($context), $activity->id);
        return null;
    }

    private function day(array $args, ChatContext $context): Day
    {
        $day = !empty($args['day_id']) ? Day::find($args['day_id']) : null;
        if (!$day || (string) $day->itinerary_id !== (string) $context->itinerary->id) {
            throw ValidationException::withMessages(['day_id' => 'That day is not in this itinerary.']);
        }
        return $day;
    }

    private function activity(array $args, ChatContext $context): Activity
    {
        $activity = !empty($args['activity_id']) ? Activity::with('period')->find($args['activity_id']) : null;
        $day = $activity ? Day::find($activity->period->day_id) : null;
        if (!$activity || !$day || (string) $day->itinerary_id !== (string) $context->itinerary->id) {
            throw ValidationException::withMessages(['activity_id' => 'That activity is not in this itinerary.']);
        }
        return $activity;
    }

    /** A request carrying the traveler's identity and edit token, for the controller's own authorization. */
    private function request(ChatContext $context, array $data = []): Request
    {
        $request = Request::create('/', 'POST', $data);
        if ($context->itineraryToken) {
            $request->headers->set(ItineraryPolicy::TOKEN_HEADER, $context->itineraryToken);
        }
        $user = $context->user;
        $request->setUserResolver(fn() => $user);
        return $request;
    }
}
