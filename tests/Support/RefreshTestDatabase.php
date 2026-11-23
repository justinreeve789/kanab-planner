<?php

namespace Tests\Support;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * RefreshDatabase drops every table before the first test. Wrap it with a guard so a stray .env can never point the
 * suite at a real database: the schema name must end in _test / _testing.
 */
trait RefreshTestDatabase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase()
    {
        $database = (string) DB::connection()->getDatabaseName();
        if (!preg_match('/(_test|_testing)$/', $database)) {
            throw new RuntimeException(
                "Refusing to refresh database [{$database}]. Point DB_DATABASE at a schema ending in _test."
            );
        }
    }
}
