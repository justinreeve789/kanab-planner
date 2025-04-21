<?php

namespace Tests\Unit;

use App\Models\Trail;
use App\Services\AssistantService;
use Tests\TestCase;

class AssistantTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function test_components_have_parameters(): void
    {
        $this->assertTrue(true);
    }

    public function test_transform_links(): void
    {
        $assistantService = new AssistantService();
        $assistantService->transformLinks('I can guide you on where to find trail maps for southern Utah\'s national parks. For Zion National Park, you can download maps from their official site, and for Canyonlands, you can also find maps there. AllTrails is another excellent option for exploring various trails and their details. If you need specific trail recommendations or information, feel free to ask!');
    }
}
