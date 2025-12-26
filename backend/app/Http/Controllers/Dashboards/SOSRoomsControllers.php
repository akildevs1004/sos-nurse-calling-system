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
use Illuminate\Support\Facades\Storage;
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
    private function roomTypeLabels(): array
    {
        return [
            "room"      => "Room",
            "toilet"    => "Toilet",
            "toilet-ph" => "Toilet For Disabled",
        ];
    }
    public function sosHourlyReport(Request $request)
    {



        $companyId = (int) $request->company_id;

        // Date range
        $from = Carbon::parse($request->date_from)->startOfDay();
        $to   = Carbon::parse($request->date_to)->endOfDay();

        // Labels
        $labels = $this->roomTypeLabels();

        // Base query
        $query = DB::table('device_sos_room_logs as l')
            ->join('device_sos_rooms as r', 'r.id', '=', 'l.device_sos_room_table_id')
            ->whereBetween('l.alarm_start_datetime', [$from, $to])
            ->whereNotNull('r.room_type');

        if ($companyId) {
            $query->where('l.company_id', $companyId);
        }

        // SOS status filter
        $query->when($request->filled('sosStatus'), function ($q) use ($request) {
            if ($request->sosStatus === "ON") {
                $q->where('l.sos_status', true);
            } elseif ($request->sosStatus === "OFF") {
                $q->where('l.sos_status', false);
            } elseif ($request->sosStatus === "PENDING") {
                // ON but not responded
                $q->where('l.sos_status', true)
                    ->whereNull('l.responded_datetime');
            } elseif ($request->sosStatus === "RESPONDED") {
                $q->whereNotNull('l.responded_datetime');
            }
        });

        // Room type filter
        $query->when($request->filled('roomType'), function ($q) use ($request) {
            $q->where('r.room_type', $request->roomType);
        });

        // DB-specific hour extraction
        $driver = DB::getDriverName();
        $hourExpr = ($driver === 'pgsql')
            ? "EXTRACT(HOUR FROM l.alarm_start_datetime)"
            : "HOUR(l.alarm_start_datetime)";

        // Aggregate
        $rows = $query
            ->selectRaw("
                r.room_type AS room_type,
                $hourExpr   AS hour,
                COUNT(*)    AS total
            ")
            ->groupBy('r.room_type', DB::raw($hourExpr))
            ->orderBy('r.room_type')
            ->orderBy(DB::raw($hourExpr))
            ->get();

        // X-axis categories (0–23)
        $categories = array_map('strval', range(0, 23));

        // Use all known room types so zero-value series still appear
        $roomTypes = array_keys($labels);

        // Initialize series map
        $seriesMap = [];
        foreach ($roomTypes as $rt) {
            $seriesMap[$rt] = array_fill(0, 24, 0);
        }

        // Fill data
        foreach ($rows as $row) {
            $rt = (string) $row->room_type;
            $h  = (int) $row->hour;

            if ($h >= 0 && $h <= 23) {
                // Include unexpected room types safely
                if (!isset($seriesMap[$rt])) {
                    $seriesMap[$rt] = array_fill(0, 24, 0);
                }
                $seriesMap[$rt][$h] = (int) $row->total;
            }
        }

        // Build ApexCharts series
        $series = [];
        foreach ($seriesMap as $roomType => $data) {
            $series[] = [
                'name' => $labels[$roomType] ?? ucfirst(str_replace('-', ' ', $roomType)),
                'data' => $data,
            ];
        }

        return response()->json([
            'categories' => $categories,
            'series'     => $series,
            'meta'       => [
                'company_id' => $companyId,
                'date_from'  => $from->toDateTimeString(),
                'date_to'    => $to->toDateTimeString(),
                'db_driver'  => $driver,
            ],
        ]);
    }
    public function roomTypesPercentages(Request $request)
    {
        $companyId = (int) $request->company_id;

        $from = Carbon::parse($request->date_from)->startOfDay();
        $to   = Carbon::parse($request->date_to)->endOfDay();

        $labels = $this->roomTypeLabels();

        /*
         * Base query
         */
        $base = DB::table('device_sos_room_logs as l')
            ->join('device_sos_rooms as r', 'r.id', '=', 'l.device_sos_room_table_id')
            ->whereBetween('l.alarm_start_datetime', [$from, $to])
            ->whereNotNull('r.room_type');

        if ($companyId) {
            $base->where('l.company_id', $companyId);
        }

        /*
         * SOS status filter
         */
        $base->when($request->filled('sosStatus'), function ($q) use ($request) {
            if ($request->sosStatus === 'ON') {
                $q->where('l.sos_status', true);
            } elseif ($request->sosStatus === 'OFF') {
                $q->where('l.sos_status', false);
            } elseif ($request->sosStatus === 'PENDING') {
                // ON but not responded yet
                $q->where('l.sos_status', true)
                    ->whereNull('l.responded_datetime');
            } elseif ($request->sosStatus === 'RESPONDED') {
                $q->whereNotNull('l.responded_datetime');
            }
        });

        /*
         * Total SOS count (denominator)
         */
        $totalSOS = (clone $base)->count();

        /*
         * Count per room type
         */
        $rows = (clone $base)
            ->selectRaw('r.room_type AS room_type, COUNT(*) AS total')
            ->groupBy('r.room_type')
            ->orderBy('r.room_type')
            ->get();

        /*
         * Build result with percentage
         */
        $items = [];
        foreach ($rows as $row) {
            $count = (int) $row->total;
            $percentage = $totalSOS > 0
                ? round(($count / $totalSOS) * 100, 2)
                : 0;

            $items[] = [
                'room_type' => (string) $row->room_type,
                'label'     => $labels[$row->room_type]
                    ?? ucfirst(str_replace('-', ' ', $row->room_type)),
                'count'     => $count,
                'percentage' => $percentage,
            ];
        }

        /*
         * OPTIONAL: include room types with zero values
         */
        if ($request->boolean('includeZeroTypes', true)) {
            $indexed = collect($items)->keyBy('room_type')->all();
            $items = [];

            foreach ($labels as $key => $label) {
                $count = $indexed[$key]['count'] ?? 0;
                $percentage = $totalSOS > 0
                    ? round(($count / $totalSOS) * 100, 2)
                    : 0;

                $items[] = [
                    'room_type' => $key,
                    'label'     => $label,
                    'count'     => $count,
                    'percentage' => $percentage,
                ];
            }
        }

        return response()->json([
            'total_sos' => (int) $totalSOS,
            'items'     => $items,
            'meta'      => [
                'company_id' => $companyId,
                'date_from'  => $from->toDateTimeString(),
                'date_to'    => $to->toDateTimeString(),
            ],
        ]);
    }
    public function roomTypes(Request $request)
    {

        return $this->roomTypeLabels();;
    }


    public function SosLogsAnalyticsPdf(Request $request)
    {



        $report  = $this->getRecords($request);

        $company = Company::query()
            ->whereKey($request->company_id)
            ->with('contact:id,company_id,number')
            ->first();

        $fileName = 'SOS Reports Analysis.pdf';
        $avgResponseMinutes = collect($report)
            ->whereNotNull('response_in_minutes')
            ->avg('response_in_minutes') ?? 0;

        // Optional: round to 2 decimals
        $avgResponseMinutes = round($avgResponseMinutes);
        $hours = floor($avgResponseMinutes / 60);
        $mins  = $avgResponseMinutes % 60;

        $time = sprintf('%02dh:%02dm', $hours, $mins);

        $items = collect($report);
        //-----------------
        // Total SOS in the report
        $total = $items->count();

        $closed = $items->filter(fn($r) => !empty($r->alarm_end_datetime))->count();

        $closedPct = $total > 0 ? round(($closed / $total) * 100, 1) : 0;
        ///----------------
        $roomCounts = [];

        // Count SOS per room
        foreach ($report as $r) {

            // Only valid SOS
            if (empty($r->alarm_start_datetime) || empty($r->room_id)) {
                continue;
            }

            $roomId   = $r->room_id;
            $roomName = $r->room_name ?? '-';

            // Initialize if not exists
            if (!isset($roomCounts[$roomId])) {
                $roomCounts[$roomId] = [
                    'room_id'   => $roomId,
                    'room_name' => $roomName,
                    'count'     => 0,
                ];
            }

            // Increment count
            $roomCounts[$roomId]['count']++;
        }
        $topRoom = null;

        foreach ($roomCounts as $room) {
            if ($topRoom === null || $room['count'] > $topRoom['count']) {
                $topRoom = $room;
            }
        }
        $topRoomName  = $topRoom['room_name'] ?? '-';
        $topRoomId    = $topRoom['room_id'] ?? null;
        $topRoomCount = $topRoom['count'] ?? 0;
        $reportPeriod = "All Time";

        if ($request->filled('date_from'))

            $reportPeriod = $request->date_from . ' To ' . $request->date_to;

        $data = [
            'unitName'          => 'All Rooms',
            'reportPeriod'      =>  $reportPeriod,
            'generatedOn'       => date("Y-m-d H:i:s"),

            'totalCalls'        => count($report),
            'avgResponse'       => $time,
            'resolvedPct'       => $closedPct . '%',
            'topLocation'       => $topRoomName . "(" . $topRoomId . ")",
            'topLocationCalls'  => $topRoomCount . ' calls this period',

            // Pass raw records; map in blade OR map here (recommended below)
            'callLogs'          => $report,
            'company'           => $company,


        ];

        // 0–23 hourly counts (must be 24 values)
        $data['hourLabels'] = range(0, 23);
        $data['isPdf'] = true;

        $data['hourValues'] = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24]; // array of 24 integers




        // $chartPath = $request->query('chart'); // "reports/charts/hourly_sos.png"

        //geenrate image
        //image is generated //path :storage/reports/charts/hourly_sos.png
        $this->chartRender($request);


        $chartPath = "reports/charts/hourly_sos.png";

        $data['chartImage'] =  public_path('storage/' . $chartPath);










        $pdf = PDF::loadView('sos.sos-reports-analysis', $data)
            ->setOption('enable-local-file-access', true)
            ->setOption('encoding', 'utf-8')
            ->setOption('page-size', 'A4')
            ->setOption('orientation', 'Portrait')
            ->setOption('margin-top', 14)
            ->setOption('margin-right', 14)
            ->setOption('margin-bottom', 14)
            ->setOption('margin-left', 14)
            ->setOption('print-media-type', true)
            ->setOption('enable-local-file-access', true);











        return $pdf->stream($fileName);
    }
    public function chartRender(Request $request)
    {
        // Example data: you should compute from your report
        $hourLabels = range(0, 23);
        $hourValues = [1, 2, 3, 4, 5, 6, 7, 8, 9, 0, 11, 2, 3, 4, 5, 6, 7, 8, 9, 0, 1, 2, 3];

        // TODO: fill $hourValues from your DB query results

        return view('sos.sos-chart-render', [
            'hourLabels' => $hourLabels,
            'hourValues' => $hourValues,
            'nextUrl'    => route('sos.report.pdf', $request->query()), // redirect after uploa
        ]);
    }

    public function storeChart(Request $request)
    {

        //return $request->file('chart');
        if (!$request->hasFile('chart')) {
            return response()->json(['message' => 'No chart received'], 422);
        }

        $file = $request->file('chart');

        if (!$file->isValid()) {
            return response()->json(['message' => 'Invalid file'], 422);
        }

        // Store into public disk
        $path = $file->storeAs('reports/charts', 'hourly_sos.png', 'public');

        return response()->json([
            'ok' => true,
            //'pdf_url' => route('geenratecharts', ['chart' => $path]),
        ]);
    }
}
