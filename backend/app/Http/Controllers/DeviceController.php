<?php

namespace App\Http\Controllers;

use App\Http\Requests\Device\StoreRequest;
use App\Http\Requests\Device\UpdateRequest;
use App\Mail\EmailNotificationForOfflineDevices;
use App\Mail\SendEmailNotificationForOfflineDevices;
use App\Models\Alarm\DeviceSensorLogs;
use App\Models\AlarmLogs;
use App\Models\AttendanceLog;
use App\Models\Company;
use App\Models\Device;
use App\Models\DeviceActivesettings;
use App\Models\DeviceNotification;
use App\Models\DeviceNotificationsLog;
use App\Models\DevicesActiveWeeklySettings;
use App\Models\DeviceTemperatureSensors;
use App\Models\Employee;
use App\Services\MqttService;
use DateTime;
use DateTimeZone;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class DeviceController extends Controller
{
    const ONLINE_STATUS_ID = 1;
    const OFFLINE_STATUS_ID = 2;

    public function dropdownList()
    {
        $model = Device::query();
        $model->where('company_id', request('company_id'));
        $model->where("device_type", "!=", "Manual");
        $model->where("device_id", "!=", "Manual");
        $model->Where("device_id",  'not like', "%Mobile%");
        $model->when(request()->filled('branch_id'), fn($q) => $q->where('branch_id', request('branch_id')));
        $model->orderBy(request('order_by') ?? "name", request('sort_by_desc') ? "desc" : "asc");
        return $model->get(["id", "name", "location", "device_id", "device_type", "serial_number", "last_live_datetime", "utc_time_zone"]);
    }

    // public function devicesDropdownListSensors()
    // {
    //     $model = Device::query();
    //     $model->where('company_id', request('company_id'));
    //     $model->where("device_type", "!=", "Manual");
    //     $model->where("device_id", "!=", "Manual");
    //     $model->Where("device_id",  'not like', "%Mobile%");
    //     $model->with("temperatureSensors");
    //     $model->when(request()->filled('branch_id'), fn($q) => $q->where('branch_id', request('branch_id')));
    //     $model->orderBy(request('order_by') ?? "name", request('sort_by_desc') ? "desc" : "asc");
    //     return $model->get(["id", "name", "location", "device_id", "device_type", "serial_number"]);
    // }

    public function devicesDropdownListSensors()
    {
        $devices = Device::query()
            ->where('company_id', request('company_id'))
            ->where('device_type', '!=', 'Manual')
            ->where('device_id', '!=', 'Manual')
            ->where('device_id', 'not like', '%Mobile%')

            ->with('temperatureSensors')


            ->when(request()->filled('branch_id'), fn($q) => $q->where('branch_id', request('branch_id')))
            ->orderBy(request('order_by', 'name'), request('sort_by_desc') ? 'desc' : 'asc')
            ->get(['id', 'name', 'location', 'device_id', 'device_type', 'serial_number', 'last_live_datetime', 'utc_time_zone']);

        $result = [];

        foreach ($devices as $device) {
            if ($device->temperatureSensors->isNotEmpty()) {
                foreach ($device->temperatureSensors as $sensor) {
                    $result[] = [
                        'device_id'         => $device->id,
                        'name'       => $device->name,
                        'location'          => $device->location,
                        'device_identifier' => $device->device_id,
                        'last_live_datetime' => $device->last_live_datetime,
                        'utc_time_zone' => $device->utc_time_zone,
                        'device_type'       => $device->device_type,
                        'serial_number'     => $device->serial_number,
                        'temperature_sensor_id'         => $sensor->id,
                        'temperature_sensor_name'       => $sensor->name,
                        'temperature_serial_address'       => $sensor->temperature_serial_address,
                    ];
                }
            } else {
                $result[] = [
                    'device_id'         => $device->id,
                    'name'       => $device->name,
                    'location'          => $device->location,
                    'device_identifier' => $device->device_id,
                    'last_live_datetime' => $device->last_live_datetime,
                    'utc_time_zone' => $device->utc_time_zone,
                    'device_type'       => $device->device_type,
                    'serial_number'     => $device->serial_number,
                    'temperature_sensor_id'         => null,
                    'temperature_sensor_name'       => null,
                    'temperature_serial_address'       => null,
                ];
            }
        }

        return $result;
    }


    public function index(Request $request)
    {
        $model = Device::query();

        $model->excludeMobile();

        $cols = $request->cols;
        $model->with(['status', 'company.user', 'companyBranch']);
        if (!$request->filled("master")) {
            $model->where('company_id', $request->company_id);
        }

        $model->when($request->filled('name'), function ($q) use ($request) {
            $q->where('name', 'ILIKE', "$request->name%");
        });
        $model->when($request->filled('short_name'), function ($q) use ($request) {
            $q->where('short_name', 'ILIKE', "$request->short_name%");
        });
        $model->when($request->filled('location'), function ($q) use ($request) {
            $q->where('location', 'ILIKE', "$request->location%");
        });
        $model->when($request->filled('device_id'), function ($q) use ($request) {
            $q->where('device_id', 'ILIKE', "%$request->device_id%");
        });
        $model->when($request->filled('device_type'), function ($q) use ($request) {
            $q->where('device_type', 'ILIKE', "$request->device_type%");
        });
        $model->when($request->filled('Status'), function ($q) use ($request) {
            $q->where('status_id', $request->Status);
        });
        $model->when($request->filled('branch_id'), function ($q) use ($request) {
            $q->where('branch_id', $request->branch_id);
        });



        // array_push($cols, 'status.id');

        $model->when(isset($cols) && count($cols) > 0, function ($q) use ($cols) {
            $q->select($cols);
        });

        $model->when($request->filled('sortBy'), function ($q) use ($request) {
            $sortDesc = $request->input('sortDesc');
            if (strpos($request->sortBy, '.')) {
                // if ($request->sortBy == 'department.name.id') {
                //     $q->orderBy(Department::select("name")->whereColumn("departments.id", "employees.department_id"), $sortDesc == 'true' ? 'desc' : 'asc');

                // }

            } else {
                $q->orderBy($request->sortBy . "", $sortDesc == 'true' ? 'desc' : 'asc'); {
                }
            }
        });

        if (!$request->sortBy)
            $model->orderBy("name", "ASC");
        return $model->paginate($request->per_page ?? 1000);

        //return $model->with(['status', 'company'])->where('company_id', $request->company_id)->paginate($request->per_page ?? 1000);
    }

    public function getDeviceList(Device $model, Request $request)
    {
        return $model->with(['status'])->where('company_id', $request->company_id)->orderBy("name", "asc")->get();
    }
    public function getDeviceListNotManul(Device $model, Request $request)
    {

        // return $model->where('company_id', $request->company_id)->orderBy("name", "asc")->distinct("name")
        //     ->orderBy("name", "asc")->get();
        $model =   $model->with(['status'])->where('company_id', $request->company_id);
        $model->where("serial_number", "!=", "NULL");

        $model->where(function ($query) use ($request) {
            $query->where("model_number", "!=", "Manual");
            $query->Where("model_number",  'not like', "%Mobile%");
        });


        return  $model->orderBy("name", "asc")->get();
    }
    public function getDevicePersonDetails(Request $request)
    {
        if ($request->system_user_id > 0) {
            $deviceName = Device::where('device_id', $request->device_id)->pluck('name')[0];

            $responseData = (new SDKController())->getPersonDetails($request->device_id, $request->system_user_id);

            return ["SDKresponseData" => json_decode($responseData), "deviceName" => $deviceName, "device_id" => $request->device_id];
        } else {
            return ["SDKresponseData" => "", "message" => "Visitor Device id is not avaialble ", "deviceName" => false, "device_id" => $request->device_id];
        }
    }
    public function store(StoreRequest $request)
    {
        try {

            $company = Company::find($request->company_id);
            $maxDevices = $company->max_devices;

            $totalAvailable = Device::where("company_id", $request->company_id)
                ->where("model_number", "!=", "Manual")
                ->where("model_number",  'not like', "%Mobile%")
                ->count();

            if ($maxDevices - $totalAvailable <= 0 && $company->account_type == "company") {
                return $this->response('Device limit reached. Max Devices :' . $maxDevices, null, false);
            }

            $model = Device::query();
            $model->where("company_id", $request->company_id);
            $model->where("device_id", $request->device_id);
            $model->where("name", $request->name);

            if ($model->exists()) {
                return $this->response('Device already exist.', null, true);
            }

            $data = $request->validated();


            if ($request->isNotFilled("device_type")) {
                $data["device_type"] = "---";
            }


            $data["ip"] = "0.0.0.0";
            $data["port"] = "0000";
            $record = $model->create($data);
            $this->updateDevicesJson();
            if ($record) {
                return $this->response('Device successfully added.', $record, true);
            } else {
                return $this->response('Device cannot add.', null, 'device_api_error');
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function show(Device $model, $id)
    {
        return $model->with(['status', 'company'])->find($id);
    }
    public function getDeviceBySerialNumber(Device $model, $serialNumber)
    {
        return $model->where("serial_number", $serialNumber)->first();
    }
    public function getDeviceByUserId(Device $model, $id)
    {
        return $model->where("device_id", $id)->first();
    }

    public function getDeviceCompany(Request $request)
    {
        $device = DB::table("devices")->where("company_id", $request->company_id)->where("device_id", $request->SN)->first(['name as device_name', 'short_name', 'device_id', 'location', "company_id"]);
        $model = DB::table("employees")->where("company_id", $request->company_id)->where("system_user_id", $request->UserCode)->first(['first_name', 'display_name', 'profile_picture']);

        if ($model && $model->profile_picture) {
            $model->profile_picture = asset('media/employee/profile_picture/' . $model->profile_picture);
        }

        return [
            "UserID" => $request->UserCode,
            "time" => date("H:i", strtotime($request->RecordDate)),
            "employee" => $model,
            "device" => $device,
        ];
    }
    public function getLastRecordsHistory($id = 0, $count = 0, Request $request)
    {

        // return Employee::select("system_user_id")->where('company_id', $request->company_id)->get();

        $model = AttendanceLog::query();
        $model->with(array('employee' => function ($query) use ($request) {
            $query->where('company_id', $request->company_id);
        }))->first();

        $model->with(['device']);
        $model->where('company_id', $id);
        $model->when($request->filled("branch_id"), function ($q) use ($request) {
            $q->whereHas("employee", fn($q) => $q->where("branch_id", $request->branch_id));
        });
        $model->whereIn('UserID', function ($query) use ($request) {
            // $model1 = Employee::query();
            // $model1->select("system_user_id")->where('employees.company_id', $request->company_id);

            $query->select('system_user_id')->from('employees')->where('employees.company_id', $request->company_id);
        });
        $model->when($request->filled('system_user_id'), function ($q) use ($request) {

            $q->Where('UserID',   $request->system_user_id);
        });
        $model->when($request->filled('search_time'), function ($q) use ($request) {
            $key = date('Y-m-d') . ' ' . $request->search_time;
            $q->Where('LogTime', 'LIKE', "$key%");
        });
        $model->when($request->filled('search_device_id'), function ($q) use ($request) {
            $key = strtoupper($request->search_device_id);
            //$q->Where(DB::raw('lower(DeviceID)'), 'LIKE', "$key%");
            $q->Where('DeviceID', 'LIKE', "$key%");
        });

        if (!$request->sortBy) {

            $model->orderBy("LogTime", 'desc');
        }
        //$model->orderByDesc("LogTime");
        $logs = $model->paginate($request->per_page);

        return $logs;
    }
    public function updateDeviceCamVIISettingsToSDK(Request $request)
    {
        if ($request->deviceSettings['device_id'] > 0) {

            $device = Device::where("device_id", $request->deviceSettings['device_id'])->first();
            $responseData['data'] = (new DeviceCameraModel2Controller($device->camera_sdk_url))->updateSettings($request);


            return ["SDKresponseData" =>  $responseData, "message" => " Settings Updated Successfully", "device_id" => $request->deviceSettings['device_id'], "status" => true];
        } else {
            return ["SDKresponseData" => "", "message" => "  Device id is not avaialble ", "deviceName" => false, "status" => false, "device_id" => $request->device_id];
        }
    }
    public function getDevicecamviiSettingsFromSDK(Request $request)
    {

        if ($request->device_id > 0) {

            $device = Device::where("device_id", $request->device_id)->first();
            $responseData['data'] = (new DeviceCameraModel2Controller($device->camera_sdk_url))->getSettings($device);


            return ["SDKresponseData" =>  $responseData,  "device_id" => $request->device_id, "status" => true];
        } else {
            return ["SDKresponseData" => "", "message" => "  Device id is not avaialble ", "deviceName" => false, "status" => false, "device_id" => $request->device_id];
        }
    }
    public function updateDeviceAlarmToSDK(Request $request)
    {


        if ($request->filled('serial_number')) {

            $device_settings = [];




            $device = Device::where("serial_number", $request->serial_number)->first();
            if ($device->status_id == 2) {
                return $this->response("Device is offline. Please Check Device Online status.", null, false);
            }
            try {

                if ($device->device_category_name == 'CAMERA') {

                    if ($device->model_number == 'CAMERA1') {
                        //(new DeviceCameraController())->updateTimeZone();
                    }
                } else  if ($device->model_number == 'MEGVII') {
                } else {

                    $data = [
                        "IllegalVerificationAlarm" => false,
                        "PasswordAlarm" => false,
                        "DoorMagneticAlarm" => false,
                        "BlacklistAlarm" => false,
                        "FireAlarm" => true,
                        "OpenDoorTimeoutAlarm" => false,
                        "AntiDisassemblyAlarm" => false,
                    ];
                    if ($request->status == 0) {
                        (new SDKController)->processSDKRequestCloseAlarm($request->serial_number, $data);
                        //always open the door till close manually
                        $this->CallAlwaysOpenDoor($request->serial_number);

                        $data = ["alarm_status" => 0, "alarm_end_datetime" => date('Y-m-d H:i:s')];
                        Device::where("serial_number", $request->serial_number)->update($data);

                        $data = ["status" => 0, "device_id" => $request->serial_number, "log_time" => date('Y-m-d H:i:s')];
                        AlarmLogs::create($data);

                        return $this->response('Device Alarm OFF status Updated Successfully',  null, true);
                    }
                }
            } catch (\Exception $e) {
                return $this->response("Unkown Error. Please retry again after 1 min or contact   technical team", null, false);
            }




            return $this->response('Device settings are updated successfully.',  null, true);
        } else {
            return $this->response('Device ID is not found',  null, false);
        }
    }
    public function updateDeviceSettingsToSDK(Request $request)
    {


        if ($request->deviceSettings && $request->deviceSettings['device_id']) {

            $device_settings = [];

            $utc_time_zone  = Device::where('device_id', $request->deviceSettings['device_id'])->pluck("utc_time_zone")->first();;

            $dateObj  = new DateTime("now", new DateTimeZone($utc_time_zone));
            $currentDateTime = $dateObj->format('Y-m-d H:i:00');


            $device_settings = [
                // "name" => $request->deviceSettings['name'] ?? '',
                "name" =>  '',
                "door" => $request->deviceSettings['door'] ?? '1',


                "language" => $request->deviceSettings['language'] ?? '2',
                "volume" => $request->deviceSettings['volume'] ?? '5',
                "menuPassword" => $request->deviceSettings['menuPassword'] ?? '',
                "msgPush" => $request->deviceSettings['msgPush'] ?? '0',
                "time" => $currentDateTime,


            ];
            if (isset($request->deviceSettings['maker_manufacturer'])) {


                if ($request->deviceSettings['maker_manufacturer'] != '' &&  $request->deviceSettings['maker_webAddr'] != '' &&  $request->deviceSettings['maker_deliveryDate'] != '') {
                    $device_settings["maker"] = [
                        "manufacturer" => $request->deviceSettings['maker_manufacturer'] ?? '',
                        "webAddr" => $request->deviceSettings['maker_webAddr'] ?? '',
                        "deliveryDate" => $request->deviceSettings['maker_deliveryDate'] ?? ''
                    ];
                }
            }
            (new SDKController)->processSDKRequestSettingsUpdate($request->deviceSettings['device_id'], $device_settings);

            return $this->response('Device settings are updated successfully.',  null, true);
        } else {
            return $this->response('Device ID is not found',  null, false);
        }
    }
    public function getDeviceSettingsFromSDK(Request $request)
    {

        if ($request->device_id > 0) {


            $responseData = (new SDKController())->getDeviseSettingsDetails($request->device_id);

            $responseDataArray = json_decode($responseData, true);

            if (!isset($responseDataArray->data->maker['manufacturer'])) {
                $responseDataArray['data']['maker_manufacturer'] = "OXAI";
                $responseDataArray['data']['maker_webAddr']   = env("APP_URL");
                $responseDataArray['data']['maker_deliveryDate']  = date("Y-01-01");
            }
            if (!isset($responseDataArray['data']['name'])) {
                $responseDataArray['data']['name'] = "OXSAI866";
            }





            return ["SDKresponseData" =>  $responseDataArray,  "device_id" => $request->device_id, "status" => true];
        } else {
            return ["SDKresponseData" => "", "message" => "  Device id is not avaialble ", "deviceName" => false, "status" => false, "device_id" => $request->device_id];
        }
    }
    public function getLastRecordsByCount($id = 0, $count = 0, Request $request)
    {
        $model = AttendanceLog::query();
        $model->where('company_id', $id);
        $model->with(["employee" => function ($q) use ($id) {
            $q->where('company_id', $id);
            $q->withOut(['schedule', 'department', 'sub_department', 'designation', 'user', 'role']);
            $q->select('first_name', 'last_name', 'employee_id', 'system_user_id', 'display_name', 'profile_picture', 'company_id');
        }]);
        $model->take($count);
        $model->orderByDesc("id");
        return $model->get()->toArray();

        // Cache::forget("last-five-logs");
        return Cache::remember('last-five-logs', 300, function () use ($id, $count) {

            $model = AttendanceLog::query();
            $model->where('company_id', $id);
            $model->take($count);

            $logs = $model->get(["UserID", "LogTime", "DeviceID"]);

            $arr = [];

            foreach ($logs as $log) {

                $employee = Employee::withOut(['schedule', 'department', 'sub_department', 'designation', 'user', 'role'])
                    ->where('company_id', $id)
                    ->where('system_user_id', $log->UserID)
                    ->first(['first_name', 'profile_picture', 'company_id']);

                $dev = Device::where('device_id', $log->DeviceID)
                    ->first(['name as device_name', 'short_name', 'device_id', 'location']);

                if ($employee) {
                    $arr[] = [
                        "company_id" => $employee->company_id,
                        "UserID" => $log->UserID,
                        "time" => date("H:i", strtotime($log->LogTime)),
                        "device" => $dev,
                        "employee" => $employee,
                    ];
                }
            }

            return $arr;
        });
    }

    public function update(Device $Device, UpdateRequest $request)
    {


        try {
            $data = $request->all();
            $data = $request->validated();
            if ($request->isNotFilled("device_type")) {
                $data["device_type"] = "---";
            }

            $data["device_id"] = $data["serial_number"];


            $record = $Device->update($data);



            $this->updateDevicesJson();

            if ($record) {
                return $this->response('Device successfully updated.', $record, true);
            } else {
                return $this->response('Device cannot update.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function updateDevicesJson()
    {
        $devices = Device::where("device_category_name", "CAMERA")->get();

        $filePath = storage_path('app') . '' . '/devices_list.json';


        file_put_contents($filePath, json_encode($devices));
    }

    public function destroy(Device $device)
    {
        try {
            $record = $device->delete();

            if ($record) {
                return $this->response('Device successfully deleted.', $record, true);
            } else {
                return $this->response('Device cannot delete.', null, 'device_api_error');
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function search(Request $request, $key)
    {
        $model = Device::query();

        $fields = [
            'name',
            'device_id',
            'location',
            'short_name',
            'status' => ['name'],
            'company' => ['name'],
        ];

        $model = $this->process_search($model, $key, $fields);

        $model->with(['status', 'company']);

        return $model->paginate($request->per_page);
    }

    public function deleteSelected(Device $model, Request $request)
    {
        try {
            $record = $model->whereIn('id', $request->ids)->delete();

            if ($record) {
                return $this->response('Device successfully deleted.', $record, true);
            } else {
                return $this->response('Device cannot delete.', null, false);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function openDoor(Request $request)
    {


        $device = Device::where("device_id", $request->device_id)->first();
        if ($device->status_id == 2) {
            return $this->response("Device is offline. Please Check Device Online status.", null, false);
        }
        try {

            if ($device->device_category_name == 'CAMERA') {

                if ($device->model_number == 'CAMERA1') {
                    //(new DeviceCameraController())->updateTimeZone();
                }
            } else  if ($device->model_number == 'MEGVII') {
                (new DeviceCameraModel2Controller($device->camera_sdk_url))->openDoor($device);
                return $this->response('Open Door Command Successfull',  null, true);
            } else {

                $url = env('SDK_URL') . "/$request->device_id/OpenDoor";
                $response = $this->callCURL($url);


                if ($response['status'] == 200)
                    return $this->response('Open Door Command Successfull',  null, true);
                else
                    return $this->response("Unkown Error. Please retry again after 1 min or contact   technical team", null, false);
            }
        } catch (\Exception $e) {
            return $this->response("Unkown Error. Please retry again after 1 min or contact   technical team", null, false);
        }
    }
    public function closeDoor(Request $request)
    {


        $device = Device::where("device_id", $request->device_id)->first();
        if ($device->status_id == 2) {
            return $this->response("Device is offline. Please Test Device Online status.", null, false);
        }
        try {

            if ($device->device_category_name == 'CAMERA') {

                if ($device->model_number == 'CAMERA1') {
                    //(new DeviceCameraController())->updateTimeZone();
                }
            } else  if ($device->model_number == 'MEGVII') {
                (new DeviceCameraModel2Controller($device->camera_sdk_url))->closeDoor($device);
                return $this->response('Close Door Command Successfull',  null, true);
            } else {


                $url = env('SDK_URL') . "/$request->device_id/CloseDoor";
                $response = $this->callCURL($url);



                if ($response['status']  == 200)
                    return $this->response('Close Door Command Successfull',  null, true);
                else
                    return $this->response("Unkown Error. Please retry again after 1 min or contact   technical team", null, false);
            }
        } catch (\Exception $e) {
            return $this->response("Unkown Error. Please retry again after 1 min or contact   technical team", null, false);
        }
    }
    public function getAlarmNotificationActive(Request $request)
    {


        return   DeviceSensorLogs::with(["device", "deviceTemperatureSensor"])->where("company_id", $request->company_id)
            ->where("alarm_status", 1)
            ->orderBy("log_time", "desc")



            ->get();
    }
    public function getAlarmNotification(Request $request)
    {
        //return  $devices = Device::with(["branch", "zone"])->where("company_id", $request->company_id)->where("alarm_status", 1)->get();
        $devices = Device::with(["branch", "zone", "deviceSensorLogs"])
            ->where("company_id", $request->company_id)
            ->where(function ($query) {
                $query->where("smoke_alarm_status", 1)
                    ->orWhere("fire_alarm_status", 1)
                    ->orWhere("temparature_alarm_status", 1)
                    ->orWhere("water_alarm_status", 1)
                    ->orWhere("power_alarm_status", 1)
                    ->orWhere("door_open_status", 1);
            })
            ->get();

        // Loop through each device and enrich log array with sensor info
        $results = $devices->map(function ($device) {
            $deviceArray = $device->toArray();

            if ($deviceArray['device_sensor_logs']["temperature_serial_address"] != null) {
                $sensor = \App\Models\DeviceTemperatureSensors::where("temperature_serial_address", $deviceArray['device_sensor_logs']["temperature_serial_address"])
                    ->where("device_id", $device->id)
                    ->first();

                $deviceArray['device_sensor_logs']["sensor_info"] = $sensor ? $sensor->toArray() : null;
            } else {
                $deviceArray['device_sensor_logs']["sensor_info"] =  null;
            }


            return $deviceArray;
        })->toArray();

        return response()->json($results);
    }
    public function openDoorAlways(Request $request)
    {
        if ($request->filled("device_id"))
            return  $this->CallAlwaysOpenDoor($request->device_id);
    }

    public function CallAlwaysOpenDoor($device_id)
    {

        if ($device_id == null || $device_id == '') return "";
        $device = Device::where("device_id", $device_id)->first();

        // if ($device->status_id == 2) {
        //     return $this->response("Device is offline. Please Test Device Online status.", null, false);
        // }
        try {

            if ($device->device_category_name == 'CAMERA') {

                if ($device->model_number == 'CAMERA1') {
                    //(new DeviceCameraController())->updateTimeZone();
                }
            } else  if ($device->model_number == 'MEGVII') {
                (new DeviceCameraModel2Controller($device->camera_sdk_url))->openDoorAlways($device);
                return $this->response('Always Open  Command is Successfull',  null, true);
            } else {
                $url = env('SDK_URL') . "/$device_id/HoldDoor";
                $response = $this->callCURL($url);

                if ($response['status']  == 200)
                    return $this->response('Always Open  Door Command is Successfull',  null, true);
                else
                    return $this->response("Unkown Error. Please retry again after 1 min or contact   technical team", null, false);
            }
        } catch (\Exception $e) {
            return $this->response("Unkown Error. Please retry again after 1 min or contact   technical team", null, false);
        }
    }


    public function sync_device_date_time(Request $request, $device_id, $company_id)
    {

        $device = Device::where("company_id", $company_id)->where("device_id", $device_id)->first();
        if ($device) {
            if ($device->device_category_name == 'CAMERA') {

                try {
                    if ($device->model_number == 'CAMERA1') {
                        //(new DeviceCameraController())->updateTimeZone();
                    }
                } catch (\Exception $e) {
                    return $this->response("Unkown Error. Please retry again after 1 min or contact to technical team", null, false);
                }
            } else  if ($device->model_number == 'MEGVII') {
                (new DeviceCameraModel2Controller($device->camera_sdk_url))->updateTimeZone($device);


                return $this->response('Time has been synced to the Device.',  null, true);
            } else {
                // $url = "http://139.59.69.241:7000/$device_id/SyncDateTime";
                $url = env('SDK_URL') . "/$device_id/SetWorkParam";


                $utc_time_zone  = Device::where('device_id', $device_id)->pluck("utc_time_zone")->first();;
                if ($utc_time_zone != '') {

                    $dateObj  = new DateTime("now", new DateTimeZone($utc_time_zone));
                    $currentDateTime = $dateObj->format('Y-m-d H:i:00');

                    (new SDKController)->processSDKRequestSettingsUpdateTime($device_id, $currentDateTime);
                    $result = (object)["status" => 200];

                    if ($result && $result->status == 200) {
                        try {
                            $record = Device::where("device_id", $device_id)->update([
                                "sync_date_time" => $currentDateTime,
                            ]);

                            if ($record) {
                                return $this->response('Time has been synced to the Device.', Device::with(['status', 'company'])->where("device_id", $device_id)->first(), true);
                            } else {
                                return $this->response('Time cannot synced to the Device.', null, false);
                            }
                        } catch (\Throwable $th) {
                            return $this->response('Time cannot synced to the Device.', null, false);
                        }
                    } else if ($result && $result->status == 102) {
                        return $this->response("The device is not connected to the server or is not registered", $result, false);
                    }

                    return $this->response("Unkown Error. Please retry again after 1 min or contact to technical team", null, false);
                } else {
                    return $this->response("The device details are not exist", null, false);
                }
            }
        }
    }

    public function devcieCountByStatus($company_id)
    {

        if ($company_id > 0) {
            $statusCounts = Device::where('company_id', $company_id)
                ->whereIn('status_id', [1, 2])
                ->where('device_id', "!=", "Manual")
                ->selectRaw('status_id, COUNT(*) as count')
                ->groupBy('status_id')
                ->get();

            $onlineDevices = 0;
            $offlineDevices = 0;

            foreach ($statusCounts as $statusCount) {
                if ($statusCount->status_id == 1) {
                    $onlineDevices = $statusCount->count;
                } elseif ($statusCount->status_id == 2) {
                    $offlineDevices = $statusCount->count;
                }
            }

            return [
                "total" => $onlineDevices + $offlineDevices,
                "labels" => ["Online", "Offline"],
                "series" => [$onlineDevices, $offlineDevices],
            ];
        } else {
            return [
                "total" => 0,
                "labels" => ["Online", "Offline"],
                "series" => [0, 0]
            ];
        }
    }
    public function getActiveTimeSettings(Request $request, $key_id)
    {
        $model =  DeviceActivesettings::where('company_id', $request->company_id)
            ->where('device_id', $key_id)
            ->where('date_from', $request->date_from)
            ->where('date_to', $request->date_to)
            ->get();
        $input_time_slots = $request->input_time_slots;

        $open_array = [];
        if (isset($model[0])) {

            $open_json = $model[0]->open_json;
            $open_array = json_decode($open_json, true);
        }
        $return_araay = [];

        foreach ($open_array as $values) {

            foreach ($values as $key => $val) {

                $return_araay[] = $key . '-' . $key = array_search($val, $input_time_slots);
            }
        }


        return $return_araay;
    }
    public function checkDeviceHealth(Request $request)
    {

        return $this->checkDevicesHealthCompanyId($request->company_id);
    }

    public function checkDevicesHealthCompanyId($company_id = '')
    {



        $devices = Device::whereNotIn("device_type", ["Mobile", "Manual"])
            ->where("device_id", '!=', "Manual")
            ->where("serial_number", '!=', "Manual")
            ->when($company_id > 0, fn($q) => $q->where('company_id', $company_id))
            ->get();

        $totalDevicesCount = $devices->count();
        $onlineCount = $devices->where('status_id', 1)->count();
        $offline_devices_count = $totalDevicesCount - $onlineCount;

        return "$onlineCount Devices online. $offline_devices_count Devices offline.  $totalDevicesCount records found.";


        /*

        $devices = Device::whereNotIn("device_type", ["Mobile", "Manual"])
            ->where("device_id", "!=", "Manual")
            ->where("serial_number", "!=", "Manual")


            ->when($company_id > 0, fn($q) => $q->where('company_id', $company_id))

            ->get();

        $onlineCount = 0;
        $totalDevicesCount = $devices->count();

        $updates = [];

        foreach ($devices as $device) {
            return  $deviceConfig = $this->getDeviceConfig($device["serial_number"]);
            if ($deviceConfig)

                $isOnline = isset($deviceConfig["config"]);

            $onlineStatus = $isOnline ? 1 : 2;
            if ($isOnline && $deviceConfig["serialNumber"] == $device["serial_number"]) {
                $onlineCount++;
                $currentDateTime = date("Y-m-d H:i:s");
                try {
                    $currentDateTime = now(new DateTimeZone($device["utc_time_zone"]))->format('Y-m-d H:i:s');
                } catch (Exception $e) {
                }


                $updates[] = [
                    'serial_number' => $device["serial_number"],
                    'status_id' => $onlineStatus,
                    'last_live_datetime' => $currentDateTime,
                ];
            }
        }

        // Batch update devices
        foreach ($updates as $update) {
            Device::where("serial_number", $update['serial_number'])->update([
                'status_id' => $update['status_id'],
                'last_live_datetime' => $update['last_live_datetime'],
            ]);
        }

        $offline_devices_count = $totalDevicesCount - $onlineCount;

        return "$offline_devices_count Devices offline. $onlineCount Devices online. $totalDevicesCount records found.";
*/


        // $total_devices_count = Device::where("device_type", "!=", "Mobile")
        //     ->when($company_id > 0, fn($q) => $q->where('company_id', $company_id))
        //     ->where("device_type", "!=", "Manual")
        //     ->where("device_id", "!=", "Manual")


        //     ->get()->count();;



        // $devicesHealth = (new SDKController())->GetAllDevicesHealth();


        // $companyDevices = Device::where("device_type", "!=", "Mobile")
        //     ->when($company_id > 0, fn($q) => $q->where('company_id', $company_id))
        //     ->where("device_type", "!=", "Manual")
        //     ->where("device_id", "!=", "Manual")

        //     ->Where(function ($q) {
        //         $q->where('device_category_name', "!=", "CAMERA");
        //         $q->orWhere('device_category_name', null);
        //     })
        //     ->get();
        // $total_iterations = count($companyDevices);
        // $online_devices_count = 0;
        // $offline_devices_count = 0;
        // $companiesIds = [];
        // foreach ($companyDevices as $key => $Device) {
        //     $companyDevice_id = $Device["device_id"];
        //     $companiesIds[] = $Device["company_id"];
        //     $SDKDeviceResponce = array_filter($devicesHealth["data"], function ($device) use ($companyDevice_id) {
        //         return $companyDevice_id == $device['sn'];
        //     });

        //     if (count($SDKDeviceResponce) && current($SDKDeviceResponce)["keepAliveTime"] != '') {
        //         $date  = new DateTime(current($SDKDeviceResponce)["keepAliveTime"], new DateTimeZone('Asia/Dubai'));
        //         $DeviceDateTime = $date->format('Y-m-d H:i:00');
        //         $online_devices_count++;
        //         Device::where("device_id", $companyDevice_id)->update(["status_id" => 1, "last_live_datetime" => $DeviceDateTime]);
        //     } else {
        //         // $offline_devices_count++;
        //         Device::where("device_id", $companyDevice_id)->update(["status_id" => 2,]);

        //         // info($count . "companies has been updated");
        //     }
        // }
        // try {

        //     $count = (new DeviceCameraController(''))->updateCameraDeviceLiveStatus($company_id);
        //     $online_devices_count = $online_devices_count +  $count;
        // } catch (\Exception $e) {
        // }
        // try {

        //     $count = (new DeviceCameraModel2Controller(''))->getCameraDeviceLiveStatus($company_id);

        //     $online_devices_count = $online_devices_count +  $count;
        // } catch (\Exception $e) {
        // }

        // $offline_devices_count = $total_devices_count - $online_devices_count;

        // Company::whereIn("id", array_values($companiesIds))->update(["is_offline_device_notificaiton_sent" => false]);
        // return   "$offline_devices_count Devices offline. $online_devices_count Devices online. $total_devices_count records found.";
    }
    // public function checkDeviceHealth_old(Request $request)
    // {
    //     $devices = Device::where("company_id", $request->company_id ?? 0)->where("device_type", "!=", "Mobile")
    //         ->where("device_type", "!=", "Manual")
    //         ->pluck("device_id");

    //     $total_iterations = 0;
    //     $online_devices_count = 0;
    //     $offline_devices_count = 0;

    //     $sdk_url = env("SDK_URL");

    //     if (checkSDKServerStatus($sdk_url) === 0) {
    //         return "Failed to connect to the SDK Server: $sdk_url";
    //     }
    //     $return_araay = [];
    //     foreach ($devices as $device_id) {
    //         $curl = curl_init();

    //         if (!$sdk_url) {
    //             return "sdk url not defined.";
    //         }

    //         curl_setopt_array($curl, array(

    //             // CURLOPT_URL => "https://sdk.ideahrms.com/CheckDeviceHealth/$device_id",
    //             // CURLOPT_URL => "http://139.59.69.241:5000/CheckDeviceHealth/$device_id",
    //             CURLOPT_URL => "$sdk_url/CheckDeviceHealth/$device_id",
    //             CURLOPT_RETURNTRANSFER => true,
    //             CURLOPT_ENCODING => '',
    //             CURLOPT_MAXREDIRS => 10,
    //             CURLOPT_TIMEOUT => 0,
    //             CURLOPT_FOLLOWLOCATION => true,
    //             CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    //             CURLOPT_CUSTOMREQUEST => 'POST',
    //         ));

    //         $response = curl_exec($curl);



    //         curl_close($curl);

    //         $status = json_decode($response);

    //         if ($status && $status->status == 200) {
    //             $online_devices_count++;
    //         } else {
    //             $offline_devices_count++;
    //         }

    //         Device::where("device_id", $device_id)->update(["status_id" => $status->status == 200 ? 1 : 2]);

    //         $total_iterations++;
    //     }

    //     return   "$offline_devices_count Devices offline. $online_devices_count Devices online. $total_iterations records found.";
    // }

    public function callCURL($url)
    {
        $curl = curl_init();
        //$device_id = $request->device_id;
        //$url = env('SDK_URL') . "$device_id/CloseDoor";
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        //echo $response;
        return  $response = json_decode($response, true);
    }
    public function updateActiveTimeSettings(Request $request, $device_id)
    {




        $input_days = $request->input_days;
        $input_time_slots = $request->input_time_slots;
        $span_time_minutes = $request->span_time_minutes;

        $selected_matrix = json_decode($request->selected_matrix);


        $days_array = [];
        $open_time_array = [];
        $closing_time_array = [];

        $input_strings = $selected_matrix; //["0-0", "1-1", "2-2", "3-3", "4-4", "5-5", "6-6", "0-2", "1-3", "2-4", "3-5", "4-6", "5-7", "6-8"];

        $open_time_array = [];
        $closing_time_array = [];

        foreach ($input_strings as $string) {
            list($key, $value) = explode('-', $string);
            if (isset($input_time_slots[$value])) {
                $newtimestamp = strtotime(date('Y-m-d ' . $input_time_slots[$value] . ':00 ') . '+ ' . $span_time_minutes . ' minute');
                $close_time = date('H:i', $newtimestamp);

                $open_time_array[] = ['' .  $key . '' => $input_time_slots[$value]]; //$key . '-' . $input_time_slots[$value];
                $closing_time_array[] = ['' .  $key . '' => $close_time]; //$key . '-' . $input_time_slots[$value];

            }
        }

        $device_settings_id = '';

        $devices_active_settings_array = [
            'device_id' => $device_id,
            'company_id' =>  $request->company_id,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'open_json' => json_encode($open_time_array),
            'close_json' => json_encode($closing_time_array)
        ];

        $record = DeviceActivesettings::where("device_id", $device_id)->where("company_id", $request->company_id);
        if ($record->count()) {


            $device_settings_id = $record->get()[0]->id;
            $record->update($devices_active_settings_array);
        } else {
            $device_settings_id =  DeviceActivesettings::create($devices_active_settings_array)->id;
        }


        return   [
            'record' => $device_settings_id,
            'message' => 'Successfully Updated.',
            'status' => true
        ];
    }

    public function handleNotification($id)
    {

        $company = Company::where("id", $id)->where("is_offline_device_notificaiton_sent", false)->first();

        if ($company) {
            $notifications = DeviceNotification::with("managers")->where("company_id", $id)

                ->with("managers", function ($query) use ($id) {
                    $query->where("company_id", $id);
                })
                ->get();


            foreach ($notifications as $key => $notification) {



                $company = $company->load(['devices' => function ($q) use ($notification) {
                    $q->where("status_id", self::OFFLINE_STATUS_ID)
                        ->where("branch_id", $notification->branch_id)
                        ->where("name", "!=", "Mobile");
                }]);

                $counter = 1;
                if ($company) {
                    $offlineDevicesCount = count($company->devices);

                    // if (!$offlineDevicesCount) {
                    //     return $this->getMeta("SendNotificatinForOfflineDevices", "All Devices Online");
                    // }
                    $location_array = array_column($company->devices->toArray(), "location");
                    $devicesLocation = json_encode($location_array);
                    $devicesLocations = '';
                    foreach ($location_array as $key => $location) {
                        $devicesLocations .= ($counter++) . ':' . $location;
                        if ($key < count($location_array) - 1) {
                            $devicesLocations .= ",\n";
                        }
                    }

                    // $this->sendWhatsappNotification($message, '971554501483');
                    // $this->sendWhatsappNotification($message, '971553303991');


                    $this->sendNotification($notification, $company, $offlineDevicesCount, $devicesLocations, "", $company->devices);

                    $company->update(["is_offline_device_notificaiton_sent" => true, "offline_notification_last_sent_at" => date('Y-m-d H:i:s')]);
                }
            }
            return "Notification sent to WhatsApp and email.";
        } else {
            return "Already sent. Waiting for schedule time";
        }
    }

    public function sendNotification($notification, $company, $offlineDevicesCount, $devicesLocations, $message, $devices)
    {

        foreach ($notification->managers as $key => $manager) {

            $message = "🔔 *Notification for offline devices* 🔔\n\n";
            $message .= "*Hello, {$manager->name}*\n\n";
            $message .= "*Company: , {$company->name}*\n\n";
            $message .= "Total *({$offlineDevicesCount})* of your devices are currently offline. Please take a look and address the issue as needed to avoid any errors in report.\n\n";
            $message .= "*Devices location(s):* \n{$devicesLocations}  \n\n";
            $message .= "If you have any questions or need assistance, feel free to reach out.\n\n";
            $message .= "Best regards\n";
            $message .= "*MyTime2Cloud*";



            $data = [
                "company_id" => $company->id,
                "branch_id" => $notification->branch_id,
                "notification_id" => $notification->id,
                "notification_manager_id" => $manager->id,
                "email" => null,
                "whatsapp_number" => null,
                "message" => $message
            ];

            // return  view('emails.TestmailFormat', ["company" => $company, "offlineDevicesCount" => $offlineDevicesCount, "devicesLocations" => $devicesLocations, "manager" => $manager]);
            // return false;
            $branch_name = $notification->branch ?  $notification->branch->branch_name :  '---';
            if (in_array("Email", $notification->mediums)) {
                if ($manager->email != '') {
                    Mail::to($manager->email)->send(new EmailNotificationForOfflineDevices($company, $offlineDevicesCount, $devices, $manager, $branch_name));

                    $data["email"] = $manager->email;
                }
            }
            if (in_array("Whatsapp", $notification->mediums)) {
                if ($manager->whatsapp_number != '' && strlen($manager->whatsapp_number) > 10) {
                    (new WhatsappController)->sendWhatsappNotification($company, $message, $manager->whatsapp_number);
                    $data["whatsapp_number"] = $manager->whatsapp_number;
                }
            }

            DeviceNotificationsLog::create($data);
        }
    }

    public function seedDefaultData($company_id)
    {
        $data = [];

        foreach (range(1, 10) as $i) {
            $data[] = [
                "company_id" => $company_id,
                "branch_id" => 1,
                "status_id" => 1,
                "name" => "demo-" . $i,
                "short_name" => "demo-" . $i,
                "device_id" => "OX-886202011" . $i,
                "location" => "demo-" . $i,
                "ip" => "0.0.0.0",
                "port" => "00000",
                "model_number" => "demo-" . $i,
                "device_type" => "Access Control",
                "synced" => 0,
                "function" => "demo",
                "serial_number" => "demo-" . $i,
                "utc_time_zone" => "demo"
            ];
        }

        Device::insert($data);

        return "Cron DeviceSeeder: " . count($data) . " record has been inserted.";
    }

    public function modes()
    {
        return [
            "Face",
            "RFID",
            "Finger Print",
            "Password"
        ];
    }
    public function getDeviceCompanyInfoForArduino(Request $request)
    {
        if ($request->has('serial_number')) {
            $device = Device::with('company')->where('serial_number', $request->serial_number)->first();

            if ($device && $device->company && $device->company->expiry) {
                $expiryDate = Carbon::parse($device->company->expiry);
                $today = Carbon::today();

                $remainingDays = $today->diffInDays($expiryDate, false); // false = get negative if expired

                return response()->json([
                    'expiry_date' => $expiryDate->toDateString(),
                    'remaining_days' => $remainingDays,
                    'name' =>  $device->company->name,


                ]);
            } else {
                return response()->json(['error' => 'Device or company expiry not found'], 404);
            }
        }

        return response()->json(['error' => 'Serial number is required'], 400);
    }
    public function getDeviceConfigSettingsFromArduinoSocket(Request $request)
    {
        return $this->getDeviceConfig($request->serial_number);
    }

    public function getDeviceConfigSettingsFromCache(Request $request)
    {

        $serial_number = $request->serial_number;
        $config = Cache::get("device_config_$serial_number");
        if ($config) {


            return response()->json([
                'serialNumber' => $serial_number,
                'config' => json_decode($config),
                'service' => "mqtt"
            ]);
        } else {
            return response()->json([
                'serialNumber' => $serial_number,
                'config' => null,
                'service' => "mqtt"
            ]);
        }
    }

    public function commandCallSocketToDevice(Request $request)
    {
        if ($request->serial_number && $request->cmd) {

            $config = [];

            $config[$request->cmd] = $request->action;


            return  $this->callDeviceCommand($request->serial_number, $config);
        }
    }

    public function getDeviceConfig($serial_number)
    {

        $isAPIResponseCompleted = false;


        //if ($serial_number && !env("DEVICE_CONFIG_MQTT"))
        {


            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'http://165.22.222.17:6000/device-config/' . $serial_number,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response = curl_exec($curl);

            curl_close($curl);


            $response = json_decode($response, true);
            $response['service'] = "socket";


            if (isset($response["error"]))
                return   $response;
            else if (isset($response['config'])) { {
                    $response['config'] = json_decode($response['config'], true);

                    if ($response['config'])
                        return response()->json([
                            'serialNumber' => $serial_number,
                            'config' => $response['config'],
                            'service' => "scoket"
                        ]);

                    else

                        return  $response['error'] = "Device Communication error";

                    $isAPIResponseCompleted = true;

                    return response()->json([
                        'serialNumber' => $serial_number,
                        'config' => [],
                        'service' => "scoket"
                    ]);
                }
            } else   $response['error'] = "Device Communication error";
            // if ($isAPIResponseCompleted)
            //     return  $response;
        }

        /*
        //else  if ($serial_number && env("DEVICE_CONFIG_MQTT"))
        {
            //Cache::forget("device_config_$serial_number");
            // // Publish GET_CONFIG to MQTT
            $mqtt = new MqttService();
            $mqtt->publish(env('MQTT_DEVICE_CLIENTID') . "/{$serial_number}/config/request", "GET_CONFIG", $serial_number);


            // Wait max 3 seconds for response from device
            $timeoutMs = 3000;
            $interval = 100; // check every 100ms
            $elapsed = 0;

            while ($elapsed < $timeoutMs) {
                usleep($interval * 1000);
                $config = Cache::get("device_config_$serial_number");
                if ($config) {


                    return response()->json([
                        'serialNumber' => $serial_number,
                        'config' => json_decode($config),
                        'service' => "mqtt"
                    ]);
                }
                $elapsed += $interval;
            }


            // $config = Cache::get("device_config_$serial_number");
            // if ($config) {
            //     return response()->json([
            //         'serialNumber' => $serial_number,
            //         'config' =>  json_decode($config, true),
            //         'service' => "mqtt"
            //     ]);
            // }

            // // // Clear old response
            // Cache::forget("device_config_$serial_number");



            return response()->json([
                'serialNumber' => $serial_number,
                'config' => [],
                'service' => "mqtt"
            ]);
        }

        */
    }

    public function updateTemperatureConfigSettingsFromArduinoSocket(Request $request)
    {

        $config = [];

        $config["temperature_alerts_config"] = $request->temperature_alerts_config;

        $temperature_alerts_config = $config["temperature_alerts_config"];

        if ($request->device_id) {

            //delete
            DeviceTemperatureSensors::where("company_id", $request->company_id)->where("device_id", $request->device_id)->delete();

            foreach ($temperature_alerts_config as   $sensor) {
                $data = [
                    "company_id" => $request->company_id,
                    "device_id" => $request->device_id,
                    "temperature_serial_address" => $sensor["sensor_address_id"],
                    "name" => $sensor["sensor_name"]
                ];

                DeviceTemperatureSensors::create($data);
            }
        }

        //creating DB


        return  $this->callDeviceCommand($request->serial_number, $config);
    }
    public function updateDeviceConfigSettingsFromArduinoSocket(Request $request)
    {

        $config = [];



        $config["heartbeat"] = $request->config["heartbeat"] == '' ? 10 : $request->config["heartbeat"];
        $config["reset_settings_duration"] = $request->config["reset_settings_duration"] == '' ? 10 : $request->config["reset_settings_duration"];
        // $config["secondsCountFrom10to60"] = $request->config["secondsCountFrom10to60"] == '' ? 60 : $request->config["secondsCountFrom10to60"];




        if (isset($request->config["mqtt_communication"]) && $request->config["mqtt_communication"] == true) {
            $config["mqtt_communication"] = true;
            $config["mqtt_server"] = $request->config["mqtt_server"];
            $config["mqtt_port"] = $request->config["mqtt_port"];
            $config["mqtt_clientId"] = $request->config["mqtt_clientId"];
        } else {
            $config["mqtt_communication"] = false;
            $config["mqtt_server"] = "";
            $config["mqtt_port"] = "";
            $config["mqtt_clientId"] = "";
        }
        if (isset($request->config["socket_communication"]) && $request->config["socket_communication"] == true) {
            $config["socket_communication"] = true;
            $config["server_ip"] = $request->config["server_ip"];
            $config["server_port"] = $request->config["server_port"];
        } else {
            $config["socket_communication"] = false;
            $config["server_ip"] = "";
            $config["server_port"] =  "";
        }

        if (isset($request->config["http_communication"]) && $request->config["http_communication"] == true) {
            $config["http_communication"] = true;
            $config["http_link"] = $request->config["http_link"];
        } else {
            $config["http_communication"] = false;
            $config["http_link"] = "";
        }
        // $config["server_url"] = $request->config["server_url"];
        // $config["server_ip"] = $request->config["server_ip"];
        // $config["server_port"] = $request->config["server_port"];

        $config["phone_number1"] = isset($request->config["phone_number1"]) ? $request->config["phone_number1"] : '';
        $config["phone_number2"] = isset($request->config["phone_number2"]) ? $request->config["phone_number2"] : '';
        $config["phone_number3"] = isset($request->config["phone_number3"]) ? $request->config["phone_number3"] : '';
        $config["phone_number4"] = isset($request->config["phone_number4"]) ? $request->config["phone_number4"] : '';
        $config["phone_number5"] = isset($request->config["phone_number5"]) ? $request->config["phone_number5"] : '';


        if (isset($request->config["power_checkbox"]) && $request->config["power_checkbox"] == true) {
            $config["power_checkbox"] = true;

            $config["power_alert_sms"] = isset($request->config["power_alert_sms"]) ? $request->config["power_alert_sms"] : false;
            $config["power_alert_call"] = isset($request->config["power_alert_call"]) ? $request->config["power_alert_call"] : false;
            $config["power_alert_whatsapp"] = isset($request->config["power_alert_whatsapp"]) ? $request->config["power_alert_whatsapp"] : false;
        } else {
            $config["power_checkbox"] = false;

            $config["power_alert_sms"] = false;
            $config["power_alert_call"] = false;
            $config["power_alert_whatsapp"] = false;
        }
        if (isset($request->config["water_checkbox"]) && $request->config["water_checkbox"] == true) {
            $config["water_checkbox"] = true;


            $config["water_alert_sms"] = isset($request->config["water_alert_sms"]) ? $request->config["water_alert_sms"] : false;
            $config["water_alert_call"] = isset($request->config["water_alert_call"]) ? $request->config["water_alert_call"] : false;
            $config["water_alert_whatsapp"] = isset($request->config["water_alert_whatsapp"]) ? $request->config["water_alert_whatsapp"] : false;
        } else {
            $config["water_checkbox"] = false;

            $config["water_alert_sms"] = false;
            $config["water_alert_call"] = false;
            $config["water_alert_whatsapp"] = false;
        }
        if (isset($request->config["fire_checkbox"]) && $request->config["fire_checkbox"] == true) {
            $config["fire_checkbox"] = true;


            $config["fire_alert_sms"] = isset($request->config["fire_alert_sms"]) ? $request->config["fire_alert_sms"] : false;
            $config["fire_alert_call"] = isset($request->config["fire_alert_call"]) ? $request->config["fire_alert_call"] : false;
            $config["fire_alert_whatsapp"] = isset($request->config["fire_alert_whatsapp"]) ? $request->config["fire_alert_whatsapp"] : false;
        } else {
            $config["fire_checkbox"] = false;

            $config["fire_alert_sms"] = false;
            $config["fire_alert_call"] = false;
            $config["fire_alert_whatsapp"] = false;
        }
        if (isset($request->config["siren_checkbox"]) &&  $request->config["siren_checkbox"] == true) {
            $config["siren_checkbox"] = true;


            $config["max_siren_play"] = isset($request->config["max_siren_play"]) ? (float) $request->config["max_siren_play"] : 0;
            $config["max_siren_pause"] = isset($request->config["max_siren_pause"]) ? (float) $request->config["max_siren_pause"] : 0;
        } else {
            $config["siren_checkbox"] = false;
        }



        if (isset($request->config["temp_checkbox"]) && $request->config["temp_checkbox"] == true) {
            $config["temp_checkbox"] = true;
            $config["max_temperature"] = (float) $request->config["max_temperature"] ?? 0;
            $config["min_temperature"] = (float) $request->config["min_temperature"] ?? 0;

            $config["temp_alert_sms"] = $request->config["temp_alert_sms"] ?? false;
            $config["temp_alert_call"] = $request->config["temp_alert_call"] ?? false;
            $config["temp_alert_whatsapp"] = $request->config["temp_alert_whatsapp"] ?? false;
        } else {
            $config["temp_checkbox"] = false;

            $config["temp_alert_sms"] = false;
            $config["temp_alert_call"] =  false;
            $config["temp_alert_whatsapp"] = false;
        }
        if (isset($request->config["humidity_checkbox"]) &&  $request->config["humidity_checkbox"] == true) {
            $config["humidity_checkbox"] = true;
            $config["max_humidity"] = (float) $request->config["max_humidity"];

            $config["humidity_alert_sms"] = $request->config["humidity_alert_sms"] ?? false;
            $config["humidity_alert_call"] = $request->config["humidity_alert_call"] ?? false;
            $config["humidity_alert_whatsapp"] = $request->config["humidity_alert_whatsapp"] ?? false;
        } else {
            $config["humidity_checkbox"] = false;

            $config["humidity_alert_sms"] = false;
            $config["humidity_alert_call"] = false;
            $config["humidity_alert_whatsapp"] = false;
        }
        if (isset($request->config["doorcontact_checkbox"]) && $request->config["doorcontact_checkbox"] == true) {
            $config["doorcontact_checkbox"] = true;
            $config["max_doorcontact"] = (float) $request->config["max_doorcontact"];

            $config["door_alert_sms"] = $request->config["door_alert_sms"] ?? false;
            $config["door_alert_call"] = $request->config["door_alert_call"] ?? false;
            $config["door_alert_whatsapp"] = $request->config["door_alert_whatsapp"] ?? false;
        } else {
            $config["doorcontact_checkbox"] = false;

            $config["door_alert_sms"] = false;
            $config["door_alert_call"] = false;
            $config["door_alert_whatsapp"] = false;
        }

        return  $this->callDeviceCommand($request->serial_number, $config);
    }
    public function callDeviceCommand($serial_number, $config)
    {
        // if ($serial_number &&  !env("DEVICE_CONFIG_MQTT")) {
        $url = 'http://165.22.222.17:6000/device-config-update/' . $serial_number;

        $postData = [
            "action" => "UPDATE_CONFIG",
            "serialNumber" => $serial_number,
            "config" => $config,
        ];




        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($postData),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen(json_encode($postData))
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);

        curl_close($curl);

        // if ($response === false) {
        //     return $this->response('cURL Error: ' . $curlError, null, false);
        // }

        //   return $this->response('Updated Successfully', ['response' => $response, 'status' => $httpCode], true);
        // } else if ($serial_number && env("DEVICE_CONFIG_MQTT")) {


        $postData = [
            "action" => "UPDATE_CONFIG",
            "serialNumber" => $serial_number,
            "config" => $config,
        ];

        // Publish  to MQTT
        $mqtt = new MqttService();
        $mqtt->publish(env('MQTT_DEVICE_CLIENTID') . "/{$serial_number}/config/request",  json_encode($postData), $serial_number);


        //}

        if ($response === false) {
            return $this->response('cURL Error: ' . $curlError, null, false);
        }

        return $this->response('Updated Successfully', null, true);
    }

    public function getDevicesTemperatureSensors()
    {
        $model = DeviceTemperatureSensors::query();
        $model->where('company_id', request('company_id'));

        $model->when(request()->filled('branch_id'), fn($q) => $q->where('branch_id', request('branch_id')));
        $model->orderBy(request('order_by') ?? "name", request('sort_by_desc') ? "desc" : "asc");


        return $model->paginate($request->per_page ?? 10);
    }
}
