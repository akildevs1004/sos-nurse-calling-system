<?php

use App\Http\Controllers\Dashboards\SOSRoomsControllers;
use App\Http\Controllers\DeviceCameraModel2Controller;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DeviceStatusController;
use App\Http\Controllers\DeviceTemperatureSensorsController;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/dashboard_rooms', [SOSRoomsControllers::class, 'dashboardRooms']);
Route::get('/dashboard_stats', [SOSRoomsControllers::class, 'dashboardStats']);
Route::post('/dashboard_alarm_response', [SOSRoomsControllers::class, 'updateResponseDatetime']);
Route::get('/sos_logs_reports', [SOSRoomsControllers::class, 'SosLogsReports']);


Route::get('/sos_logs_print_pdf', [SOSRoomsControllers::class, 'SosLogsPrintPdf']);
Route::get('/sos_logs_download_pdf', [SOSRoomsControllers::class, 'SosLogsDownloadPdf']);
Route::get('/sos_logs_export_excel', [SOSRoomsControllers::class, 'SosLogsDownloadCSV']);

//monitor

Route::get('/sos_monitor_statistics', [SOSRoomsControllers::class, 'SOSMonitorStatistics']);
Route::get('/sos_hourly_report', [SOSRoomsControllers::class, 'sosHourlyReport']);





// Route::post('/update_sos_devices_to_deviceConfig', [DeviceController::class, 'updateSOSDevicesToDeviceConfig']);
