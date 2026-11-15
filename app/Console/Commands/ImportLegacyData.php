<?php

namespace App\Console\Commands;

use App\Support\Legacy\LegacyImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Throwable;

class ImportLegacyData extends Command
{
    /**
     * @var string
     */
    protected $signature = 'import:legacy
                            {--dry-run : Read the legacy database and report what would be imported without writing}
                            {--only=* : Limit the import to these entities (e.g. --only=organisers,events)}
                            {--limit= : Maximum number of legacy rows read per table (smoke runs)}';

    /**
     * @var string
     */
    protected $description = 'Import the legacy Attendize database into the new schema (idempotent, keyed by legacy_id)';

    public function handle(): int
    {
        if (! $this->ensureLegacyConnection()) {
            return self::FAILURE;
        }

        try {
            $entities = LegacyImporter::normaliseOnly((array) $this->option('only'));
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
            $this->line('Available entities: '.implode(', ', LegacyImporter::ENTITIES));

            return self::FAILURE;
        }

        $limit = $this->option('limit');

        if ($limit !== null && (! is_numeric($limit) || (int) $limit < 1)) {
            $this->error('The --limit option must be a positive integer.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->warnLegacyLoungeAvailability();

        $this->info(sprintf(
            '%simport from [%s] into the [%s] connection: %s',
            $dryRun ? 'DRY RUN — ' : '',
            config('database.connections.legacy.database'),
            config('database.default'),
            implode(', ', $entities),
        ));

        if ($limit !== null) {
            $this->line('Row limit per table: '.(int) $limit);
        }

        $importer = new LegacyImporter(
            $dryRun,
            $limit === null ? null : (int) $limit,
            fn (string $message) => $this->line($message),
        );

        try {
            $importer->run($entities);
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('Import failed: '.$e->getMessage());
            $this->line($e->getFile().':'.$e->getLine());

            return self::FAILURE;
        }

        $this->renderSummary($importer, $entities, $dryRun);

        return self::SUCCESS;
    }

    /**
     * Fail with a readable message instead of a stack trace when the read-only
     * legacy connection is not configured or unreachable.
     */
    private function ensureLegacyConnection(): bool
    {
        $database = config('database.connections.legacy.database');

        if (blank($database)) {
            $this->error('The [legacy] connection has no database configured. Set DB_DATABASE_LEGACY in your .env file.');
            $this->line('The read-only legacy connections are defined in config/database.php.');

            return false;
        }

        try {
            DB::connection('legacy')->getPdo();
        } catch (Throwable $e) {
            $this->error("Could not connect to the legacy database [{$database}]: ".$e->getMessage());
            $this->line('Check DB_HOST / DB_PORT / DB_USERNAME / DB_PASSWORD / DB_DATABASE_LEGACY in your .env file.');

            return false;
        }

        $required = [
            'accounts', 'organisers', 'events', 'tickets', 'orders', 'order_items',
            'order_statuses', 'attendees', 'currencies', 'timezones', 'countries',
        ];

        $missing = array_values(array_filter(
            $required,
            fn (string $table) => ! Schema::connection('legacy')->hasTable($table),
        ));

        if ($missing !== []) {
            $this->error('The legacy database is missing expected tables: '.implode(', ', $missing));
            $this->line('Is DB_DATABASE_LEGACY pointing at the Attendize database?');

            return false;
        }

        return true;
    }

    /**
     * The lounge database is optional (only used for club Stripe accounts), but
     * an unreachable connection is still a configuration error worth stopping for.
     */
    private function warnLegacyLoungeAvailability(): void
    {
        $database = config('database.connections.legacy_lounge.database');

        if (blank($database)) {
            $this->warn('DB_DATABASE_LEGACY_LOUNGE is not configured; club Stripe accounts will not be imported.');

            return;
        }

        try {
            DB::connection('legacy_lounge')->getPdo();
        } catch (Throwable $e) {
            $this->warn('Could not connect to the lounge database ['.$database.']: '.$e->getMessage());
            $this->warn('Club Stripe accounts will not be imported.');
        }
    }

    /**
     * @param  list<string>  $entities
     */
    private function renderSummary(LegacyImporter $importer, array $entities, bool $dryRun): void
    {
        $counts = $importer->counts();
        $rows = [];

        foreach (LegacyImporter::ENTITIES as $entity) {
            $ran = in_array($entity, $entities, true);
            $row = $counts[$entity] ?? ['created' => 0, 'updated' => 0, 'skipped' => 0];

            $rows[] = [
                $entity,
                $ran ? $row['created'] : '—',
                $ran ? $row['updated'] : '—',
                $ran ? $row['skipped'] : '—',
            ];
        }

        $this->newLine();
        $this->info($dryRun ? 'Dry run summary (nothing was written)' : 'Import summary');
        $this->table(['Entity', 'Created', 'Updated', 'Skipped'], $rows);

        $warnings = $importer->warnings();

        if ($warnings === []) {
            $this->info('No warnings.');

            return;
        }

        $this->newLine();
        $this->warn(count($warnings).' warning(s):');

        $shown = array_slice($warnings, 0, 40);

        foreach ($shown as $warning) {
            $this->line(' • '.$warning);
        }

        if (count($warnings) > count($shown)) {
            $this->line(' …and '.(count($warnings) - count($shown)).' more warning(s).');
        }
    }
}
