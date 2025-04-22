<?php
/**
 * A user may type things like "I want a new itinerary" or "This is too expensive," in which case we can identify and
 * run appropriate functions in ItineraryService to update the itinerary accordingly.
 *
 * A lot of these requests should be included in the training data to indicate what steps the user can take to do it
 * themselves. The assistant can provide basic how-to information in this regard.
 */

namespace App\Services;

use App\Models\Assistant\Message;
use App\Models\Location;
use App\Models\Restaurant;
use App\Models\Trail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Meilisearch\Client;

class AssistantService
{
    const MEILISEARCH_RANKING_THRESHOLD = 0.95;
    const TEXT_ANALYSIS_RANKING_THRESHOLD = 75;
    const RESTAURANTS = 'restaurants';
    const TRAILS = 'trails';

    public $client;
    public $phraseService;
    public $wordsToRemove = [
        'backpacking',
        'backpack',
        'dining',
        'eat',
        'food',
        'hiking',
        'locations',
        'location',
        'restaurants',
        'restaurant',
        'trails',
        'trail',
    ];
    public $stats;

    public function __construct()
    {
        $this->client = new Client(Config::get('scout.meilisearch.host'), Config::get('scout.meilisearch.key'));
        $this->phraseService = new PhraseService();
        $this->stats = [
            self::RESTAURANTS => [],
            self::TRAILS => [],
        ];
    }

    public function getResourceFromTypeAndId(string $type, string $id) {
        if (is_numeric($id)) {
            switch ($type) {
                case 'restaurant':
                    $resource = Restaurant::find($id);
                    break;
                case 'trail':
                    $resource = Trail::find($id);
                    break;
                default:
            }
        }
        return $resource ?? null;
    }

    private function removeWords(string $str): string {
        foreach ($this->wordsToRemove as $word) {
            $str = str_ireplace($word, '', $str);
        }
        return $str;
    }

    private function queryResourceNames(string $query, Collection $resources, bool $exactMatch = true) {
        $query = preg_replace('/\s+/', ' ', $query);
        $query = trim(strtolower($query));
        if ($exactMatch === true) {
            foreach ($resources as $resource) {
                $resourceName = $this->removeWords($resource->name);
                if (str_contains($query, strtolower($resourceName))) {
                    return [
                        'score' => 1,
                        'resource' => $resource,
                    ];
                }
            }
        } else {
            $bestMatch = null;
            $highestPercent = 0;
            foreach ($resources as $resource) {
                $resourceName = $this->removeWords($resource->name);
                if (similar_text($query, strtolower($resourceName), $percent)) {
                    if ($percent > $highestPercent) {
                        $highestPercent = $percent;
                        $bestMatch = $resource;
                    }
                }
            }
            if ($highestPercent > self::TEXT_ANALYSIS_RANKING_THRESHOLD) {
                return [
                    'score' => ($highestPercent / 100),
                    'resource' => $bestMatch,
                ];
            }
        }
        return null;
    }

    private function findMentionedRestaurant(string $query, bool $exactMatch = true)
    {
        $restaurants = Restaurant::all();
        return $this->queryResourceNames($query, $restaurants, $exactMatch);
    }

    private function findMentionedTrail(string $query, bool $exactMatch = true)
    {
        $trails = Trail::all();
        return $this->queryResourceNames($query, $trails, $exactMatch);
    }

    private function analyzeQueryForRestaurant(string $query)
    {
        $resource = $this->findMentionedRestaurant($query);
        if (!$resource) {
            $resource = $this->findMentionedRestaurant($query, false);
        }
        return $resource;
    }

    private function analyzeQueryForTrail(string $query)
    {
        $resource = $this->findMentionedTrail($query);
        if (!$resource) {
            $resource = $this->findMentionedTrail($query, false);
        }
        return $resource;
    }

