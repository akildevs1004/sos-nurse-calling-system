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



    public function dashboardStats(Request $request)
    {
        $companyId = (int) $request->company_id;
        $slaMinutes = 5;

        // total responded calls
        $total = DeviceSosRoomLogs::where('company_id', $companyId)
            ->whereNotNull('response_in_minutes')
            ->count();

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
