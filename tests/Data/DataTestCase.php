<?php

namespace Tests\Data;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Throwable;

/**
 * Base for checks that only make sense against the real dataset (database/dumps). Points the default connection at
 * TEST_DATA_DB_DATABASE (built by tests/Data/load-dumps.sh) and wraps every test in a transaction that is rolled
 * back, so the dataset is never modified. Skips when the schema isn't loaded.
 */
abstract class DataTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $database = env('TEST_DATA_DB_DATABASE');
        if (!$database) {
            $this->markTestSkipped('TEST_DATA_DB_DATABASE is not set.');
        }

        config(['database.connections.mysql.database' => $database]);
        DB::purge('mysql');

        try {
            $trails = DB::table('trails')->count();
        } catch (Throwable $e) {
            $this->markTestSkipped("Dataset schema [{$database}] isn't available. Run tests/Data/load-dumps.sh.");
        }
        if ($trails === 0) {
            $this->markTestSkipped("Dataset schema [{$database}] has no trails. Run tests/Data/load-dumps.sh.");
        }

        DB::beginTransaction();
        // The smoke tests generate dozens of itineraries in one go.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }

    protected function tearDown(): void
    {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        parent::tearDown();
    }
}