    public function analyzeQueryForResource($query)
    {
        $stats = [];

        if (strlen(trim($query)) < 4) {
            return null;
        }

        $restaurantResult = $this->analyzeQueryForRestaurant($query);
        $trailResult = $this->analyzeQueryForTrail($query);

        // Determine if we have a restaurant or trail after the previous analysis. If we don't, we'll use Meilisearch.
        if ($restaurantResult || $trailResult) {
            $restaurantScore = $restaurantResult ? $restaurantResult['score'] : 0;
            $trailScore = $trailResult ? $trailResult['score'] : 0;
            if ($restaurantScore > $trailScore) {
                return $restaurantResult['resource'];
            } else {
                return $trailResult['resource'];
            }
        }

        // Is this referencing a restaurant?
        $adjQuery = trim(str_ireplace('restaurant','', $query));
        if ($adjQuery !== '') {
            $hits = $this->client->index(self::RESTAURANTS)->search($adjQuery, [
                'attributesToSearchOn' => ['name'],
                'matchingStrategy' => 'frequency',
                'rankingScoreThreshold' => self::MEILISEARCH_RANKING_THRESHOLD,
                'showRankingScore' => true,
            ])->getHits();
            if ($hits) {
                $topHit = reset($hits);
                $stats['restaurant_' . $topHit['id']] = [
                    'model' => Restaurant::class,
                    'id' => $topHit['id'],
                    'score' => $topHit['_rankingScore'],
                ];
            }
        }

        // Is this referencing a trail?
        $adjQuery = trim(str_ireplace('trail','', $query));
        if ($adjQuery !== '') {
            $hits = $this->client->index(self::TRAILS)->search($adjQuery, [
                'attributesToSearchOn' => ['name'],
                'rankingScoreThreshold' => self::MEILISEARCH_RANKING_THRESHOLD,
                'showRankingScore' => true,
            ])->getHits();
            if ($hits) {
                $topHit = reset($hits);
                $stats['trail_' . $topHit['id']] = [
                    'model' => Trail::class,
                    'id' => $topHit['id'],
                    'score' => $topHit['_rankingScore'],
                ];
            }
        }

        if (!empty($stats)) {
            usort($stats, function ($a, $b) {
                return $b['score'] <=> $a['score'];
            });

            $result = reset($stats);
            $newResource = $result['model']::find($result['id']);
            return $newResource;
        }

        return null;
    }

    /**
     * Use RubixML to do some basic phrase matching and determine if the user is asking for a specific action to be taken.
     */
    public function analyzeQueryForRequests(string $query, $resource) {
    }

    /**
     * TODO: Use the $threadId to analyze previous messages for context.
     */
    public function analyzeQueryForInstantResponse(string $query, int $threadId, $resource = null): ?Message
    {
        if ($resource === null) {
            $message = $this->analyzeQueryForInstantResponseWithoutResource($query);
        } else {
            $message = $this->analyzeQueryForInstantResponseWithResource($query, $resource);
        }

        if (!$message && $resource !== null) {
            switch (get_class($resource)) {
                case Trail::class:
                    $message = $this->analyzeQueryForInstantResponseWithTrail($query, $resource);
                    break;
                default:
                    break;
            }
        }

        if ($message) {
            $message->assistant_thread_id = $threadId;
            $message->is_assistant = true;
            $message->show_in_chat_bubble = $message->show_in_chat_bubble ?? true;
            return $message;
        }

        return null;
    }

    private function analyzeQueryForInstantResponseWithTrail(string $query, Trail $resource): ?Message {
        if ($this->isMapRequested($query, $resource)) {
            // TODO: Are there other maps that could be used beside trail maps? Add them here. Either that, or remove
            // anything we don't need from $resource before passing it into component_params.
            $trail = new Trail();
            $trail->id = $resource->id;

            if ($resource->latitude && $resource->longitude) {
                $trail->latitude = $resource->latitude;
                $trail->longitude = $resource->longitude;
                return new Message([
                    'content' => 'Here\'s a trail map for ' . $resource->name . '.',
                    'component' => 'buttons/ViewMap',
                    'component_params' => json_encode((object)[
                        'resource' => (object)$trail->toArray(),
                    ]),
                    'show_in_chat_bubble' => false,
                ]);
            }

            return new Message([
                'content' => 'I couldn\'t find a trail map for ' . $resource->name . '. Weird. Try searching for it in the itinerary trail lists.',
            ]);
        }
        return null;
    }

