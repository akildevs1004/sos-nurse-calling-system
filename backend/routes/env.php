<?php

use App\Http\Controllers\SecurityLoginController;
use App\Http\Controllers\Dashboards\SOSRoomsControllers;
use App\Http\Controllers\DeviceCameraModel2Controller;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DeviceStatusController;
use App\Http\Controllers\DeviceTemperatureSensorsController;
use App\Http\Controllers\SecuritySosRoomsController;
use App\Models\Device;
use App\Services\MqttService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SecuritySosRoomsListController;


//tv cmds
Route::get('/envsettings',  function () {

    return [
        "MQTT_SOCKET_HOST" => "mqtt://" . (new SOSRoomsControllers())->getServerIp() . ":8083",
        "MQTT_DEVICE_CLIENTID" => "xtremesos",
        "TV_COMPANY_ID" => "8",
        "BACKEND_URL2" => "http://" . (new SOSRoomsControllers())->getServerIp() . ":8000",
    ];
});
