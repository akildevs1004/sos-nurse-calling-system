<?php

use App\Http\Controllers\Dashboards\SOSRoomsControllers;
use App\Http\Controllers\DeviceCameraModel2Controller;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DeviceStatusController;
use App\Http\Controllers\DeviceTemperatureSensorsController;
use App\Models\Device;
use App\Services\MqttService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/dashboard_rooms', [SOSRoomsControllers::class, 'dashboardRooms']);
Route::get('/dashboard_stats', [SOSRoomsControllers::class, 'dashboardStats']);
Route::post('/dashboard_alarm_response', [SOSRoomsControllers::class, 'updateResponseDatetime']);
Route::get('/sos_logs_reports', [SOSRoomsControllers::class, 'SosLogsReports']);


Route::get('/sos_logs_print_pdf', [SOSRoomsControllers::class, 'SosLogsPrintPdf']);
Route::get('/sos_logs_download_pdf', [SOSRoomsControllers::class, 'SosLogsDownloadPdf']);
Route::get('/sos_logs_export_excel', [SOSRoomsControllers::class, 'SosLogsDownloadCSV']);
Route::get('/sos_analytics_pdf', [SOSRoomsControllers::class, 'SosLogsAnalyticsPdf'])->name("sos.report.pdf");


//monitor

Route::get('/sos_monitor_statistics', [SOSRoomsControllers::class, 'SOSMonitorStatistics']);
Route::get('/sos_hourly_report', [SOSRoomsControllers::class, 'sosHourlyReport']);

Route::get('/sos_room_types', [SOSRoomsControllers::class, 'roomTypes']);

Route::get('/sos_room_percentage_roomtypes', [SOSRoomsControllers::class, 'roomTypesPercentages']);


//chart


//step1

Route::get('/sos/report/chart-render', [SOSRoomsControllers::class, 'chartRender'])
    ->name('geenratecharts');

Route::post('/sos/report/store-chart', [SOSRoomsControllers::class, 'storeChart'])
    ->name('sos.report.storeChart');


//tv cmds
Route::get('/tv_reload',  function () {

    return (new MqttService())->publish(
        "tv/reload",
        "{}",
        null
    );
});



// Route::post('/update_sos_devices_to_deviceConfig', [DeviceController::class, 'updateSOSDevicesToDeviceConfig']);
