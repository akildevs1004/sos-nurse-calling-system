<?php

namespace App\Services;

use App\Http\Controllers\Alarm\Api\ApiAlarmControlController;
use App\Http\Controllers\Dashboards\SOSRoomsControllers;
use App\Http\Controllers\DeviceController;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;
use Illuminate\Support\Facades\Log;
use App\Models\Device;
use App\Models\DeviceSosRoomLogs;
use App\Models\DeviceSosRooms;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MqttService
{
    protected $mqtt;
    protected $clientId;
    protected $mqttDeviceClientId;

    public function __construct()
    {
        $host = env('MQTT_HOST');
        $port = env('MQTT_PORT', 1883);
        $this->clientId = 'laravel-client-' . uniqid(); //env('MQTT_CLIENT_ID', 'laravel-client-' . uniqid());
        $this->mqttDeviceClientId = env('MQTT_DEVICE_CLIENTID');

        Log::info("MQTT Initialied " . $this->clientId);

        $connectionSettings = new ConnectionSettings();

        $this->mqtt = new MqttClient($host, $port, $this->clientId);
        $this->mqtt->connect($connectionSettings, true);
    }

    /**
     * Publish a message to a topic.
     */
    public function publish($topic, $message, $serial_number)
    {


        $clientId = 'laravel-client-' . uniqid(); //env('MQTT_CLIENT_ID', 'laravel-client-' . uniqid());
        $host =  env('MQTT_HOST'); //env('MQTT_HOST', '165.22.222.17');
        $port = env('MQTT_PORT', 1883);

        $mqtt = new MqttClient($host, $port, $clientId);
        $mqtt->connect(new ConnectionSettings(), true);

        echo   $topic;

        $mqtt->publish($topic, $message, 0, false);
        $mqtt->disconnect();
    }

    /**
     * Subscribe and listen for MQTT heartbeat and config messages.
     */
    public function subscribeAndListen()
    {
        while (true) {
            // echo "heartbeat";
            try {
                $this->mqtt->subscribe($this->mqttDeviceClientId . '/+/sosalarm', function ($topic, $message) {

                    $logDir = base_path('../../logs/sos-nurse-calling-system/mqtt-sosalarm-logs');
                    if (!File::exists($logDir)) {
                        File::makeDirectory($logDir, 0755, true);
                    }
                    $logPath = $logDir . '/' . date("Y-m-d") . '.log';

                    try {
                        echo "\n";
                        $serialNumber = $this->extractSerial($topic);
                        echo $serialNumber . "-sosalarm New Alarm Received\n";
                        File::prepend($logPath, "------------------------------------------------------\n");
                        File::prepend($logPath, "[" . now() . "] Data: " . $message . "\n");

                        $json = json_decode($message, true);

                        if (!is_array($json)) {
                            echo $serialNumber . "[" . now() . "] ERROR: invalid JSON payload\n";

                            File::prepend($logPath, "[" . now() . "] ERROR: invalid JSON payload\n");
                            return;
                        }

                        if (($json['type'] ?? null) !== 'sos' || $serialNumber === '') {
                            echo $serialNumber . "[" . now() . "] ERROR: Type is Not SOS " . $json['type'] . "\n";

                            File::prepend($logPath, "[" . now() . "] ERROR: Type is Not SOS " . $json['type'] . "\n");
                            return;
                        }

                        $device = Device::where("serial_number", $serialNumber)->first();
                        if (!$device) {
                            echo $serialNumber . "[" . now() . "] ERROR: Device not found for $serialNumber\n";

                            File::prepend($logPath, "[" . now() . "] ERROR: Device not found for $serialNumber\n");
                            return;
                        }

                        $now = now()->setTimezone($device->utc_time_zone)->format('Y-m-d H:i:s');

                        // Payload fields
                        $name   = isset($json['name']) ? trim((string)$json['name']) : null;
                        $roomId = isset($json['roomId']) ? (int)$json['roomId'] : null;

                        $status = isset($json['status']) ? strtoupper(trim((string)$json['status'])) : null;
                        $code   = isset($json['code']) ? (string)$json['code'] : null;

                        File::prepend($logPath, "[" . now() . "] SOS alarm received from $serialNumber " . $roomId . " " . $name . "\n");

                        // Validate minimum required fields
                        if (!$roomId || !$status || $code === null || $code === '') {
                            File::prepend($logPath, "[" . now() . "] ERROR: missing fields roomId/status/code\n");
                            return;
                        }

                        // Accept ON / OFF only
                        if (!in_array($status, ['ON', 'OFF'], true)) {
                            File::prepend($logPath, "[" . now() . "] ERROR: invalid status=$status\n");
                            return;
                        }

                        $deviceSosRoom = DeviceSosRooms::where("serial_number", $serialNumber)
                            ->where("room_id", $roomId)
                            ->first();

                        if (!$deviceSosRoom) {
                            File::prepend($logPath, "[" . now() . "] WARN: SOS room not found serial=$serialNumber room_id=$roomId\n");
                            return;
                        }

                        echo "\n SOS Status : " . $status;
                        if ($status === "ON") {

                            // $now = now()->format("Y-m-d H:i:s");

                            $openLog = DeviceSosRoomLogs::where("serial_number", $serialNumber)
                                ->where("room_id", $roomId)
                                ->where("sos_status", true)                 // FIX: correct column
                                ->whereNull("alarm_end_datetime")           // RECOMMENDED: only open alarms
                                ->orderBy("alarm_start_datetime", "desc")
                                ->first();

                            if (!$openLog) {
                                $data = [
                                    "company_id"               => $device->company_id,
                                    "device_id"                => $device->id,
                                    "device_sos_room_table_id" => $deviceSosRoom->id,
                                    "room_id"                  => $roomId,
                                    "room_name"                => $name,
                                    "serial_number"            => $serialNumber,

                                    "sos_status"               => true,
                                    "on_code"                  => $code,

                                    "created_datetime"         => $now,
                                    "alarm_start_datetime"     => $now,
                                ];

                                $alarmId = DeviceSosRoomLogs::create($data);

                                $deviceSosRoom->update([
                                    'alarm_status'     => true,
                                    'updated_at' => now(),
                                ]);

                                File::prepend($logPath, "[" . now() . "] Info: SOS Alarm Is created $alarmId serial=$serialNumber room_id=$roomId\n");
                            } else {
                                File::prepend($logPath, "[" . now() . "] Info: SOS Alarm Is Already  created $openLog->id serial=$serialNumber room_id=$roomId\n");
                            }
                        } elseif ($status === "OFF") {


                            echo "\n SOS OFF  " . $deviceSosRoom->id;

                            // $now = now()->format("Y-m-d H:i:s");

                            // Find the latest OPEN alarm (ON) for this room
                            $openLog = DeviceSosRoomLogs::where("serial_number", $serialNumber)
                                ->where("room_id", $roomId)
                                ->where("sos_status", true)                 // FIX: correct column
                                ->whereNull("alarm_end_datetime")           // RECOMMENDED: only open alarms
                                ->orderBy("alarm_start_datetime", "desc")
                                ->get();

                            foreach ($openLog as $key => $log) {
                                # code...


                                $response_in_minutes = 0;

                                if ($log) {
                                    $response_in_minutes = Carbon::parse($log->alarm_start_datetime)
                                        ->diffInMinutes($now);

                                    DeviceSosRoomLogs::where("id", $log->id)->update([                          // FIX: update the instance
                                        "sos_status"          => false,
                                        "off_code"            => $code,
                                        "alarm_end_datetime"  => $now,
                                        "response_in_minutes"  => $response_in_minutes,

                                    ]);

                                    File::prepend($logPath, "[" . now() . "] Info: SOS Alarm Is Updated  $log->id serial=$serialNumber room_id=$roomId\n");
                                }
                            }


                            echo "\nOFF " . $deviceSosRoom->id;
                            echo  $deviceSosRoom->update([
                                'alarm_status'     => false,
                                'updated_at' => now(),
                            ]);
                        }
                    } catch (\Throwable $e) {
                        echo $e->getMessage();
                        File::prepend($logPath, "[" . now() . "] EXCEPTION: " . $e->getMessage() . "\n");
                    }
                });
                $this->mqtt->subscribe($this->mqttDeviceClientId . '/+/heartbeat', function ($topic, $message) {




                    try {

                        $serialNumber = $this->extractSerial($topic);
                        echo $serialNumber . "-heartbeat\n";
                        // Log::info($message);

                        echo "\n";


                        Device::where("serial_number", $serialNumber)->update([
                            'status_id' => 1,
                            'last_live_datetime' => date("Y-m-d H:i:s"),
                        ]);
                        $logPath = base_path('../../logs/sos-nurse-calling-system/mqtt-heartbeat-logs/');
                        if (!File::exists($logPath)) {
                            File::makeDirectory($logPath, 0755, true);
                        }
                        $logPath = $logPath . '/' . date("Y-m-d") . '.log';
                        File::prepend($logPath, "[" . now() . "] " . $message . "\n");
                    } catch (\Throwable $e) {

                        echo "ERROR in heartbeat\n" . $e->getMessage();


                        $logPath = base_path('../../logs/sos-nurse-calling-system/mqtt-heartbeat-logs/');
                        if (!File::exists($logPath)) {
                            File::makeDirectory($logPath, 0755, true);
                        }
                        $logPath = $logPath . '/' . date("Y-m-d") . '.log';
                        File::prepend($logPath, "[" . now() . "] ❌ MQTT heartbeat Exception: " . $e->getMessage() . "\n");
                    }
                });
                //---------------------------CONFIG -------------------------------------------------------
                $this->mqtt->subscribe($this->mqttDeviceClientId . '/+/config', function ($topic, $message) {
                    $logPath = base_path('../../logs/sos-nurse-calling-system/mqtt-config-logs/');
                    if (!File::exists($logPath)) {
                        File::makeDirectory($logPath, 0755, true);
                    }

                    try {

                        echo "\n";
                        $serialNumber = $this->extractSerial($topic);
                        echo $serialNumber . "-config\n";

                        $logDir = base_path('../../logs/sos-nurse-calling-system/mqtt-config-logs');

                        if (!File::exists($logDir)) {
                            File::makeDirectory($logDir, 0755, true);
                        }
                        $logPath = $logDir . '/' . date("Y-m-d") . '.log';

                        File::prepend($logPath, "[" . now() . "] Data :" . $message . "\n");

                        $json = json_decode($message, true);

                        if (!isset($json['config']) || $serialNumber === '') {
                            return;
                        }

                        $device = Device::where("serial_number", $serialNumber)->first();
                        if (!$device) {
                            File::prepend($logPath, "[" . now() . "] ERROR: Device not found for $serialNumber\n");
                            return;
                        }

                        echo "Config is Available\n";
                        File::prepend($logPath, "[" . now() . "] Config received from $serialNumber\n");

                        // config may arrive as JSON string or array
                        $config = is_array($json['config'])
                            ? $json['config']
                            : json_decode((string)$json['config'], true);

                        if (!is_array($config)) {
                            File::prepend($logPath, "[" . now() . "] ERROR: config JSON decode failed for $serialNumber\n");
                            return;
                        }

                        // Update device IP
                        $ethIp  = $config['eth_ip']  ?? null;
                        $wifiIp = $config['wifi_ip'] ?? null;
                        $ipAddress = $ethIp ?: $wifiIp;

                        if ($ipAddress) {
                            $device->update(["wifiipaddress" => $ipAddress]);
                        }

                        $sosDevices = $config['sos_devices'] ?? [];
                        if (!is_array($sosDevices)) $sosDevices = [];

                        DB::transaction(function () use ($serialNumber, $sosDevices, $device) {

                            $rows = [];
                            $incomingDeviceRoomIds = [];

                            echo "\n SOS Devices Count:" . count($sosDevices);

                            foreach ($sosDevices as $d) {


                                if (!is_array($d)) continue;

                                echo "\n SOS Devices Count: " . count($d);


                                // IMPORTANT: use config "id" as stable device room identifier
                                $deviceRoomId = isset($d['roomId']) ? (int)$d['roomId'] : null;

                                echo "\n SOS Devices Room Id: " . ($deviceRoomId);
                                if (!$deviceRoomId) continue;

                                $incomingDeviceRoomIds[] = $deviceRoomId;

                                $rows[] = [
                                    'device_id'       => $device->id,
                                    'serial_number'   => $serialNumber,
                                    'company_id'   =>  $device->company_id,
                                    'room_type'   =>   $d['roomType']  ?? null,

                                    'room_id'         => isset($d['roomId']) ? (int)$d['roomId'] : null,
                                    'name'            => isset($d['name']) ? trim((string)$d['name']) : null,
                                    'on_code'         => $d['onCode']  ?? null,
                                    'off_code'        => $d['offCode'] ?? null,
                                    'status'          => true,                       // present => active
                                    'updated_at'      => now(),
                                    'created_at'      => now(),
                                ];
                            }


                            echo "\n SOS Devices Update Rooms Count: " . count($rows);
                            // Upsert present rooms
                            if (!empty($rows)) {
                                DB::table('device_sos_rooms')->upsert(
                                    $rows,
                                    ['device_id', 'room_id'],   // UNIQUE KEY
                                    ['serial_number', 'room_id', 'name', 'on_code', 'off_code', 'status', 'updated_at', 'room_type',]
                                );
                            }

                            // Deactivate missing rooms (soft delete via status=false)
                            DB::table('device_sos_rooms')
                                ->where('device_id', $device->id)
                                ->when(count($incomingDeviceRoomIds) > 0, function ($q) use ($incomingDeviceRoomIds) {
                                    $q->whereNotIn('room_id', $incomingDeviceRoomIds);
                                })
                                ->delete();
                        });
                    } catch (\Throwable $e) {
                        // Always log exceptions in MQTT consumers
                        $logDir = base_path('../../logs/sos-nurse-calling-system/mqtt-config-logs');
                        if (!File::exists($logDir)) {
                            File::makeDirectory($logDir, 0755, true);
                        }
                        $logPath = $logDir . '/' . date("Y-m-d") . '.log';
                        File::prepend($logPath, "[" . now() . "] EXCEPTION: " . $e->getMessage() . "\n");
                    }
                });


                // Listen to all companies
                $this->mqtt->subscribe('tv/+/dashboard/request', function (string $topic, string $message) {


                    // echo "MQTT Request Message: " . $message;
                    try {
                        $payload = json_decode($message, true, 512, JSON_THROW_ON_ERROR);

                        $parts = explode('/', $topic); // tv/{companyId}/dashboard/request
                        $companyId = (int)($parts[1] ?? 0);
                        if ($companyId <= 0) return;

                        $reqId = (string)($payload['reqId'] ?? '');
                        $params = (array)($payload['params'] ?? []);

                        // Force company id from topic (cannot be spoofed)
                        $params['company_id'] = $companyId;

                        // Call your existing controller methods directly (NO HTTP)
                        $controller = app(SOSRoomsControllers::class);

                        $roomsRes = $controller->dashboardRooms(new Request($params));
                        $statsRes = $controller->dashboardStats(new Request($params));

                        // Unwrap JsonResponse cleanly
                        $roomsData = method_exists($roomsRes, 'getData') ? $roomsRes->getData(true) : $roomsRes;
                        $statsData = method_exists($statsRes, 'getData') ? $statsRes->getData(true) : $statsRes;

                        // Publish responses
                        $this->mqtt->publish(
                            "tv/{$companyId}/dashboard/rooms",
                            json_encode(['reqId' => $reqId, 'data' => $roomsData], JSON_UNESCAPED_SLASHES),
                            0,
                            false
                        );

                        $this->mqtt->publish(
                            "tv/{$companyId}/dashboard/stats",
                            json_encode(['reqId' => $reqId, 'data' => $statsData], JSON_UNESCAPED_SLASHES),
                            0,
                            false
                        );
                    } catch (\Throwable $e) {
                        Log::error('TV MQTT responder error', [
                            'topic' => $topic,
                            'message' => $message,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }, 0);


                $this->mqtt->subscribe('tv/+/device-list/get', function (string $topic, string $message) {


                    echo $message;

                    $payload = json_decode($message, true);

                    $companyId = $payload['company_id'] ?? null;
                    $requestId = $payload['requestId'] ?? null;

                    echo "\n Device List Request from Company ID: " . $companyId;

                    // Basic validation
                    if (!$companyId || !$requestId) {
                        return;
                    }

                    $respTopic = "tv/{$companyId}/device-list/resp";


                    try {

                        $devices = (new DeviceController())->dropdownList((new Request(['company_id' => $companyId])));
                        echo "\n Count" . count($devices);
                        $resp = [
                            'requestId' => $requestId,
                            'ok'        => true,
                            'data'      => $devices, // collection will JSON serialize
                            'ts'        => now()->timestamp,
                        ];

                        $this->mqtt->publish($respTopic, json_encode($resp), 0, false);
                    } catch (\Throwable $e) {

                        echo "\n Error: " . $e->getMessage();
                        $resp = [
                            'requestId' => $requestId,
                            'ok'        => false,
                            'message'   => $e->getMessage(),
                            'ts'        => now()->timestamp,
                        ];

                        $this->mqtt->publish($respTopic, json_encode($resp), 0, false);
                    }
                }, 1);


                // Listen to all companies
                $this->mqtt->subscribe('tv/+/dashboard_alarm_response', function (string $topic, string $message) {


                    try {
                        $payload = json_decode($message, true, 512, JSON_THROW_ON_ERROR);



                        $parts = explode('/', $topic); // tv/{companyId}/dashboard/request
                        $companyId = (int)($parts[1] ?? 0);
                        if ($companyId <= 0) return;

                        // $alarmId = (string)($payload['alarmId'] ?? '');
                        $params = (array)($payload['params'] ?? []);

                        // Force company id from topic (cannot be spoofed)
                        $params['company_id'] = $companyId;
                        $params['alarmId'] = $params['alarmId'];


                        // Call your existing controller methods directly (NO HTTP)
                        $controller = app(SOSRoomsControllers::class);

                        $roomsRes = $controller->updateResponseDatetime(new Request($params));
                    } catch (\Throwable $e) {

                        echo $e->getMessage();
                        Log::error('TV MQTT responder error', [
                            'topic' => $topic,
                            'message' => $message,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }, 0);

                $this->mqtt->subscribe('tv/auth/req', function (string $topic, string $message) {


                    echo $message;

                    //echo $topic;


                    try {

                        // $message = "";
                        $payload = json_decode($message, true, 512, JSON_THROW_ON_ERROR);

                        echo $payload["credentials"]["email"];

                        $user = User::where('email', $payload["credentials"]["email"])
                            ->with("company:id,user_id,name,location,logo,company_code,expiry")
                            ->select()
                            ->first();


                        if (!$user || !Hash::check($payload["credentials"]["password"], $user->password)) {

                            $message = "Invalid Login Details";
                        } else {
                            // unset($user->company);
                            // unset($user->employee);
                            // unset($user->assigned_permissions);

                            if ($user->user_type == "security") {
                                $user->load("security");
                                $user->load("security.sosRooms");
                            }

                            $data = [
                                'status' => true,
                                'token' => $user->createToken('myApp')->plainTextToken,
                                'user' => $user
                            ];

                            $this->mqtt->publish(
                                "tv/auth/resp/" . $payload["correlationId"],
                                json_encode(['data' => $data], JSON_UNESCAPED_SLASHES),
                                0,
                                false
                            );
                        }







                        // $parts = explode('/', $topic); // tv/{companyId}/dashboard/request
                        // $companyId = (int)($parts[1] ?? 0);
                        // if ($companyId <= 0) return;

                        // // $alarmId = (string)($payload['alarmId'] ?? '');
                        // $params = (array)($payload['params'] ?? []);

                        // // Force company id from topic (cannot be spoofed)
                        // $params['company_id'] = $companyId;
                        // $params['alarmId'] = $params['alarmId'];


                        // // Call your existing controller methods directly (NO HTTP)
                        // $controller = app(SOSRoomsControllers::class);

                        // $roomsRes = $controller->updateResponseDatetime(new Request($params));
                    } catch (\Throwable $e) {

                        echo $e->getMessage();
                        Log::error('TV MQTT responder error', [
                            'topic' => $topic,
                            'message' => $message,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }, 0);







                $this->mqtt->loop(true); // Blocking loop
            } catch (\Throwable $e) {


                sleep(5); // Wait and retry
                $this->reconnect();
            }
        } //loop
    }

    public function createUpdateNewAlarm() {}

    protected function reconnect()
    {
        $host = env('MQTT_HOST', '165.22.222.17');
        $port = env('MQTT_PORT', 1883);
        $this->clientId = env('MQTT_CLIENT_ID', 'laravel-client-' . uniqid());

        $this->mqtt = new MqttClient($host, $port, $this->clientId);
        $this->mqtt->connect(new ConnectionSettings(), true);
    }


    /**
     * Extract serial number from MQTT topic.
     */
    protected function extractSerial($topic)
    {
        preg_match('#' . $this->mqttDeviceClientId . '/([^/]+)/#', $topic, $matches);
        return $matches[1] ?? null;
    }
}
