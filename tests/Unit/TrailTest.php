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

    public function test_difficulty_derived_from_strenuousness_matches_difficulty_derived_from_imports(): void
    {
    }

    public function test_higher_adventurousness_and_strenuousness_results_in_longer_tails(): void
    {
    }
}