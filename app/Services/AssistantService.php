<?php
/**
 * A user may type things like "I want a new itinerary" or "This is too expensive," in which case we can identify and
 * run appropriate functions in ItineraryService to update the itinerary accordingly.
 *
 * A lot of these requests should be included in the training data to indicate what steps the user can take to do it
 * themselves. The assistant can provide basic how-to information in this regard.
 */

namespace App\Services;

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