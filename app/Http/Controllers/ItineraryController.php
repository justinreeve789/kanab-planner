<?php

namespace App\Http\Controllers;

use App\Models\ActivityType;
use App\Models\Itinerary\Activity;
use App\Models\Itinerary\Day;
use App\Models\Itinerary\Itinerary;
use App\Models\Itinerary\Period;
use App\Policies\ItineraryPolicy;
use App\ResourceEnum;
use App\Services\ItineraryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Itineraries, their days and activities.
 *
 * Reading is open to anyone with the id. Every change is authorized by ItineraryPolicy: the X-Itinerary-Token header
 * returned when the itinerary was created, or a signed-in owner.
 */
class ItineraryController extends Controller
{
    /** Activity type for a resource added by hand. */
    public const ACTIVITY_TYPE_FOR_TABLE = [
        ResourceEnum::TRAILS_TABLE => ActivityType::HIKING,
        ResourceEnum::RESTAURANTS_TABLE => ActivityType::DINING,
        ResourceEnum::EVENTS_TABLE => ActivityType::EVENT,
        ResourceEnum::LOCATIONS_TABLE => ActivityType::SIGHTSEEING,
        ResourceEnum::ACCOMMODATIONS_TABLE => ActivityType::CHECKING_IN,
    ];

    /**
     * The signed-in user's saved itineraries, newest first.
     */
    public function index(Request $request)
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['errors' => 'Sign in to see your saved itineraries.'], 401);
        }
        return $user->itineraries()
            ->select('itineraries.id', 'itineraries.name', 'start_date', 'end_date', 'duration_days', 'base_area_id', 'itineraries.created_at')
            ->orderByDesc('user_itineraries.created_at')
            ->get();
    }

    /**
     * Generate a new itinerary from the request's preferences. The response carries a one-time edit_token; keep it
     * to make changes later. A signed-in user also becomes the owner.
     */
    public function createItinerary(Request $request)
    {
        $request->validate([
            'adventurousness' => 'nullable|numeric|between:0,1',
            'solitariness' => 'nullable|numeric|between:0,1',
            'strenuousness' => 'nullable|numeric|between:0,1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'duration_days' => 'nullable|integer|between:1,30',
            'base_area_id' => 'nullable|integer|exists:areas,id',
            'average_hiking_speed' => 'nullable|numeric|between:0.3,6',
            'name' => 'nullable|string|max:255',
        ]);

        $service = new ItineraryService();
        $token = $service->getItinerary()->issueEditToken();
        $itinerary = $service->generate($request);

        if ($user = $request->user('sanctum')) {
            $itinerary->users()->syncWithoutDetaching([$user->id]);
        }

        return response()->json(['can_edit' => true, 'edit_token' => $token] + $this->present($request, $itinerary->id), 201);
    }

    public function loadItinerary(Request $request, $id)
    {
        if (!Itinerary::whereKey($id)->exists()) {
            return response()->json(['errors' => 'Itinerary not available'], 422);
        }
        return $this->present($request, $id);
    }

    /**
     * Rename the trip.
     */
    public function updateItinerary(Request $request, $id)
    {
        $itinerary = Itinerary::findOrFail($id);
        $this->authorizeEdit($request, $itinerary);
        $data = $request->validate(['name' => 'nullable|string|max:255']);
        $itinerary->name = $data['name'] ?? null;
        $itinerary->save();
        return $this->present($request, $itinerary->id);
    }

    public function deleteItinerary(Request $request, string $id)
    {
        $itinerary = Itinerary::findOrFail($id);
        $this->authorizeEdit($request, $itinerary, 'delete');
        $itinerary->delete();
        return response()->noContent();
    }

    /**
     * Save an itinerary you can edit (you hold its token) to your account, so it follows you across devices.
     */
    public function claimItinerary(Request $request, string $id)
    {
        $itinerary = Itinerary::findOrFail($id);
        $user = $request->user('sanctum');
        Gate::forUser($user)->authorize('claim', [$itinerary, $request->header(ItineraryPolicy::TOKEN_HEADER)]);
        $itinerary->users()->syncWithoutDetaching([$user->id]);
        return $this->present($request, $itinerary->id);
    }

    public function getDay(Request $request, string $dayId)
    {
        return Day::with(['periods' => fn($q) => $q->orderBy('type'), 'periods.activities' => fn($q) => $q->orderBy('start_time')])
            ->findOrFail($dayId);
    }

    /**
     * Edit the day's note (shown above its schedule).
     */
    public function updateDay(Request $request, string $dayId)
    {
        $day = Day::findOrFail($dayId);
        $this->authorizeEdit($request, $day->itinerary);
        $data = $request->validate(['preamble' => 'nullable|string|max:2000']);
        $day->setPreamble($data['preamble'] ?? null);
        $day->save();
        return $this->getDay($request, $day->id);
    }

    /**
     * Clear everything off the day, leaving a free day.
     */
    public function deleteDay(Request $request, string $dayId)
    {
        $day = Day::with('periods.activities')->findOrFail($dayId);
        $this->authorizeEdit($request, $day->itinerary);
        foreach ($day->periods as $period) {
            $period->activities()->delete();
        }
        $day->stats = null;
        $day->save();
        return $this->getDay($request, $day->id);
    }

    /**
     * Throw away the day's plan and build a new one in the same area.
     */
    public function regenerateDay(Request $request, string $dayId)
    {
        $day = Day::findOrFail($dayId);
        $itinerary = $day->itinerary;
        $this->authorizeEdit($request, $itinerary);
        (new ItineraryService())->regenerateDay($itinerary, $day);
        return $this->getDay($request, $day->id);
    }

    public function getActivity(Request $request, string $activityId)
    {
        return Activity::findOrFail($activityId);
    }

    /**
     * Add to a day (POST /day/{id}/activity) or a specific period (POST /period/{id}/activity): either a resource
     * (resource_table + resource_id) or a custom item (name, description). The period is chosen from start_time.
     */
    public function createActivity(Request $request, ?string $dayId = null)
    {
        if ($request->route('periodId')) {
            $period = Period::findOrFail($request->route('periodId'));
            $day = Day::findOrFail($period->day_id);
        } else {
            $day = Day::findOrFail($dayId);
            $period = null;
        }
        $this->authorizeEdit($request, $day->itinerary);
        $data = $this->validateActivity($request, true);

        $start = Itinerary::normalizeTime($data['start_time'] ?? null)
            ?? Period::getStartTimeForActivities($period?->type ?? Period::MORNING);
        $period = $period ?? $day->periods()->where('type', (string) Period::forTime($start))->firstOrFail();

        $activity = new Activity(['period_id' => $period->id, 'start_time' => $start]);
        $this->fillActivity($activity, $data, $day);
        $activity->save();
        $this->recalculateDay($day);

        return response()->json($activity->fresh(), 201);
    }

    /**
     * Change an activity's times or text, or swap it for another resource.
     */
    public function updateActivity(Request $request, string $activityId)
    {
        $activity = Activity::findOrFail($activityId);
        $day = Day::findOrFail($activity->period->day_id);
        $this->authorizeEdit($request, $day->itinerary);
        $data = $this->validateActivity($request, false);

        if (array_key_exists('start_time', $data)) {
            $activity->start_time = Itinerary::normalizeTime($data['start_time']);
            $newPeriod = $day->periods()->where('type', (string) Period::forTime($activity->start_time))->first();
            $activity->period_id = $newPeriod?->id ?? $activity->period_id;
        }
        $this->fillActivity($activity, $data, $day);
        $activity->save();
        $this->recalculateDay($day);

        return $activity->fresh();
    }

    public function deleteActivity(Request $request, string $activityId)
    {
        $activity = Activity::findOrFail($activityId);
        $day = Day::findOrFail($activity->period->day_id);
        $this->authorizeEdit($request, $day->itinerary);
        $activity->delete();
        $this->recalculateDay($day);
        return response()->noContent();
    }

    private function validateActivity(Request $request, bool $creating): array
    {
        return $request->validate([
            'resource_table' => ['nullable', Rule::in(array_keys(self::ACTIVITY_TYPE_FOR_TABLE)), 'required_with:resource_id'],
            'resource_id' => 'nullable|integer|required_with:resource_table',
            'name' => ($creating ? 'required_without:resource_table|' : '') . 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'start_time' => 'nullable|date_format:H:i,H:i:s',
            'end_time' => 'nullable|date_format:H:i,H:i:s',
        ]);
    }

    private function fillActivity(Activity $activity, array $data, Day $day): void
    {
        if (!empty($data['resource_table'])) {
            $model = Activity::RESOURCE_CLASSES[$data['resource_table']];
            $resource = $model::findOrFail($data['resource_id']);
            $activity->resource = $resource;
            $activity->activity_type_id = ActivityType::idFor(self::ACTIVITY_TYPE_FOR_TABLE[$data['resource_table']]);
            $activity->name = null;
            if (!array_key_exists('end_time', $data)) {
                $activity->end_time = $resource->calculateEndTime($day->date, $activity->start_time);
            }
        } elseif (!$activity->exists) {
            $activity->activity_type_id = ActivityType::idFor('unknown');
        }
        foreach (['name', 'description'] as $field) {
            if (array_key_exists($field, $data) && $activity->isTask()) {
                $activity->{$field} = $data[$field];
            }
        }
        if (array_key_exists('end_time', $data)) {
            $activity->end_time = Itinerary::normalizeTime($data['end_time']);
        }
        $activity->end_time = $activity->end_time ?? $activity->start_time;
        if ($activity->end_time < $activity->start_time) {
            abort(response()->json(['errors' => ['end_time' => ['The end time must be after the start time.']]], 422));
        }
    }

    private function recalculateDay(Day $day): void
    {
        $day->load('periods.activities');
        $day->calculateASS(true);
        $day->save();
    }

    private function authorizeEdit(Request $request, Itinerary $itinerary, string $ability = 'update'): void
    {
        Gate::forUser($request->user('sanctum'))
            ->authorize($ability, [$itinerary, $request->header(ItineraryPolicy::TOKEN_HEADER)]);
    }

    /**
     * The itinerary as the app renders it, plus whether the caller may edit it and whether they own it.
     */
    private function present(Request $request, string $id): array
    {
        $itinerary = Itinerary::withSchedule()->findOrFail($id);
        $user = $request->user('sanctum');
        $canEdit = Gate::forUser($user)->allows('update', [$itinerary, $request->header(ItineraryPolicy::TOKEN_HEADER)]);
        return $itinerary->toArray() + ['can_edit' => $canEdit, 'owned' => $itinerary->isOwnedBy($user)];
    }
}
