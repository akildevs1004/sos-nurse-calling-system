<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;


use Illuminate\Support\Facades\File;

class DeleteOldLogFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:files-delete-old-log-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete .log and .txt files older than 7 days from the specified path';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $path = storage_path() . "/app"; //"/mytime2cloud/backend/storage";


        $this->deleteAttendanceLogFiles($path);

        $path = storage_path() . "/kernal_logs"; //"/mytime2cloud/backend/storage";
        $this->deleteAttendanceLogFiles($path);

        $path = storage_path() . "/temp"; //"/mytime2cloud/backend/storage";
        $this->deleteAttendanceLogFiles($path);

        $path = storage_path() . "/logs"; //"/mytime2cloud/backend/storage";
        $this->deleteAttendanceLogFiles($path);

        $path = storage_path() . "/app/logs"; //"/mytime2cloud/backend/storage";
        $this->deleteAttendanceLogFiles($path);


        $path = "../arduino-sdk"; // var/www/mytime2cloud/arduino-sdk
        $this->deleteAttendanceLogFiles($path);

        $path = "../mqtt-logs"; // var/www/mytime2cloud/mqtt-logs
        $this->deleteAttendanceLogFiles($path);
    }

    public function deleteAttendanceLogFiles($path)
    {
        //$path = storage_path() . "/app"; //"/mytime2cloud/backend/storage";

        if (!File::exists($path)) {
            echo "The specified path does not exist.";
            return 1;
        }

        //$files = File::files($path);
        $files = File::allFiles($path);

        echo $path . " - Files count - " . count($files);

        $now = time();
        $days30 = 7 * 24 * 60 * 60; //30Days days



        foreach ($files as $file) {
            $extension = $file->getExtension();
            if (in_array($extension, ['log', 'logs', 'txt', '.zip']) && ($now - $file->getMTime() >= $days30)) {
                File::delete($file);


                $this->info("Deleted: {$file->getFilename()}");
            }
        }

        $this->info('Old files deletion process completed.');
        return 0;
    }
}
