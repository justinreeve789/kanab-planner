<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Assistant\Message;
use Illuminate\Support\Facades\DB;
use Tests\Support\RefreshTestDatabase;
use Tests\Support\SeedsKanabFixtures;
use Tests\TestCase;

/**
 * Replaces the old Unit\AssistantTest::test_transform_links, which called a method that doesn't exist on
 * AssistantService and asserted nothing.
 */
class AssistantMessageTest extends TestCase
{
    use RefreshTestDatabase;
    use SeedsKanabFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedKanabFixtures();
        DB::table('locations')->insert([
            'name' => 'Zion National Park', 'area_id' => Area::ZION, 'url' => 'https://www.nps.gov/zion/', 'is_usable' => 1,
        ]);
        $this->insertTrail([
            'name' => 'Angels Landing', 'area_id' => Area::ZION, 'url' => 'https://www.nps.gov/zion/planyourvisit/angels-landing.htm',
        ]);
    }

    public function test_transform_links_links_known_places(): void
    {
        $html = Message::transformLinks(
            'I can guide you on where to find trail maps for Zion National Park. Angels Landing needs a permit.'
        );

        $this->assertStringContainsString('<a href="https://www.nps.gov/zion/" target="_blank" rel="noopener noreferrer"><span>Zion National Park</span></a>', $html);
        $this->assertStringContainsString('<a href="https://www.nps.gov/zion/planyourvisit/angels-landing.htm" target="_blank" rel="noopener noreferrer"><span>Angels Landing</span></a>', $html);
    }

    public function test_transform_links_leaves_unknown_text_alone(): void
    {
        $text = 'AllTrails is another excellent option for exploring various trails.';
        $this->assertSame($text, Message::transformLinks($text));
    }

    public function test_overlapping_names_produce_one_link(): void
    {
        DB::table('locations')->insert(['name' => 'Zion', 'area_id' => Area::ZION, 'url' => 'https://example.test/zion', 'is_usable' => 1]);

        $html = Message::transformLinks('Visit Zion National Park early, or explore Zion after dark.');
        $this->assertSame(2, substr_count($html, '<a '), $html);
        $this->assertStringContainsString('<a href="https://www.nps.gov/zion/" target="_blank" rel="noopener noreferrer"><span>Zion National Park</span></a>', $html);
        $this->assertStringContainsString('explore <a href="https://example.test/zion"', $html);
    }

    public function test_message_text_is_escaped(): void
    {
        $html = Message::transformLinks('<img src=x onerror=alert(1)> Zion National Park & more');
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
        $this->assertStringContainsString('&amp; more', $html);
    }

    public function test_unsafe_urls_are_never_linked(): void
    {
        DB::table('locations')->insert(['name' => 'Coral Pink Sand Dunes', 'area_id' => Area::KANAB, 'url' => 'javascript:alert(1)', 'is_usable' => 1]);
        $this->assertStringNotContainsString('javascript:', Message::transformLinks('Coral Pink Sand Dunes is fun.'));
    }
}
