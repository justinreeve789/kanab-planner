<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Itinerary\Period;
use App\Models\User;
use App\Policies\ItineraryPolicy;
use Illuminate\Support\Facades\DB;
use Tests\Support\ItineraryAssertions;
use Tests\Support\RefreshTestDatabase;
use Tests\Support\SeedsKanabFixtures;
use Tests\TestCase;

/**
 * Who can change an itinerary, and the endpoints that change it.
 */
class ItineraryEditingTest extends TestCase
{
    use ItineraryAssertions;
    use RefreshTestDatabase;
    use SeedsKanabFixtures;

    private array $itinerary;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedKanabFixtures();
        $this->itinerary = $this->generateItinerary(['duration_days' => 5, 'adventurousness' => 0.6]);
        $this->token = $this->itinerary['edit_token'];
    }

    private function tokenHeaders(?string $token = null): array
    {
        return [ItineraryPolicy::TOKEN_HEADER => $token ?? $this->token];
    }

    private function middleDay(): array
    {
        return $this->itinerary['days'][2];
    }

    public function test_creating_returns_a_one_time_edit_token(): void
    {
        $this->assertSame(40, strlen($this->token));
        $this->assertTrue($this->itinerary['can_edit']);
        $this->assertArrayNotHasKey('edit_token_hash', $this->itinerary);
        $this->assertSame(hash('sha256', $this->token), DB::table('itineraries')->where('id', $this->itinerary['id'])->value('edit_token_hash'));

        $viewed = $this->getJson('/api/v1/itinerary/' . $this->itinerary['id'])->assertOk()->json();
        $this->assertFalse($viewed['can_edit'], 'Anyone with the link can view, but not edit.');
        $this->assertArrayNotHasKey('edit_token', $viewed);

        $this->getJson('/api/v1/itinerary/' . $this->itinerary['id'], $this->tokenHeaders())->assertOk()->assertJsonPath('can_edit', true);
    }

    public function test_creating_with_get_is_no_longer_possible(): void
    {
        $this->getJson('/api/v1/itinerary?start_date=2027-06-01')->assertUnauthorized();
        $this->assertSame(1, DB::table('itineraries')->count());
    }

    public function test_preferences_are_validated(): void
    {
        $this->postJson('/api/v1/itinerary', ['adventurousness' => 4, 'duration_days' => 90])
            ->assertStatus(422)->assertJsonValidationErrors(['adventurousness', 'duration_days']);
    }

    public function test_rename_requires_the_token(): void
    {
        $url = '/api/v1/itinerary/' . $this->itinerary['id'];
        $this->putJson($url, ['name' => 'Hijacked'])->assertForbidden();
        $this->putJson($url, ['name' => 'Hijacked'], $this->tokenHeaders('wrong-token-wrong-token-wrong-token-xxxx'))->assertForbidden();
        $this->putJson($url, ['name' => 'Spring in Kanab'], $this->tokenHeaders())->assertOk()->assertJsonPath('name', 'Spring in Kanab');
    }

    public function test_delete_requires_the_token(): void
    {
        $url = '/api/v1/itinerary/' . $this->itinerary['id'];
        $this->deleteJson($url)->assertForbidden();
        $this->deleteJson($url, [], $this->tokenHeaders())->assertNoContent();
        $this->getJson($url)->assertStatus(422);
        $this->assertSame(0, DB::table('itinerary_days')->count(), 'Days, periods and activities cascade.');
    }

    public function test_signed_in_creator_owns_it_without_a_token(): void
    {
        $user = User::factory()->create();
        $created = $this->actingAs($user, 'sanctum')->postJson('/api/v1/itinerary', ['start_date' => '2027-06-01', 'duration_days' => 4])
            ->assertCreated()->json();

        $this->actingAs($user, 'sanctum')->putJson('/api/v1/itinerary/' . $created['id'], ['name' => 'Mine'])->assertOk();
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/itinerary')->assertOk()
            ->assertJsonCount(1)->assertJsonPath('0.id', $created['id']);

        $stranger = User::factory()->create();
        $this->actingAs($stranger, 'sanctum')->putJson('/api/v1/itinerary/' . $created['id'], ['name' => 'Theirs'])->assertForbidden();
    }

    public function test_listing_requires_sign_in(): void
    {
        $this->getJson('/api/v1/itinerary')->assertUnauthorized();
    }

    public function test_claiming_saves_an_anonymous_itinerary_to_the_account(): void
    {
        $user = User::factory()->create();
        $url = '/api/v1/itinerary/' . $this->itinerary['id'] . '/claim';

        $this->postJson($url)->assertForbidden();
        $this->actingAs($user, 'sanctum')->postJson($url)->assertForbidden();
        $this->actingAs($user, 'sanctum')->postJson($url, [], $this->tokenHeaders())->assertOk()
            ->assertJsonPath('can_edit', true)->assertJsonPath('owned', true);

        $this->assertSame([$this->itinerary['id']], $user->itineraries()->pluck('itineraries.id')->all());
    }

    public function test_change_an_activitys_time_moves_it_to_the_right_period(): void
    {
        $activity = $this->activitiesOfDay($this->middleDay())[0]['activity'];
        $url = '/api/v1/activity/' . $activity['id'];

        $this->putJson($url, ['start_time' => '18:00', 'end_time' => '18:45'])->assertForbidden();
        $updated = $this->putJson($url, ['start_time' => '18:00', 'end_time' => '18:45'], $this->tokenHeaders())->assertOk()->json();

        $this->assertSame('18:00:00', $updated['start_time']);
        $this->assertSame('18:45:00', $updated['end_time']);
        $this->assertEquals(Period::EVENING, DB::table('itinerary_periods')->where('id', $updated['period_id'])->value('type'));

        $this->putJson($url, ['start_time' => '19:00', 'end_time' => '18:00'], $this->tokenHeaders())->assertStatus(422);
    }

    public function test_swap_an_activity_for_another_trail(): void
    {
        $day = $this->middleDay();
        $replacement = $this->fixtureTrailIds[$day['area']['id']][1];
        $activity = $this->activitiesOfDay($day)[0]['activity'];

        $swapped = $this->putJson('/api/v1/activity/' . $activity['id'], [
            'resource_table' => 'trails', 'resource_id' => $replacement,
        ], $this->tokenHeaders())->assertOk()->json();

        $this->assertSame('trails', $swapped['resource_table']);
        $this->assertSame($replacement, $swapped['resource_id']);
        $this->assertSame('hiking', $swapped['activity_type']['name']);
        $this->assertNull($swapped['name']);
        $this->assertSame($activity['start_time'], $swapped['start_time']);
        $this->assertGreaterThan($swapped['start_time'], $swapped['end_time']);

        $this->putJson('/api/v1/activity/' . $activity['id'], ['resource_table' => 'users', 'resource_id' => 1], $this->tokenHeaders())
            ->assertStatus(422);
    }

    public function test_add_and_remove_activities(): void
    {
        $day = $this->middleDay();
        $base = '/api/v1/day/' . $day['id'] . '/activity';

        $this->postJson($base, ['name' => 'Ice cream'])->assertForbidden();
        $this->postJson($base, [], $this->tokenHeaders())->assertStatus(422)->assertJsonValidationErrors('name');

        $note = $this->postJson($base, [
            'name' => 'Ice cream at the Kanab Creamery', 'description' => 'Treat yourself.', 'start_time' => '20:00', 'end_time' => '20:30',
        ], $this->tokenHeaders())->assertCreated()->json();
        $this->assertSame('20:00:00', $note['start_time']);

        $restaurant = $this->fixtureRestaurantIds[$day['area']['id']][5];
        DB::table('locations')->where('id', DB::table('restaurants')->where('id', $restaurant)->value('location_id'))
            ->update(['url' => 'https://diner.example']);
        $meal = $this->postJson($base, ['resource_table' => 'restaurants', 'resource_id' => $restaurant, 'start_time' => '13:00'], $this->tokenHeaders())
            ->assertCreated()->json();
        $this->assertSame('dining', $meal['activity_type']['name']);
        $this->assertSame('15:00:00', $meal['end_time'], 'End defaults to the restaurant\'s usual meal length.');
        $this->assertSame('https://diner.example', $meal['resource']['location']['url'], 'The website button reads the location.');

        $periodNote = $this->postJson('/api/v1/period/' . $day['periods'][0]['id'] . '/activity', ['name' => 'Sunrise'], $this->tokenHeaders())
            ->assertCreated()->json();
        $this->assertSame($day['periods'][0]['id'], $periodNote['period_id']);

        $this->deleteJson('/api/v1/activity/' . $note['id'])->assertForbidden();
        $this->deleteJson('/api/v1/activity/' . $note['id'], [], $this->tokenHeaders())->assertNoContent();
        $this->getJson('/api/v1/activity/' . $note['id'])->assertNotFound();
    }

    public function test_edit_the_days_note_and_clear_the_day(): void
    {
        $url = '/api/v1/day/' . $this->middleDay()['id'];

        $this->putJson($url, ['preamble' => 'Rest day'])->assertForbidden();
        $this->putJson($url, ['preamble' => 'Rest day'], $this->tokenHeaders())->assertOk()->assertJsonPath('preamble', 'Rest day');
        $this->assertSame('Rest day', $this->getJson('/api/v1/itinerary/' . $this->itinerary['id'])->json('days.2.preamble'));

        $this->deleteJson($url, [], $this->tokenHeaders())->assertOk();
        $this->assertSame([], $this->activitiesOfDay($this->getJson($url)->json()));
    }

    public function test_regenerate_a_day(): void
    {
        $day = $this->middleDay();
        $otherDaysTrails = [];
        foreach ($this->itinerary['days'] as $other) {
            if ($other['id'] !== $day['id']) {
                foreach ($this->activitiesOfDay($other) as $row) {
                    if ($row['activity']['resource_table'] === 'trails') {
                        $otherDaysTrails[] = $row['activity']['resource_id'];
                    }
                }
            }
        }

        $this->postJson('/api/v1/day/' . $day['id'] . '/regenerate')->assertForbidden();
        $fresh = $this->postJson('/api/v1/day/' . $day['id'] . '/regenerate', [], $this->tokenHeaders())->assertOk()->json();

        $this->assertSame($day['id'], $fresh['id']);
        $this->assertSame($day['area_id'] ?? $day['area']['id'], $fresh['area_id']);
        $activities = $this->activitiesOfDay($fresh);
        $this->assertNotEmpty($activities, 'Regenerated days get at least their meals.');
        foreach ($activities as $row) {
            if ($row['activity']['resource_table'] === 'trails') {
                $this->assertNotContains($row['activity']['resource_id'], $otherDaysTrails, 'No trail repeats from another day.');
            }
        }
        $this->assertSame(count($activities), DB::table('itinerary_activities')
            ->whereIn('period_id', array_column($fresh['periods'], 'id'))->count(), 'Old activities were removed.');
    }

    public function test_itinerary_generation_is_rate_limited(): void
    {
        for ($i = 0; $i < 9; $i++) {
            $this->postJson('/api/v1/itinerary', ['start_date' => '2027-06-01', 'duration_days' => 2])->assertCreated();
        }
        $this->postJson('/api/v1/itinerary', ['start_date' => '2027-06-01', 'duration_days' => 2])->assertStatus(429);
    }
}
