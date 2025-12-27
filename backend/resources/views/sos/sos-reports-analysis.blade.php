{{-- resources/views/sos/sos-reports-analysis.blade.php --}}
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>SOS Call Report Analysis</title>

    <style>
        @page {
            margin: 14mm 14mm 14mm 14mm;
        }

        /* body {
            font-family: Arial, sans-serif;
            color: #0f172a;
            font-size: 12px;
        }

        .page {
            page-break-after: always;
            padding-bottom: 18mm;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .muted {
            color: #64748b;
        }

        .primary {
            color: #137fec;
        }

        .border-b {
            border-bottom: 1px solid #e2e8f0;
        }

        .card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px;
        }

        .card-title {
            font-size: 11px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .h1 {
            font-size: 40px;
            font-weight: 800;
            line-height: 1.05;
            margin: 0;
        }

        .h2 {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
        }

        .h3 {
            font-size: 14px;
            font-weight: 700;
            margin: 0;
        }

        .big {
            font-size: 22px;
            font-weight: 800;
            margin: 0;
        }

        .small {
            font-size: 11px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            padding: 10px 8px;
            border-bottom: 2px solid #e2e8f0;
        }

        td {
            padding: 10px 8px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }

        tr.alt td {
            background: #f8fafc;
        }

        .pill {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            border: 1px solid transparent;
        }

        .pill-red {
            background: #fee2e2;
            color: #b91c1c;
            border-color: #fecaca;
        }

        .pill-amber {
            background: #fef3c7;
            color: #92400e;
            border-color: #fde68a;
        }

        .pill-green {
            background: #d1fae5;
            color: #047857;
            border-color: #a7f3d0;
        }

        .section-title {
            font-size: 13px;
            font-weight: 800;
            margin: 0 0 10px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
        }


        .bar-wrap {
            height: 140px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px;
        }

        .bars {
            height: 110px;
        }

        .bars td {
            border: 0;
            padding: 0 2px;
            vertical-align: bottom;
        }

        .bar {
            width: 100%;
            background: #cfe6ff;
            border-radius: 4px 4px 0 0;
        }

        .bar>div {
            width: 100%;
            background: #137fec;
            border-radius: 4px 4px 0 0;
        }

        .progress {
            height: 8px;
            background: #f1f5f9;
            border-radius: 999px;
            overflow: hidden;
        }

        .progress>div {
            height: 8px;
            background: #137fec;
        }

        .note {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px;
            color: #475569;
        }

        .footer {
            position: fixed;
            bottom: 10mm;
            left: 14mm;
            right: 14mm;
            font-size: 10px;
            color: #94a3b8;
        }

        .footer table {
            width: 100%;
            border-collapse: collapse;
        }


        .chart-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px;
        }

        .chart-cell {
            width: 50%;
            vertical-align: top;
        }

        .chart-card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 5px;
            height: 240px;
        }

        .chart-title {
            font-size: 12px;
            font-weight: 800;
            margin: 0 0 10px 0;
            color: #0f172a;
        }

        .chart-img {
            display: block;

            max-width: 100%;

            text-align: center
        }

        */
    </style>
</head>

<body>

    @php
        // Icon paths (local)

        // Safe defaults
        $unitName = $unitName ?? 'Unit';
        $reportPeriod = $reportPeriod ?? '-';
        $generatedOn = $generatedOn ?? '-';
        $totalCalls = $totalCalls ?? 0;
        $avgResponse = $avgResponse ?? '00:00';
        $resolvedPct = $resolvedPct ?? '0%';
        $topLocation = $topLocation ?? '-';
        $topLocationCalls = $topLocationCalls ?? 0;

        // Charts defaults (if you still use any HTML bars)
        $bars = $hourlyBars ?? [15, 10, 5, 8, 25, 45, 65, 85, 55, 40, 30, 20];
        $maxBars = max($bars) ?: 1;

        $trend = $trend ?? ['Mon' => 72, 'Tue' => 68, 'Wed' => 64, 'Thu' => 66, 'Fri' => 60, 'Sat' => 56, 'Sun' => 52];

        $statusResolved = $statusResolved ?? '0%';
        $statusAck = $statusAck ?? '0%';
        $statusPending = $statusPending ?? '0%';

        $sources = $sourceBars ?? [
            ['label' => 'Patient Rooms', 'value' => 0, 'pct' => 0],
            ['label' => 'Disabled Toilets', 'value' => 0, 'pct' => 0],
            ['label' => 'Common Areas', 'value' => 0, 'pct' => 0],
        ];

        $periodFrom = $periodFrom ?? '';
        $periodTo = $periodTo ?? '';

        // ===== IMPORTANT: wkhtmltopdf local images must use file:// + public_path =====
        $chartHourlySos = 'file://' . public_path('storage/reports/charts/hourly_sos.png');
        $chartResponseHourly = 'file://' . public_path('storage/reports/charts/response_hourly_sos.png');
        $chartStatusDonut = 'file://' . public_path('storage/reports/charts/sos_status_donut.png');

        // 4th chart: change filename to your actual chart file
        // Example: room_type_donut.png / room_type_bars.png / top_locations.png etc.
        // $chartRoomType = 'file://' . public_path('storage/reports/charts/room_type_donut.png');

    @endphp

    {{-- PAGE 1 --}}

    @include('sos.sos-report-analysis-page1')

    {{-- PAGE 2 --}}

    @include('sos.sos-report-analysis-page2')

    {{-- PAGE 3 --}}
    <div class="page">


        @include('sos.sos-reports-logs-with-color')


        <div class="note" style="margin-top:16px;">
            <strong>Note:</strong>
            This report includes all SOS calls triggered
            @if ($periodFrom && $periodTo)
                between {{ $periodFrom }} and {{ $periodTo }}.
            @else
                within the selected report period.
            @endif
            Average response time is calculated based on the interval between “Call Time” and “Ack. Time”.
        </div>

        <div class="footer">
            <table>
                <tr>
                    <td>© {{ date('Y') }} INTELLIGENT NURSE CALL SYSTEM </td>
                    <td style="text-align:right;"> </td>
                </tr>
            </table>
        </div>
    </div>

</body>

</html>
