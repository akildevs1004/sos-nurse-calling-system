{{-- resources/views/sos/sos-reports-list-clean.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>SOS Reports</title>

    <style>
        @page {
            margin: 20px 20px 20px 20px;
        }

        /* body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #0f172a;
        } */

        /* ===== Header helpers (if you keep your include) ===== */
        .clearfix {
            clear: both;
        }

        /* ===== Clean table like your screenshot ===== */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .report-table thead th {
            text-align: left;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #64748b;
            padding: 10px 10px;
            border-bottom: 2px solid #e2e8f0;
            background: #ffffff;
        }

        .report-table tbody td {
            padding: 12px 10px;
            border-bottom: 1px solid #eef2f7;
            vertical-align: middle;
        }

        .report-table tbody tr:last-child td {
            border-bottom: 0;
        }

        /* alternate subtle rows */
        .report-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        /* Column sizing similar to screenshot */
        .col-id {

            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: 10px;
            color: #334155;
            white-space: nowrap;
        }

        .col-time {

            white-space: nowrap;
            color: #0f172a;
        }

        .col-duration {

            white-space: nowrap;
            color: #334155;
        }

        .col-status {

            white-space: nowrap;
        }

        .col-staff {

            white-space: nowrap;
        }

        .location-strong {
            font-weight: 800;
            color: #0f172a;
        }

        .location-muted {
            display: block;
            margin-top: 2px;
            font-size: 10px;
            color: #64748b;
        }

        /* Pills like screenshot */
        .pill {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 7px;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: .04em;
            text-transform: uppercase;
            border: 1px solid transparent;
        }

        .pill-pending {
            background: #fee2e2;
            color: #b91c1c;
            border-color: #fecaca;
        }

        .pill-ack {
            background: #fef3c7;
            color: #92400e;
            border-color: #fde68a;
        }

        .pill-resolved {
            background: #d1fae5;
            color: #047857;
            border-color: #a7f3d0;
        }

        /* Duration highlight for short/critical */
        .duration-critical {
            color: #dc2626;
            font-weight: 900;
        }

        /* Unassigned style */
        .staff-unassigned {
            color: #94a3b8;
            font-style: italic;
        }

        /* Footer (optional) */
        .footer {
            position: fixed;
            bottom: 6px;
            left: 0;
            right: 0;
            font-size: 9px;
            color: #94a3b8;
            text-align: right;
        }

        .footer span {
            padding-right: 6px;
        }
    </style>
</head>

<body>

    @php
        // ===== Optional helper functions (kept minimal) =====
        if (!function_exists('fmtTime')) {
            function fmtTime($dt)
            {
                if (!$dt) {
                    return '--';
                }
                try {
                    return \Carbon\Carbon::parse($dt)->format('h:i A d-m-Y');
                } catch (\Throwable $e) {
                    return '--';
                }
            }
        }

        if (!function_exists('fmtDuration')) {
            // expects minutes (int/float) or seconds (int) based on what you pass
            function fmtDuration($seconds)
            {
                if ($seconds === null || $seconds === '') {
                    return '--';
                }
                $seconds = (int) $seconds;
                if ($seconds < 60) {
                    return $seconds . 'm';
                }
                $m = intdiv($seconds, 60);
                $s = $seconds % 60;
                return $m . 'h ' . $s . 'm';
            }
        }

        // Titles (same logic you used)
        $title2 = '';
        if (!empty($request?->date_from) && !empty($request?->date_to)) {
            try {
                $fromDate = \Carbon\Carbon::parse($request->date_from)->format('M j, Y');
                $toDate = \Carbon\Carbon::parse($request->date_to)->format('M j, Y');
                $title2 = "From $fromDate to $toDate";
            } catch (\Throwable $e) {
                $title2 = '';
            }
        }
        $title1 = 'SOS Reports';
    @endphp

    {{-- Header --}}
    <header>
        @include('header', [
            'company' => $company,
            'title1' => $title1,
            'title2' => $title2,
            'request' => $request,
        ])
    </header>

    {{-- Footer --}}
    <footer>
        @include('footer', [
            'company' => $company,
        ])
    </footer>

    <main>
        <div class="clearfix"></div>

        {{-- ===== Clean SOS table like screenshot ===== --}}
        <table class="report-table">
            <thead>
                <tr>
                    <th class="col-id">#</th>
                    {{-- <th class="col-id">SOS ID</th> --}}
                    <th>Location</th>
                    <th class="col-time">Time</th>
                    <th class="col-duration">Duration</th>
                    <th class="col-status">Status</th>
                    <th class="col-status">Acknowledgement</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($reports as $index => $r)
                    @php
                        /**
                         * Map your data to the new columns.
                         * Adjust these fields if your model uses different names.
                         */

                        // SOS ID shown like "#24-1042" (use any identifier you have)
                        $sosId = $r->sos_id ?? ($r->id ?? ($r->alarm_id ?? '--'));

                        // Location: room + device location
                        $room = $r->room_name ?? ($r->room?->room_name ?? '--');
                        $loc = $r->device?->location ?? ($r->location ?? '');

                        // Time: alarm_start_datetime as in your old table
                        $time = fmtTime($r->alarm_start_datetime ?? null);

                        /**
                         * Duration:
                         * - If you have duration seconds, use it directly.
                         * - Otherwise compute from start/end.
                         */
                        $durationSeconds = null;

                        // If you already have a numeric duration:
                        if (isset($r->duration_seconds)) {
                            $durationSeconds = (int) $r->duration_seconds;
                        } elseif (!empty($r->alarm_start_datetime) && !empty($r->alarm_end_datetime)) {
                            try {
                                $durationSeconds = \Carbon\Carbon::parse($r->alarm_start_datetime)->diffInSeconds(
                                    \Carbon\Carbon::parse($r->alarm_end_datetime),
                                );
                            } catch (\Throwable $e) {
                                $durationSeconds = null;
                            }
                        }

                        // Status logic (your logic, but mapped to 3 labels like screenshot)
                        // PENDING / ACK. / RESOLVED
                        $status = $r->sos_status ? ($r->responded_datetime ? 'ACK.' : 'PENDING') : 'RESOLVED';

                        $pillClass =
                            $status === 'PENDING'
                                ? 'pill-pending'
                                : ($status === 'ACK.'
                                    ? 'pill-ack'
                                    : 'pill-resolved');

                        $ackstatus = $r->responded_datetime ? 'ACK.' : '';

                        $pillClass =
                            $status === 'PENDING'
                                ? 'pill-pending'
                                : ($status === 'ACK.'
                                    ? 'pill-ack'
                                    : 'pill-resolved');

                        $ackpillClass =
                            $status === 'PENDING'
                                ? 'pill-pending'
                                : ($status === 'ACK.'
                                    ? 'pill-ack'
                                    : 'pill-resolved');

                        // Staff name
                        $staff = $r->staff_name ?? ($r->staff ?? ($r->responded_by ?? (null ?? 'Unassigned')));

                        $staffClass = strtolower((string) $staff) === 'unassigned' ? 'staff-unassigned' : '';

                        // Duration highlight rule (example: < 60s show red like screenshot 45s)
                        $durationClass = $durationSeconds !== null && $durationSeconds > 60 ? 'duration-critical' : '';
                    @endphp

                    <tr>
                        <td class="col-id">
                            <strong>#{{ $index }}</strong>
                        </td>
                        {{-- <td class="col-id">
                            <strong> {{ $sosId }}</strong>
                        </td> --}}

                        <td>
                            <span class="location-strong">{{ $room }}</span>
                            @if (!empty($loc))
                                <span class="location-muted">{{ $loc }}</span>
                            @endif
                        </td>

                        <td class="col-time">{{ $time }}</td>

                        <td class="col-duration">
                            <span class="{{ $durationClass }}">{{ fmtDuration($durationSeconds) }}</span>
                        </td>

                        <td class="col-status">
                            <span class="pill {{ $pillClass }}">{{ $status }}</span>
                        </td>
                        <td class="col-status">
                            @if ($ackstatus)
                                <span class="pill pill-ack">{{ $ackstatus }}</span>
                            @else
                                --
                            @endif

                        </td>


                    </tr>
                @endforeach
            </tbody>
        </table>
    </main>

    <div class="footer">
        <span>© {{ date('Y') }} CardioSOS System</span>
    </div>

</body>

</html>
