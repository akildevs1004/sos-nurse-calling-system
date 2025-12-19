<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>SOS Reports</title>

    <style>
        @page {
            margin: 18px 18px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            background: #ffffff;
            color: #111827;
        }

        /* ===== HEADER ===== */
        .header {
            margin-bottom: 14px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 8px;
        }

        .company {
            font-size: 16px;
            font-weight: 700;
        }

        .subtitle {
            font-size: 11px;
            color: #6b7280;
            margin-top: 2px;
        }

        /* ===== TABLE ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        thead th {
            /* background: #f9fafb; */
            color: #6b7280;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        tbody td {
            padding: 9px 8px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        tbody tr:nth-child(even) {
            /* background: #f9fafb; */
        }

        /* ===== DATE / TIME ===== */
        .time {
            font-weight: 700;
            font-size: 12px;
        }

        .date {
            font-size: 10px;
            color: #6b7280;
            margin-top: 2px;
        }

        /* ===== ROOM / LOCATION ===== */
        .room {
            font-weight: 700;
        }

        .location {
            font-size: 10px;
            color: #6b7280;
            margin-top: 2px;
        }

        /* ===== STATUS CHIPS ===== */
        .chip {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.04em;
            border: 1px solid;
            background: #ffffff;
        }

        .pending {
            color: #dc2626;
            border-color: #dc2626;
        }

        .resolved {
            color: #059669;
            border-color: #059669;
        }

        .ack {
            color: #d97706;
            border-color: #d97706;
        }

        /* ===== RESPONSE TIME ===== */
        .resp {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 10px;
            border: 1px solid;
            background: #ffffff;
        }

        .resp-ok {
            color: #059669;
            border-color: #059669;
        }

        .resp-warn {
            color: #ea580c;
            border-color: #ea580c;
        }

        .center {
            text-align: center;
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <div class="header">
        <div class="company">{{ $company->name }}</div>
        <div class="subtitle">
            SOS Alarm Reports
            @if ($request->date_from)
                | {{ $request->date_from }}
                @if ($request->date_to)
                    – {{ $request->date_to }}
                @endif
            @endif
        </div>
    </div>

    <!-- TABLE -->
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Alarm Start</th>
                <th>Alarm End</th>
                <th>Location / Room</th>
                <th>Room Type</th>
                <th class="center">Response (HH:MM)</th>
                <th>Status</th>
                <th>ACK</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reports as $i => $r)
                @php
                    $status = $r->sos_status ? ($r->responded_datetime ? 'ACKNOWLEDGED' : 'PENDING') : 'RESOLVED';

                    $statusClass =
                        $status === 'PENDING' ? 'pending' : ($status === 'ACKNOWLEDGED' ? 'ack' : 'resolved');

                    $respMin = $r->response_in_minutes;
                    $respClass = $respMin !== null && $respMin > 5 ? 'resp-warn' : 'resp-ok';

                    $roomType = $r->room?->room_type ? strtoupper(str_replace('-PD', '', $r->room->room_type)) : '--';
                @endphp

                <tr>
                    <td>{{ $i + 1 }}</td>

                    <td>
                        @if ($r->alarm_start_datetime)
                            <div class="time">{{ \Carbon\Carbon::parse($r->alarm_start_datetime)->format('H:i') }}
                            </div>
                            <div class="date">{{ \Carbon\Carbon::parse($r->alarm_start_datetime)->format('M d, Y') }}
                            </div>
                        @else
                            --
                        @endif
                    </td>

                    <td>
                        @if ($r->alarm_end_datetime)
                            <div class="time">{{ \Carbon\Carbon::parse($r->alarm_end_datetime)->format('H:i') }}</div>
                            <div class="date">{{ \Carbon\Carbon::parse($r->alarm_end_datetime)->format('M d, Y') }}
                            </div>
                        @else
                            --
                        @endif
                    </td>

                    <td>
                        <div class="room">{{ $r->room_name ?? '--' }}</div>
                        <div class="location">{{ $r->device?->location ?? '--' }}</div>
                    </td>

                    <td>{{ $roomType }}</td>

                    <td class="center">
                        @if ($respMin !== null)
                            <span class="resp {{ $respClass }}">
                                {{ gmdate('H:i', $respMin * 60) }}
                            </span>
                        @else
                            --
                        @endif
                    </td>

                    <td>
                        <span class="chip {{ $statusClass }}">
                            {{ $status }}
                        </span>
                    </td>

                    <td>
                        @if ($r->responded_datetime)
                            <div class="time">{{ \Carbon\Carbon::parse($r->responded_datetime)->format('H:i') }}
                            </div>
                            <div class="date">{{ \Carbon\Carbon::parse($r->responded_datetime)->format('M d, Y') }}
                            </div>
                        @else
                            --
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>

</html>
