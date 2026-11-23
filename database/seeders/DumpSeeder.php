<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Loads the dataset in database/dumps (written by dump.sh) into an already-migrated schema.
 *
 *   php artisan migrate --seed            # fresh install
 *   php artisan db:seed --class=DumpSeeder
 *
 * The migrations used to do this themselves, which made every migrate:fresh (including the test suite's) import
 * ~10 MB of data, and gunzipped the dumps in place in the repo while doing it. This streams the .gz files instead.
 */
class DumpSeeder extends Seeder
{
    /**
     * Parents before children. areas and activity_types aren't listed: their reference rows are created by their
     * migrations, and the dumps of them predate columns those rows need.
     */
    public const TABLES = [
        'locations',
        'location_hours',
        'location_images',
        'location_tags',
        'restaurants',
        'trails',
        'trail_images',
        'trail_geojson',
        'events',
        'event_images',
        'resource_data',
        'resource_text',
        'resource_merges',
        'resource_statuses',
        'resource_busy',
        'blogs',
        'blog_posts',
        'blog_post_images',
        'guides',
        'openai_prompts',
        'assistant_sessions',
        'assistant_threads',
        'assistant_messages',
    ];

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            foreach (self::TABLES as $table) {
                $file = $this->dumpFile($table);
                if ($file === null) {
                    continue;
                }
                $this->command?->info("Importing {$table}");
                $this->import($file);
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        // The seeded accommodation points at the location it shares with the first dumped location.
        DB::table('accommodations')->where('id', 1)->whereNull('location_id')
            ->whereExists(fn($q) => $q->from('locations')->where('id', 1))
            ->update(['location_id' => 1]);
    }

    public static function dumpFile(string $table): ?string
    {
        foreach (["{$table}.sql.gz", "{$table}.sql"] as $name) {
            $path = database_path('dumps/' . $name);
            if (is_file($path)) {
                return $path;
            }
        }
        return null;
    }

    /**
     * mysqldump writes one statement per line (INSERTs are extended, so a line can be several MB).
     */
    private function import(string $path): void
    {
        $handle = str_ends_with($path, '.gz') ? gzopen($path, 'rb') : fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Could not open {$path}");
        }
        $read = str_ends_with($path, '.gz') ? 'gzgets' : 'fgets';
        $close = str_ends_with($path, '.gz') ? 'gzclose' : 'fclose';

        try {
            $statement = '';
            while (($line = $read($handle)) !== false) {
                if ($statement === '' && (trim($line) === '' || str_starts_with($line, '--'))) {
                    continue;
                }
                $statement .= $line;
                if (str_ends_with(rtrim($line), ';')) {
                    DB::unprepared($statement);
                    $statement = '';
                }
            }
        } finally {
            $close($handle);
        }
    }
}
