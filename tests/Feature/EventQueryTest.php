<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Event;
use Illuminate\Support\Facades\DB;
use Tests\Support\RefreshTestDatabase;
use Tests\Support\SeedsKanabFixtures;
use Tests\TestCase;

class EventQueryTest extends TestCase
{
    use RefreshTestDatabase;
    use SeedsKanabFixtures;

    private int $concertId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedKanabFixtures();

        $locationId = DB::table('locations')->insertGetId(['name' => 'Kanab Center', 'area_id' => Area::KANAB]);
        $this->concertId = DB::table('events')->insertGetId([
            'name' => 'Evening Concert in the Park', 'source_type' => 'fixture', 'source_id' => 'concert',
            'location_id' => $locationId, 'start_date' => '2027-06-02 17:00:00', 'end_date' => '2027-06-02 21:00:00',
        ]);
        DB::table('event_dates')->insert([
            'event_id' => $this->concertId, 'start_date' => '2027-06-02 17:00:00', 'end_date' => '2027-06-02 21:00:00',
        ]);
    }

    /**
     * The window used to be run through DATE_FORMAT(?, '%h-%i-%s'), a 12-hour clock, so 18:00 was compared as 06:00
     * and evening events never matched evening windows.
     */
    public function test_evening_event_is_found_for_an_evening_window(): void
    {
        $ids = Event::betweenDates('2027-06-02 18:00:00', '2027-06-02 20:00:00')->pluck('events.id')->all();
        $this->assertSame([$this->concertId], $ids);
    }

    public function test_event_is_not_found_outside_its_dates(): void
    {
        $this->assertSame(0, Event::betweenDates('2027-06-02 06:00:00', '2027-06-02 11:00:00')->count());
        $this->assertSame(0, Event::betweenDates('2027-06-03 18:00:00', '2027-06-03 20:00:00')->count());
    }
}
