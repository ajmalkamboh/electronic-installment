<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DatabaseBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:backup-database {--clean-days=30 : Delete backups older than specified days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate an automated snapshot of the database and prune obsolete backups';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting automated database backup procedure...');

        $backupDir = storage_path('app'.DIRECTORY_SEPARATOR.'backups');
        File::ensureDirectoryExists($backupDir);

        $driver = config('database.default');
        $timestamp = now()->format('Y-m-d_His');
        $success = false;

        try {
            if ($driver === 'sqlite') {
                $success = $this->backupSqlite($backupDir, $timestamp);
            } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                $success = $this->backupMysql($backupDir, $timestamp);
            } else {
                $this->warn("Unsupported database driver for direct snapshot: [{$driver}]. Exporting via table iterator...");
                $success = $this->backupMysql($backupDir, $timestamp);
            }
        } catch (Exception $e) {
            $this->error('Backup procedure failed: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! $success) {
            $this->error('Backup generation failed.');

            return self::FAILURE;
        }

        $cleanDays = (int) $this->option('clean-days');
        $this->pruneOldBackups($backupDir, $cleanDays);

        $this->info('Database backup routine finished successfully.');

        return self::SUCCESS;
    }

    /**
     * Backup SQLite database file.
     */
    protected function backupSqlite(string $backupDir, string $timestamp): bool
    {
        $dbPath = config('database.connections.sqlite.database');

        if ($dbPath === ':memory:' || empty($dbPath)) {
            $targetPath = $backupDir.DIRECTORY_SEPARATOR."backup_sqlite_{$timestamp}.sql";
            File::put($targetPath, "-- SQLite database was in-memory or empty at {$timestamp}\n");
            $this->info("Snapshot saved to: {$targetPath}");

            return true;
        }

        if (! File::exists($dbPath)) {
            // Check if relative path
            $dbPath = database_path($dbPath);
        }

        if (! File::exists($dbPath)) {
            $this->warn("SQLite file not found at [{$dbPath}]. Creating empty placeholder backup.");
            $targetPath = $backupDir.DIRECTORY_SEPARATOR."backup_sqlite_{$timestamp}.sql";
            File::put($targetPath, "-- SQLite database was in-memory or empty at {$timestamp}\n");
            $this->info("Snapshot saved to: {$targetPath}");

            return true;
        }

        $targetPath = $backupDir.DIRECTORY_SEPARATOR."backup_sqlite_{$timestamp}.sqlite";
        File::copy($dbPath, $targetPath);

        $sizeMb = round(File::size($targetPath) / 1024 / 1024, 2);
        $this->info("SQLite snapshot created: [{$targetPath}] ({$sizeMb} MB)");

        return true;
    }

    /**
     * Backup MySQL database via portable SQL table dump.
     */
    protected function backupMysql(string $backupDir, string $timestamp): bool
    {
        $database = config('database.connections.mysql.database', 'installment');
        $targetPath = $backupDir.DIRECTORY_SEPARATOR."backup_mysql_{$database}_{$timestamp}.sql";

        $handle = fopen($targetPath, 'w');
        if (! $handle) {
            throw new Exception("Unable to open backup file for writing at [{$targetPath}]");
        }

        fwrite($handle, "-- Automated Database Backup\n");
        fwrite($handle, "-- Database: {$database}\n");
        fwrite($handle, '-- Created: '.now()->toIso8601String()."\n\n");
        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = DB::connection()->getDoctrineSchemaManager()->listTableNames();

        foreach ($tables as $tableName) {
            $this->line("Dumping table [{$tableName}]...");
            fwrite($handle, "-- Table structure for `{$tableName}`\n");
            fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");

            try {
                $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`");
                if (! empty($createTable)) {
                    $prop = 'Create Table';
                    $sql = $createTable[0]->$prop ?? array_values((array) $createTable[0])[1];
                    fwrite($handle, $sql.";\n\n");
                }

                // Dump data
                $rows = DB::table($tableName)->get();
                if ($rows->isNotEmpty()) {
                    fwrite($handle, "-- Dumping data for `{$tableName}`\n");
                    foreach ($rows->chunk(100) as $chunk) {
                        foreach ($chunk as $row) {
                            $rowArray = (array) $row;
                            $columns = array_keys($rowArray);
                            $escapedValues = array_map(function ($val) {
                                if ($val === null) {
                                    return 'NULL';
                                }

                                return "'".addslashes((string) $val)."'";
                            }, array_values($rowArray));

                            $colNames = implode('`, `', $columns);
                            $valString = implode(', ', $escapedValues);
                            fwrite($handle, "INSERT INTO `{$tableName}` (`{$colNames}`) VALUES ({$valString});\n");
                        }
                    }
                    fwrite($handle, "\n");
                }
            } catch (Exception $e) {
                fwrite($handle, "-- Error reading table `{$tableName}`: {$e->getMessage()}\n\n");
            }
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        $sizeMb = round(File::size($targetPath) / 1024 / 1024, 2);
        $this->info("MySQL snapshot created: [{$targetPath}] ({$sizeMb} MB)");

        return true;
    }

    /**
     * Prune backups older than designated retention days.
     */
    protected function pruneOldBackups(string $backupDir, int $cleanDays): void
    {
        if ($cleanDays <= 0) {
            return;
        }

        $cutoff = Carbon::now()->subDays($cleanDays)->timestamp;
        $files = File::files($backupDir);
        $prunedCount = 0;

        foreach ($files as $file) {
            if ($file->getMTime() < $cutoff) {
                File::delete($file->getRealPath());
                $prunedCount++;
            }
        }

        if ($prunedCount > 0) {
            $this->info("Pruned {$prunedCount} old backup files older than {$cleanDays} days.");
        }
    }
}
