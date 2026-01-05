<?php

namespace App\Console;

use App\Models\AccessControlTimeSlot;
use App\Models\Company;
use App\Models\DeviceActivesettings;
use App\Models\PayrollSetting;
use App\Models\ReportNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $monthYear = date("d-m-Y");


        try {
            $schedule
                ->command('task:delete_old_logs')
                // ->everyThirtyMinutes()
                ->everyFourHours();

            // ->appendOutputTo(storage_path("logs/fire-alarm-deleted-$monthYear-logs.log")); //->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));
        } catch (\Exception $e) {
        }
        try {
            $schedule
                ->command('task:check_device_health')
                // ->everyThirtyMinutes()
                ->everyMinute()

                ->appendOutputTo(storage_path("logs/device-offline-$monthYear-logs.log")); //->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));


        } catch (\Exception $e) {
        }
        try {
            $schedule
                ->command('task:db_backup')
                ->dailyAt('6:00')
                ->appendOutputTo(storage_path("logs/db_backup.log"));
        } catch (\Exception $e) {
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
