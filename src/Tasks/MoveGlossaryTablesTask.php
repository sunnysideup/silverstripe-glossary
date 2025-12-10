<?php

declare(strict_types=1);


namespace Sunnysideup\Glossary\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;

class MoveGlossaryTablesTask extends BuildTask
{
    protected $title = 'Move Glossary Tables';
    protected $description = 'Migrates glossary tables from namespaced format to base format.';

    private static string $segment = 'move-glossary-tables';

    private static $conversions = [
        'GlossaryPage'          => 'Sunnysideup_Glossary_PageTypes_GlossaryPage',
        'GlossaryPage_Live'     => 'Sunnysideup_Glossary_PageTypes_GlossaryPage_Live',
        'GlossaryPage_Versions' => 'Sunnysideup_Glossary_PageTypes_GlossaryPage_Versions',
    ];

    public function run($request)
    {

        $tables = $this->config()->get('conversions');
        foreach ($tables as $newTable => $oldTable) {
            $this->migrateTable($oldTable, $newTable);
        }

        DB::alteration_message('Done.', 'created');
    }

    /**
     * Move data only if:
     * - old table exists
     * - new table does NOT exist OR is empty
     */
    private function migrateTable(string $oldTable, string $newTable): void
    {
        $oldExists = $this->tableExists($oldTable);
        $newExists = $this->tableExists($newTable);
        $newEmpty  = $newExists ? $this->tableIsEmpty($newTable) : true;

        if (! $oldExists) {
            DB::alteration_message("Skip: {$oldTable} does not exist.", 'info');
            return;
        }

        if (! $newEmpty) {
            DB::alteration_message("Skip: {$newTable} exists and has data.", 'info');
            return;
        }

        // --- Create new table if missing ---
        if (! $newExists) {
            DB::alteration_message("Creating {$newTable}…", 'created');
            DB::query("CREATE TABLE {$newTable} LIKE {$oldTable}");
        }

        // --- Copy data ---
        DB::alteration_message("Copying data from {$oldTable} → {$newTable}…", 'created');
        DB::query("INSERT INTO {$newTable} SELECT * FROM {$oldTable}");

        DB::alteration_message("✔ Copied {$oldTable} → {$newTable}", 'created');
    }

    private function tableExists(string $table): bool
    {
        $result = DB::query("SHOW TABLES LIKE '{$table}'")->value();
        return ! empty($result);
    }

    private function tableIsEmpty(string $table): bool
    {
        $count = DB::query("SELECT COUNT(*) FROM {$table}")->value();
        return ((int) $count) === 0;
    }
}
