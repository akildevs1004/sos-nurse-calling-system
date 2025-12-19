<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;

use App\Models\Attendance;
use App\Models\Device;
use App\Models\DeviceSosAlarms;
use App\Models\DeviceSosRoomLogs;
use App\Models\DeviceSosRooms;
use App\Models\Employee;
use App\Models\HostCompany;
use App\Models\Leave;
use App\Models\Visitor;
use App\Models\VisitorAttendance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SOSRoomsControllers extends Controller
{

    public function updateResponseDatetime(Request $request)
    {


        $companyId =  (int) $request->company_id;
        $alarmId =  (int) $request->alarmId;

        $alarmLog = DeviceSosRoomLogs::with(["device"])->where("id", $alarmId)->where("company_id", $companyId)->where("sos_status", true)->first();


        if ($alarmLog && $alarmLog["device"]) {

            $now = now()->setTimezone($alarmLog->device->utc_time_zone)->format('Y-m-d H:i:s');

            $alarmLog->update(["responded_datetime" => $now]);
            return $this->response('Acknowledgement Updated Successfully', null, true);
        }
        return $this->response('Not Updated.Try Again ', null, false);
    }

    public function dashboardStats(Request $request)
    {
        $companyId = (int) $request->company_id;
        $slaMinutes = 5;

        // total responded calls
        $total = DeviceSosRoomLogs::where('company_id', $companyId)
            ->whereNotNull('response_in_minutes')
            ->count();

        $repeatedCalls = DeviceSosRoomLogs::where('company_id', $companyId)->whereDate('alarm_start_datetime', date("Y-m-d"))
            ->whereNotNull('response_in_minutes')
            ->select('room_id')
            ->groupBy('room_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        $ackCount = DeviceSosRoomLogs::where('company_id', $companyId)->whereDate('alarm_start_datetime', date("Y-m-d"))
            ->whereNotNull('responded_datetime')
            ->select('id')
            ->groupBy('id')
            ->havingRaw('COUNT(*) > 0')
            ->count();

        $averageMinutes = DeviceSosRoomLogs::where('company_id', $companyId)
            ->whereNotNull('response_in_minutes')

            ->avg('response_in_minutes');

        // responded within SLA
        $withinSla = DeviceSosRoomLogs::where('company_id', $companyId)
            ->whereNotNull('response_in_minutes')
            ->where('response_in_minutes', '<=', $slaMinutes)
            ->count();

        $percentage = $total > 0
            ? round(($withinSla / $total) * 100, 2)
            : 0;

        return response()->json([
            'sla_percentage' => $percentage,   // e.g. 82.45
            'sla_minutes'    => $slaMinutes,
            'averageMinutes'    => $averageMinutes,



            'repeated'    => $repeatedCalls,
            'ackCount'    => $ackCount,




        ]);
    }



    public function dashboardRooms(Request $request)
    {
        $companyId = (int) $request->company_id;

        $deviceRooms = DeviceSosRooms::query()
            ->with([
                'device',
                'latestAlarm' => function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                }
            ])
            ->where('company_id', $companyId)
            ->orderby('room_id', "asc")

            ->get()
            ->map(function ($room) {
                // keep your output key name: alarm
                $room->setAttribute('alarm', $room->latestAlarm);
                unset($room->latestAlarm);
                return $room;
            });

        return response()->json($deviceRooms);
        // $companyId = $request->company_id;


        // $deviceRooms = DeviceSosRooms::with(["device"])->where("company_id", $companyId)->get();

        // foreach ($deviceRooms as $key => $room) {
        //     $alarm = DeviceSosRoomLogs::where("company_id", $companyId)->where("device_sos_room_table_id", $room->id)->orderby("alarm_start_datetime", "desc")->first();

        //     if ($alarm)
        //         $room["alarm"] = $alarm;

        //     else
        //         $room["alarm"] = null;
        // }

        // return $deviceRooms;
    }
}
