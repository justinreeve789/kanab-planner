<?php

namespace Tests\Data;

use App\Models\Area;
use App\Models\Restaurant;
use App\Models\Trail;
use Tests\Support\ItineraryAssertions;

/**
 * Generate an itinerary from every base area at several adventurousness levels against the real data. Catches
 * crashes and anything scheduled that the app itself would refuse to show.
 */
class ItinerarySmokeTest extends DataTestCase
{
    use ItineraryAssertions;

    public function test_assortment_of_areas(): void
    {
        $usableTrails = Trail::pluck('id')->all();
        $usableRestaurants = Restaurant::pluck('id')->all();

        foreach (Area::pluck('id') as $areaId) {
            foreach ([0.1, 0.5, 0.9] as $adventurousness) {
                $label = "base area {$areaId}, adventurousness {$adventurousness}";
                $itinerary = $this->generateItinerary([
                    'base_area_id' => $areaId,
                    'start_date' => '2027-06-14',
                    'duration_days' => 6,
                    'adventurousness' => $adventurousness,
                ]);

                $this->assertCount(6, $itinerary['days'], $label);
                $this->assertSame($areaId, $itinerary['days'][0]['area']['id'], $label);
                $this->assertEmpty(array_diff($this->resourceIdsOfTable($itinerary, 'trails'), $usableTrails), $label);
                $this->assertEmpty(array_diff($this->resourceIdsOfTable($itinerary, 'restaurants'), $usableRestaurants), $label);

                $trailIds = $this->resourceIdsOfTable($itinerary, 'trails');
                $this->assertSame(array_values(array_unique($trailIds)), $trailIds, $label);
            }
        }
    }
}
