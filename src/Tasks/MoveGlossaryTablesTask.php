<?php

declare(strict_types=1);


namespace Sunnysideup\Glossary\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;
use SilverStripe\PolyExecution\Output\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

class MoveGlossaryTablesTask extends BuildTask
{
    protected string $title = 'Move Glossary Tables';

    protected static string $description = 'Migrates glossary tables from namespaced format to base format.';

    protected static string $commandName = 'move-glossary-tables';

    private static array $conversions = [
        'GlossaryPage'          => 'Sunnysideup_Glossary_PageTypes_GlossaryPage',
        'GlossaryPage_Live'     => 'Sunnysideup_Glossary_PageTypes_GlossaryPage_Live',
        'GlossaryPage_Versions' => 'Sunnysideup_Glossary_PageTypes_GlossaryPage_Versions',
    ];

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $tables = $this->config()->get('conversions') ?? [];
        foreach ($tables as $newTable => $oldTable) {
            $this->migrateTable($oldTable, $newTable, $output);
        }

        return Command::SUCCESS;
    }

    /**
     * Move data only if:
     * - old table exists
     * - new table does NOT exist OR is empty
     */
    private function migrateTable(string $oldTable, string $newTable, PolyOutput $output): void
    {
        $oldExists = $this->tableExists($oldTable);
        $newExists = $this->tableExists($newTable);
        $newEmpty  = $newExists ? $this->tableIsEmpty($newTable) : true;

        if (! $oldExists) {
            $output->writeln("Skip: {$oldTable} does not exist.");
            return;
        }

        if (! $newEmpty) {
            $output->writeln("Skip: {$newTable} exists and has data.");
            return;
        }

        // --- Create new table if missing ---
        if (! $newExists) {
            $output->writeln("Creating {$newTable}...");
            DB::query("CREATE TABLE {$newTable} LIKE {$oldTable}");
        }

        // --- Copy data ---
        $output->writeln("Copying data from {$oldTable} -> {$newTable}...");
        DB::query("INSERT INTO {$newTable} SELECT * FROM {$oldTable}");

        $output->writeln("Copied {$oldTable} -> {$newTable}.");
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
