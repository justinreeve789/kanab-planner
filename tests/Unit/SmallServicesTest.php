<?php

namespace Tests\Unit;

use App\Models\Itinerary\Period;
use App\Models\Resource;
use App\Models\Trail;
use App\ResourceEnum;
use App\Services\ConversionService;
use App\Services\RestaurantService;
use Tests\TestCase;

class SmallServicesTest extends TestCase
{
    public function test_average_meal_price_by_google_price_level(): void
    {
        $service = new RestaurantService();
        $this->assertSame([8, 10, 15, 30, 50, 15], array_map(
            fn($level) => $service->calculateAveragePriceForPriceLevel($level),
            [0, 1, 2, 3, 4, null]
        ));
    }

    public function test_period_times_are_ordered_through_the_day(): void
    {
        $starts = array_map(fn($p) => Period::getStartTimeForActivities($p), Period::getPeriodTypes());
        $sorted = $starts;
        sort($sorted);
        $this->assertSame($sorted, $starts);

        foreach ([Period::MORNING, Period::AFTERNOON, Period::EVENING] as $period) {
            $meal = Period::getTimesForMeals($period);
            $this->assertLessThan($meal['endTime'], $meal['startTime']);
        }
        $this->assertNull(Period::getTimesForMeals(Period::NIGHT));
        $this->assertNull(Period::getStartTimeForActivities(99));
    }

    public function test_resource_table_lookups(): void
    {
        $this->assertSame(Trail::class, Resource::getModelForResourceTable(ResourceEnum::TRAILS_TABLE));
        $this->assertSame('trail', Resource::getTypeForResourceTable(ResourceEnum::TRAILS_TABLE));
        $this->expectException(\Exception::class);
        Resource::getModelForResourceTable('users');
    }

    public function test_resource_end_time_adds_average_hours(): void
    {
        $trail = new Trail(['average_hours' => 2.5]);
        $this->assertSame('11:30:00', $trail->calculateEndTime('2027-06-01', '09:00:00'));
    }

    public function test_url_detection_and_extraction(): void
    {
        $service = new ConversionService();
        $this->assertTrue($service->isUrl('https://www.nps.gov/zion/index.htm'));
        $this->assertFalse($service->isUrl('zion national park'));

        $html = '<p>See <a href="https://www.nps.gov/zion/">Zion</a> or https://visitsouthernutah.com/events?x=1.</p>';
        $this->assertSame(
            ['https://www.nps.gov/zion/', 'https://visitsouthernutah.com/events?x=1'],
            $service->extractUrlsFromHtml($html)
        );
    }

    public function test_image_extraction_from_html(): void
    {
        $service = new ConversionService();
        $html = "<div>\n<img class=\"a\" src=\"https://cdn.test/one.jpg\" alt=\"\">\n<img src='/two.png'>\n</div>";
        $this->assertSame(['https://cdn.test/one.jpg', '/two.png'], $service->extractImagesFromHtml($html));
    }

    public function test_extracts_every_image_on_a_line_including_backgrounds(): void
    {
        $html = '<img src="/a.jpg"><img alt="b" src=\'/b.jpg\'><div style="background-image: url(/c.png)"></div><div style="background-image:url(\'/d.webp\')"></div>';
        $this->assertSame(['/a.jpg', '/b.jpg', '/c.png', '/d.webp'], (new ConversionService())->extractImagesFromHtml($html));
    }

    public function test_extracts_pdf_links(): void
    {
        $html = '<a href="/maps/zion.pdf">Map</a> <a href=\'https://x.test/guide.PDF?v=2\'>Guide</a> <a href="/page.html">Page</a>';
        $this->assertSame(['/maps/zion.pdf', 'https://x.test/guide.PDF?v=2'], (new ConversionService())->extractPDFsFromHtml($html));
    }
}
