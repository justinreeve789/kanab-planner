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

    public function test_events_overlapping_a_window_are_found(): void
    {
        $locationId = DB::table('locations')->insertGetId(['name' => 'Kanab Park', 'area_id' => Area::KANAB]);
        $marketId = DB::table('events')->insertGetId([
            'name' => 'Farmers Market', 'source_type' => 'fixture', 'source_id' => 'market', 'location_id' => $locationId,
            'start_date' => '2027-06-02 09:00:00', 'end_date' => '2027-06-02 12:00:00',
        ]);
        DB::table('event_dates')->insert(['event_id' => $marketId, 'start_date' => '2027-06-02 09:00:00', 'end_date' => '2027-06-02 12:00:00']);

        $morning = Event::betweenDates('2027-06-02 06:00:00', '2027-06-02 11:00:00')->pluck('events.id')->all();
        $this->assertSame([$marketId], $morning, 'Starts inside the window.');
        $this->assertSame([$marketId], Event::betweenDates('2027-06-02 11:30:00', '2027-06-02 16:00:00')->pluck('events.id')->all(), 'Still running.');
        $this->assertSame(0, Event::betweenDates('2027-06-02 12:00:00', '2027-06-02 13:00:00')->where('events.id', $marketId)->count(), 'Ended.');
    }

    public function test_open_ended_occurrences_last_four_hours_or_until_ten(): void
    {
        $locationId = DB::table('locations')->insertGetId(['name' => 'Kanab Library', 'area_id' => Area::KANAB]);
        $talkId = DB::table('events')->insertGetId([
            'name' => 'Ranger Talk', 'source_type' => 'fixture', 'source_id' => 'talk', 'location_id' => $locationId,
            'start_date' => '2027-06-03 19:00:00',
        ]);
        DB::table('event_dates')->insert(['event_id' => $talkId, 'start_date' => '2027-06-03 19:00:00']);

        $this->assertSame(1, Event::betweenDates('2027-06-03 21:30:00', '2027-06-03 23:00:00')->where('events.id', $talkId)->count());
        $this->assertSame(0, Event::betweenDates('2027-06-03 22:00:00', '2027-06-03 23:00:00')->where('events.id', $talkId)->count());
        $this->assertSame('2027-06-03 22:00:00', \App\Models\EventDate::where('event_id', $talkId)->first()->effective_end_date);
    }
}
