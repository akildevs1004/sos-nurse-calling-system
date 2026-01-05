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



        // try {
        //     $schedule
        //         ->command('task:alarm_update_company_ids')
        //         // ->everyThirtyMinutes()
        //         ->everyMinute()
        //         //->withoutOverlapping()
        //         ->appendOutputTo(storage_path("logs/alarm_update_company_ids-$monthYear-logs.log")); //->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));
        // } catch (\Exception $e) {
        // }
        // try {
        //     $schedule
        //         ->command('task:device_sample_data') //dummy data
        //         // ->everyThirtyMinutes()
        //         ->everyMinute();
        //     //->withoutOverlapping()
        //     // ->appendOutputTo(storage_path("logs/sampledata-$monthYear-logs.log")); //->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));
        // } catch (\Exception $e) {
        // }
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
        // try {
        //     $schedule
        //         ->command('task:db_backup')
        //         ->dailyAt('6:00')
        //         ->appendOutputTo(storage_path("logs/db_backup.log"))
        //         ->emailOutputOnFailure(env("ADMIN_MAIL_RECEIVERS"));
        // } catch (\Exception $e) {
        // }

        // $schedule
        //     ->command("task:files-delete-old-log-files")
        //     ->dailyAt('10:30');
        //     //->withoutOverlapping()
        //     //->appendOutputTo(storage_path("kernal_logs/$monthYear-delete-old-logs.log"))

        // ;;

        // $schedule->command('mqtt:subscribe')
        //     // ->hourly() // Runs once per hour instead of every minute
        //     // ->dailyAt('10:30')
        //     ->everyMinute()
        //     ->runInBackground()
        //     ->withoutOverlapping()
        //     ->appendOutputTo(storage_path("logs/mqtt-kernal-$monthYear-logs.log"));
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
