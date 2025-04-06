<?php

namespace Tests\Unit;

use App\Models\Trail;
use Tests\TestCase;

class TrailTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function test_unusable_trails_arent_retrieved(): void
    {
        $trails = Trail::all();
        foreach ($trails as $trail) {
            $this->assertTrue((bool) $trail->is_usable);
        }
    }
}
