<?php

namespace App\Console\Commands;

use App\Mail\DbBackupMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// use Illuminate\Support\Facades\Log as Logger;
// use Illuminate\Support\Facades\Mail;
// use App\Mail\NotifyIfLogsDoesNotGenerate;


class DbBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:db_backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Database Backup';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()



    {
        $driver = config('database.default'); // sqlite | pgsql
        $conn   = config("database.connections.$driver");

        if (!$driver || !$conn) {
            Log::error('Backup: database connection not configured', ['driver' => $driver]);
            return;
        }

        // ✅ Date-based folder
        $dateFolder = now()->format('Y-m-d');
        $destDir = base_path("storage/dbbackup/{$dateFolder}");
        File::ensureDirectoryExists($destDir);

        $backupFile = null;

        /* ===========================
     | SQLITE BACKUP
     =========================== */
        if ($driver === 'sqlite') {

            $dbPath = $conn['database'] ?? null;

            // Resolve relative SQLite path
            if ($dbPath && !Str::startsWith($dbPath, [':memory:', '/', '\\']) && !preg_match('/^[A-Za-z]:\\\\/', $dbPath)) {
                $dbPath = base_path($dbPath);
            }

            if (!$dbPath || $dbPath === ':memory:' || !File::exists($dbPath)) {
                Log::error('Backup: SQLite database file not found', ['dbPath' => $dbPath]);
                return;
            }

            $backupFile = $destDir . DIRECTORY_SEPARATOR
                . 'sqlite_backup_' . now()->format('Ymd_His') . '.sqlite';

            File::copy($dbPath, $backupFile);
        }

        /* ===========================
     | POSTGRESQL BACKUP
     =========================== */ elseif ($driver === 'pgsql') {

            // Run Spatie DB backup
            $cleanExit = Artisan::call('backup:clean');
            $runExit   = Artisan::call('backup:run', ['--only-db' => true]);

            if ($cleanExit !== 0 || $runExit !== 0) {
                Log::error('Backup: pgsql backup failed', [
                    'output' => Artisan::output(),
                ]);
                return;
            }

            // Move latest ZIP into date folder
            $sourceFiles = glob(base_path('storage/dbbackup/*.zip')) ?: [];

            $latestZip = collect($sourceFiles)
                ->sortBy(fn($p) => @filemtime($p) ?: 0)
                ->last();

            if (!$latestZip) {
                Log::error('Backup: no pgsql zip found after backup');
                return;
            }

            $backupFile = $destDir . DIRECTORY_SEPARATOR
                . 'pgsql_backup_' . now()->format('Ymd_His') . '.zip';

            File::move($latestZip, $backupFile);
        } else {
            Log::warning('Backup: unsupported driver', ['driver' => $driver]);
            return;
        }

        /* ===========================
     | EMAIL BACKUP
     =========================== */
        $raw = env('ADMIN_MAIL_RECEIVERS', '');
        $receivers = array_values(array_filter(array_map('trim', preg_split('/[,\s]+/', $raw))));

        if ($receivers && $backupFile) {
            Mail::to($receivers)->queue(
                new DbBackupMail([
                    'file' => $backupFile,
                    'date' => now()->format('Y-M-d'),
                    'body' => "Database Backup ({$driver})",
                ])
            );
        }

        Log::info('Backup completed', [
            'driver' => $driver,
            'file'   => $backupFile,
        ]);
    }


    public function sqlBackup()
    {
        // $filename = "backup-" . Carbon::now()->format('Y-m-d') . ".sql";
        // // Create backup folder and set permission if not exist.
        // $storageAt = storage_path() . "/app/backup/";
        // if (!File::exists($storageAt)) {
        //     File::makeDirectory($storageAt, 0755, true, true);
        // }
        // $command = "" . env('DB_DUMP_PATH', 'mysqldump') . " --user=" . env('DB_USERNAME') . " --password=" . env('DB_PASSWORD') . " --host=" . env('DB_HOST') . " " . env('DB_DATABASE') . "  | gzip > " . $storageAt . $filename;
        // $returnVar = NULL;
        // $output = NULL;
        // exec($command, $output, $returnVar);
    }
}