    private function analyzeQueryForInstantResponseWithResource(string $query, $resource): ?Message {
        $stripped = strtolower(preg_replace("/[^a-zA-Z0-9 ]/", "", $query));
        if ($stripped === 'thanks' || $stripped === 'thankyou') {
            if ($resource) {
                $responses = [
                    'You\'re welcome. Let me know if you have other questions about ' . $resource->name . '.',
                ];
                return new Message(['content' => $responses[array_rand($responses)]]);
            }
        }

        if ($this->isAccommodationsRequested($query)) {
            return new Message([
                'content' => 'I can help you find suitable accommodations near ' . $resource->name . '.',
                'component' => 'buttons/FindAccommodations',
                'component_params' => [],
                'show_in_chat_bubble' => false,
            ]);
        }
        return null;
    }

    private function analyzeQueryForInstantResponseWithoutResource(string $query): ?Message {
        $stripped = strtolower(preg_replace("/[^a-zA-Z0-9]/", "", $query));
        if ($stripped === '') {
            $responses = [
                'I\'m not sure I understand what you\'re asking. Could you rephrase your question?',
            ];
            return new Message(['content' => $responses[array_rand($responses)]]);
        }

        if ($stripped === 'thanks' || $stripped === 'thankyou') {
            $responses = [
                'You\'re welcome. Let me know if you have other questions.',
            ];
            return new Message(['content' => $responses[array_rand($responses)]]);
        }

        if ($this->isAccommodationsRequested($query)) {
            return new Message([
                'content' => 'I can help you find suitable accommodations.',
                'component' => 'buttons/FindAccommodations',
                'component_params' => [],
                'show_in_chat_bubble' => false,
            ]);
        }

        return null;
    }

    public function isMapRequested(string $query, $resource) {
        $query = preg_replace('/\s+/', ' ', $query);
        $query = trim(strtolower($query));
        if (get_class($resource) === Trail::class) {
            if (str_contains($query, 'map')) {
                return true;
            }
        }
        return false;
    }

    public function isAccommodationsRequested(string $query): bool {
        $query = preg_replace('/\s+/', ' ', $query);
        $query = trim(strtolower($query));
        if (str_contains($query, 'hotel') || str_contains($query, 'motel') || str_contains($query, 'airbnb') || str_contains($query, 'vrbo') || str_contains($query, 'accommodation')) {
            return true;
        }
        return false;
    }

    /**
     * A user may ask how far one resource is from another. Use Gemini or Google Maps since it's more accurate than OpenAI.
     */
    public function isDistanceRequested(string $query, $resource): bool {
        return false;
    }

    /**
     * A user may type something like "create a new itinerary," in which case we'll want to refresh the itinerary, with
     * the same dates.
     */
    public function isNewItineraryRequested(string $query, $resource): bool {
        return false;
    }

    /**
     * A user may type something like "I want different activities," in which case we'll want to refresh the itinerary,
     * but keep the dates and areas.
     */
    public function isNewActivitiesRequested(string $query, $resource): bool {
        return false;
    }

    /**
     * A user may type something like "These hikes are too hard, give me new ones," in which we'll want to update the
     * trails, keeping the dates and areas, unless no alternative trails are found in the areas.
     */
    public function isStrenuousChangeRequested(string $query, $resource): bool {
        return false;
    }

    /**
     * A user may indicate they have dietary restrictions, in which case we'll want to ensure the restaurants meet their
     * standards, and update them if necessary.
     */
    public function isRestaurantChangeRequested(string $query, $resource): bool {
        return false;
    }

    /**
     * A user may indicate everything's too expensive, in which case we'll want refresh the itinerary with a new
     * budget limit.
     */
    public function isBudgetChangeRequested(string $query, $resource): bool {
        return false;
    }

    /**
     * A user may request a weather forecast.
     */
    public function isWeatherForecastRequested(string $query, $resource): bool {
        return false;
    }

    /**
     * A user may type something like "Print my itinerary," in which case we'll want to generate a formatted version.
     */
    public function isPrintableItineraryRequested(string $query, $resource): bool {
        return false;
    }
}