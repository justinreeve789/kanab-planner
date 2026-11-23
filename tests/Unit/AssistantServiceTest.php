<?php

namespace Tests\Unit;

use App\Models\Restaurant;
use App\Models\Trail;
use App\Services\AssistantService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The assistant's keyword-based intent detection (what runs before anything is sent to OpenAI).
 */
class AssistantServiceTest extends TestCase
{
    private AssistantService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AssistantService();
    }

    public static function itineraryRequests(): array
    {
        return [
            ['Can you create a new itinerary for me?', true],
            ['Please REVISE my itinerary!', true],
            ['make an itinerary with more hikes', true],
            ['What is on my itinerary tomorrow?', false],
            ['Create a list of restaurants', false],
        ];
    }

    #[DataProvider('itineraryRequests')]
    public function test_new_itinerary_requests(string $query, bool $expected): void
    {
        $this->assertSame($expected, $this->service->isNewItineraryRequested($query));
    }

    public static function accommodationRequests(): array
    {
        return [
            ['Any good hotels near Kanab?', true],
            ['Is there an Airbnb by Zion', true],
            ['VRBO options?', true],
            ['Where can I get dinner?', false],
        ];
    }

    #[DataProvider('accommodationRequests')]
    public function test_accommodation_requests(string $query, bool $expected): void
    {
        $this->assertSame($expected, $this->service->isAccommodationsRequested($query));
    }

    public function test_map_requests_only_apply_to_trails(): void
    {
        $this->assertTrue($this->service->isMapRequested('Show me the   MAP', new Trail()));
        $this->assertFalse($this->service->isMapRequested('show me the map', new Restaurant()));
        $this->assertFalse($this->service->isMapRequested('show me the map', null), 'No resource in context must not crash.');
    }

    public function test_requests_nudge_preferences(): void
    {
        $nudged = $this->service->adjustPreferencesForQuery('Something easier with fewer crowds please', ['strenuousness' => 0.6]);
        $this->assertEquals(0.4, $nudged['strenuousness']);
        $this->assertEquals(0.7, $nudged['solitariness']);
        $this->assertArrayNotHasKey('adventurousness', $nudged);

        $this->assertEquals(1.0, $this->service->adjustPreferencesForQuery('much harder hikes', ['strenuousness' => 0.9])['strenuousness']);
        $this->assertSame([], $this->service->adjustPreferencesForQuery('what time is it'));
    }
}
