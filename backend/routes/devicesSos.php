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



// Route::post('/update_sos_devices_to_deviceConfig', [DeviceController::class, 'updateSOSDevicesToDeviceConfig']);
