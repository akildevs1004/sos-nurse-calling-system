<?php

use App\Http\Controllers\Alarm\Api\ApiAlarmControlController;
use App\Http\Controllers\Alarm\Api\ApiNurseCallingControlController;
use App\Http\Controllers\Alarm\DeviceSensorLogsController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AnnouncementsCategoriesController;
use App\Models\Alarm\DeviceSensorLogs;
use App\Models\NurseCallingLogs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// announcement
//Route::apiResource('announcement', AnnouncementController::class);
// Route::get('announcement_list', [AnnouncementController::class, 'annoucement_list']);
// Route::get('announcement/search/{key}', [AnnouncementController::class, 'search']);
Route::get('alarm_device_status', [ApiAlarmControlController::class, 'LogDeviceStatus']);
Route::post('alarm_device_status', [ApiAlarmControlController::class, 'LogDeviceStatus']);
Route::post('nursecalling_device_status', [ApiNurseCallingControlController::class, 'ApiLogDeviceStatus']);

Route::get('/getNurseDeviceLogs', function (Request $request) {


    return NurseCallingLogs::orderBy("log_time", "DESC")->orderBy("log_time", "DESC")->take(100)->get();;;
});



//Route::get('announcement/employee/{id}', [AnnouncementController::class, 'getAnnouncement']);

Route::get('alarm_dashboard_get_temparature_latest', [DeviceSensorLogsController::class, 'getDeviceLatestTemperature']);

Route::get('alarm_dashboard_get_hourly_data', [DeviceSensorLogsController::class, 'getDeviceTodayHourlyTemperature']);
Route::get('alarm_dashboard_get_humidity_hourly_data', [DeviceSensorLogsController::class, 'getDeviceTodayHourlyHumidity']);
Route::get('alarm_dashboard_get_humidity_temperature_hourly_data', [DeviceSensorLogsController::class, 'getDeviceTodayHourlyHumidityTemperature']);
Route::get('alarm_dashboard_get_humidity_temperature_monthly_data', [DeviceSensorLogsController::class, 'getDeviceMonthlyHumidityTemperature']);


Route::get('alarm_device_logs', [DeviceSensorLogsController::class, 'getDeliveLogs']);
Route::get('alarm_alarm_logs', [DeviceSensorLogsController::class, 'getAlarmLogs']);

Route::get('alarm_temperature_logs', [DeviceSensorLogsController::class, 'getTemperatureLogs']);



Route::get('delete_alarm_device_logs', [DeviceSensorLogsController::class, 'deleteOneMonthOldLogs']);
