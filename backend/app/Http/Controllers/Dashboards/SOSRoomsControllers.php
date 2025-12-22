<?php

namespace App\Http\Controllers\Dashboards;

use App\Exports\SOSExcelReports;
use App\Http\Controllers\Controller;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Device;
use App\Models\DeviceSosAlarms;
use App\Models\DeviceSosRoomLogs;
use App\Models\DeviceSosRooms;
use App\Models\Employee;
use App\Models\HostCompany;
use App\Models\Leave;
use App\Models\Visitor;
use App\Models\VisitorAttendance;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

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
        $slaMinutes = 1;




        // total responded calls
        $totalSOSActive = DeviceSosRoomLogs::with('room')
            ->where('company_id', $companyId)
            ->where("alarm_end_datetime", null)
            ->when($request->filled('date_from'), function ($q) use ($request) {
                $q->whereDate('alarm_start_datetime', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($q) use ($request) {
                $q->whereDate('alarm_end_datetime', '<=', $request->date_to);
            })
            ->when($request->filled('roomType'), function ($q) use ($request) {
                $q->whereHas('room', function ($qr) use ($request) {
                    $qr->where('room_type', $request->roomType);
                });
            })
            ->count();

        $activeDisabledSOS = DeviceSosRoomLogs::with('room')
            ->where('company_id', $companyId)

            ->where('alarm_end_datetime', null)
            ->when($request->filled('date_from'), function ($q) use ($request) {
                $q->whereDate('alarm_start_datetime', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($q) use ($request) {
                $q->whereDate('alarm_end_datetime', '<=', $request->date_to);
            })
            ->when($request->filled('roomType'), function ($q) use ($request) {
                $q->whereHas('room', function ($qr) use ($request) {
                    $qr->where('room_type', $request->roomType);
                });
            })
            ->whereHas('room', function ($qr) use ($request) {
                $qr->where('room_type', $request->roomType);
            })
            ->count();
        $repeatedCalls = DeviceSosRoomLogs::where('company_id', $companyId)->whereDate('alarm_start_datetime', date("Y-m-d"))
            ->whereNotNull('alarm_start_datetime')
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

        $percentage = $totalSOSActive > 0
            ? round(($withinSla / $totalSOSActive) * 100, 2)
            : 0;

        return response()->json([
            'sla_percentage' => $percentage,   // e.g. 82.45
            'sla_minutes'    => $slaMinutes,
            'averageMinutes'    => $averageMinutes,



            'repeated'    => $repeatedCalls,
            'ackCount'    => $ackCount,

            'totalSOSCount' => $totalSOSActive,

            "activeDisabledSos" => $activeDisabledSOS


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

    public function  getRecords($request, $perpage = null)
    {

        $companyId = (int) $request->company_id;

        $model = DeviceSosRoomLogs::query()
            ->with(['room', 'device'])
            ->where('company_id', $companyId);

        // Common search
        $model->when($request->filled('common_search'), function ($q) use ($request) {
            $s = trim($request->common_search);

            $q->where(function ($qq) use ($s) {
                $qq->where('room_id', 'ILIKE', "%{$s}%")
                    ->orWhere('serial_number', 'ILIKE', "%{$s}%")
                    ->orWhere('room_name', 'ILIKE', "%{$s}%")
                    // IMPORTANT: use correct relation name (likely "room")
                    ->orWhereHas('room', function ($rq) use ($s) {
                        $rq->where('room_type', 'ILIKE', "%{$s}%");
                    })
                    // Optional: device location search
                    ->orWhereHas('device', function ($dq) use ($s) {
                        $dq->where('location', 'ILIKE', "%{$s}%");
                    });
            });
        });

        // Date range (safe)
        $model->when($request->filled('date_from'), function ($q) use ($request) {
            $q->whereDate('alarm_start_datetime', '>=', $request->date_from);

            if ($request->filled('date_to')) {
                $q->whereDate('alarm_start_datetime', '<=', $request->date_to);
            }
        });

        // Alarm status filter (keeping your semantics)
        if ($request->filled('alarm_status')) {
            if ($request->alarm_status === "true") {
                $model->where('sos_status', true);
            } elseif ($request->alarm_status === "false") {
                $model->where('sos_status', false);
            } elseif ($request->alarm_status === "acknowledged") {
                // Your "none" means acknowledged (active + responded)
                $model->whereNotNull('responded_datetime');
            }
        }


        $model->orderBy('alarm_start_datetime', 'desc');


        // $model->orderBy("updated_at", "DESC");

        if (request()->has('perPage') || request()->has('page') || $perpage) {
            return   $model->paginate($request->per_page ?? 20);
        } else {
            return $model->get();
        }
    }
    public function SosLogsReports(Request $request)
    {



        return   $this->getRecords($request, 20);
    }


    public function SosLogsPrintPdf(Request $request)
    {



        $report =  $this->getRecords($request);
        $company = Company::whereId($request->company_id)->with('contact:id,company_id,number')->first();

        $fileName = "SOS Reports List.pdf";


        return   Pdf::loadview("sos/sos-reports", ["request" => $request, "reports" => $report, "company" => $company])->setpaper("A4", "potrait")->stream($fileName);
    }
    public function SosLogsDownloadPdf(Request $request)
    {

        $report =  $this->getRecords($request);
        $company = Company::whereId($request->company_id)->with('contact:id,company_id,number')->first();

        $fileName = "SOS Reports List.pdf";


        return   Pdf::loadview("sos/sos-reports", ["request" => $request, "reports" => $report, "company" => $company])->setpaper("A4", "potrait")->download($fileName);
    }
    public function SosLogsDownloadCSV(Request $request)
    {

        $reports =   $this->getRecords($request);
        $fileName = "SOS Reports List.xlsx";

        return Excel::download((new SOSExcelReports($reports)), $fileName);
    }

    //-[[----------------------------------------------------------]]
    public function SOSMonitorStatistics(Request $request)
    {

        $date_from = $request->date_from;
        $date_to = $request->date_to;


        $companyId = (int) $request->company_id;
        $slaMinutes = 5;

        // total   calls
        $totalSOSCount = DeviceSosRoomLogs::where('company_id', $companyId)
            ->whereDate('alarm_start_datetime', ">=", $date_from . " 00:00:00")
            ->where('alarm_start_datetime', "<=", $date_to . " 24:00:00")



            ->when($request->filled('sosStatus'), function ($q) use ($request) {
                if ($request->sosStatus == "ON")
                    $q->where("sos_status", true);
                if ($request->sosStatus == "OFF")
                    $q->where("sos_status", false);
                if ($request->sosStatus == "PENDING")
                    $q->where("sos_status", true)->where("responded_datetime", "!=", null);
            })

            ->when($request->filled('roomType'), function ($q) use ($request) {
                $q->whereHas('room', function ($qr) use ($request) {
                    $qr->where('room_type', $request->roomType);
                });
            })




            ->count();


        $totalResolved = DeviceSosRoomLogs::where('company_id', $companyId)
            ->where('alarm_end_datetime', ">=", $date_from . " 00:00:00")
            ->where('alarm_end_datetime', "<=", $date_to . " 24:00:00")
            ->where('alarm_end_datetime', "!=", null)
            ->when($request->filled('sosStatus'), function ($q) use ($request) {
                if ($request->sosStatus == "ON")
                    $q->where("sos_status", true);
                if ($request->sosStatus == "OFF")
                    $q->where("sos_status", false);
                if ($request->sosStatus == "PENDING")
                    $q->where("sos_status", true)->where("responded_datetime", "!=", null);
            })
            ->when($request->filled('roomType'), function ($q) use ($request) {
                $q->whereHas('room', function ($qr) use ($request) {
                    $qr->where('room_type', $request->roomType);
                });
            })
            ->count();


        $totalSOSActive = DeviceSosRoomLogs::where('company_id', $companyId)

            ->where('alarm_end_datetime',   null)
            ->when($request->filled('sosStatus'), function ($q) use ($request) {
                if ($request->sosStatus == "ON")
                    $q->where("sos_status", true);
                if ($request->sosStatus == "OFF")
                    $q->where("sos_status", false);
                if ($request->sosStatus == "PENDING")
                    $q->where("sos_status", true)->where("responded_datetime", "!=", null);
            })
            ->when($request->filled('roomType'), function ($q) use ($request) {
                $q->whereHas('room', function ($qr) use ($request) {
                    $qr->where('room_type', $request->roomType);
                });
            })
            ->count();



        $averageMinutes = DeviceSosRoomLogs::where('company_id', $companyId)
            ->whereNotNull('response_in_minutes')
            ->when($request->filled('sosStatus'), function ($q) use ($request) {
                if ($request->sosStatus == "ON")
                    $q->where("sos_status", true);
                if ($request->sosStatus == "OFF")
                    $q->where("sos_status", false);
                if ($request->sosStatus == "PENDING")
                    $q->where("sos_status", true)->where("responded_datetime", "!=", null);
            })
            ->where('alarm_start_datetime', ">=", $date_from . " 00:00:00")
            ->where('alarm_end_datetime', "<=", $date_to . " 24:00:00")
            ->when($request->filled('roomType'), function ($q) use ($request) {
                $q->whereHas('room', function ($qr) use ($request) {
                    $qr->where('room_type', $request->roomType);
                });
            })
            ->avg('response_in_minutes');


        $totalDeviceOnline = Device::where('company_id', $companyId)
            ->where('status_id', 1)
            ->count();
        $totalDeviceOffline = Device::where('company_id', $companyId)
            ->where('status_id', 2)
            ->count();




        return response()->json([
            'totalSOSCount' => $totalSOSCount,   // e.g. 82.45
            'totalResolved'    => $totalResolved,
            'totalSOSActive'    => $totalSOSActive,
            'totalDeviceOnline'    => $totalDeviceOnline,
            'totalDeviceOffline'    => $totalDeviceOffline,




            'averageMinutes'    => round($averageMinutes, 2),




        ]);
    }

    public function sosHourlyReport(Request $request)
    {
        $from = $request->date_from;;
        $to =  $request->date_to;;


        $from = Carbon::parse($from)->startOfDay();
        $to   = Carbon::parse($to)->endOfDay();



        $query = DB::table('device_sos_room_logs as l')
            ->join('device_sos_rooms as r', 'r.id', '=', 'l.device_sos_room_table_id')
            ->whereBetween('l.alarm_start_datetime', [$from, $to])


            ->when($request->filled('sosStatus'), function ($q) use ($request) {
                if ($request->sosStatus == "ON")
                    $q->where("sos_status", true);
                if ($request->sosStatus == "OFF")
                    $q->where("sos_status", false);
                if ($request->sosStatus == "PENDING")
                    $q->where("sos_status", true)->where("responded_datetime", "!=", null);
            })

            ->when($request->filled('roomType'), function ($q) use ($request) {
                $q->whereHas('room', function ($qr) use ($request) {
                    $qr->where('room_type', $request->roomType);
                });
            });



        if ($request->company_id) {
            $query->where('l.company_id', $request->company_id);
        }

        // OPTIONAL: count only SOS ON
        // $query->where('l.sos_status', true);

        $hourExpr = "EXTRACT(HOUR FROM l.alarm_start_datetime)";

        $rows = $query
            ->selectRaw("
            r.room_type AS room_type,
            $hourExpr   AS hour,
            COUNT(*)    AS total
        ")

            ->where("r.room_type", "!=", null)
            ->groupBy('r.room_type', DB::raw($hourExpr))
            ->orderBy('r.room_type')
            ->orderBy(DB::raw($hourExpr))
            ->get();

        /*
     * Categories: 0 → 23 (plain integers / strings)
     */
        $categories = array_map('strval', range(0, 23));

        /*
     * Collect unique room types
     */
        $roomTypes = collect($rows)->pluck('room_type')->unique()->values();

        /*
     * Initialize each room_type with 24 zeros
     */
        $seriesMap = [];
        foreach ($roomTypes as $rt) {
            $seriesMap[$rt] = array_fill(0, 24, 0);
        }

        /*
     * Fill data
     */
        foreach ($rows as $row) {
            $h = (int) $row->hour; // 0–23
            if ($h >= 0 && $h <= 23) {
                $seriesMap[$row->room_type][$h] = (int) $row->total;
            }
        }

        /*
     * Convert to ApexCharts format
     */
        $series = [];
        foreach ($seriesMap as $roomType => $data) {
            $series[] = [
                'name' => $roomType,
                'data' => $data,
            ];
        }

        return response()->json([
            'categories' => $categories, // ["0","1","2",...,"23"]
            'series'     => $series,
        ]);
    }

    public function roomTypes(Request $request)
    {

        return ["room" => "Room", "toilet" => "Toilet", "toilet-ph" => "Toilet For Disabled"];
    }
}
