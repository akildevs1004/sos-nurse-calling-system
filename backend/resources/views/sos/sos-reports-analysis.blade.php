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

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
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

        /* PDF-safe simple charts */
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
    </style>
</head>

<body>

    @php
        // Icon paths (local)
        $icon1 = 'file://' . public_path('pdf_icons/pdfreporticon1.png');
        $icon2 = 'file://' . public_path('pdf_icons/pdfreporticon2.png');

        // Safe defaults
        $unitName = $unitName ?? 'Unit';
        $reportPeriod = $reportPeriod ?? '-';
        $generatedOn = $generatedOn ?? '-';
        $totalCalls = $totalCalls ?? 0;
        $avgResponse = $avgResponse ?? '00:00';
        $resolvedPct = $resolvedPct ?? '0%';
        $topLocation = $topLocation ?? '-';
        $topLocationCalls = $topLocationCalls ?? 0;

        // Charts defaults
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
    @endphp

    {{-- PAGE 1 --}}
    <div class="page">
        <div style="margin-top: 10px;">
            <table>
                <tr>
                    <td style="width:80px; vertical-align: top; border-bottom:0;">
                        <img src="{{ $icon1 }}" style="width:70px; max-width:70px;">
                    </td>

                    <td style="vertical-align: top; border-bottom:0;">
                        <div style="border-left:4px solid #137fec; padding-left:14px;">
                            <p class="h1">SOS Call Report</p>
                            <p class="muted" style="font-size:16px; margin:10px 0 0 0;">{{ $unitName }}</p>
                        </div>

                        <div style="margin-top:40px;">
                            <table>
                                <tr>
                                    <td class="muted" style="width:220px; border-bottom:0; font-size:18px;">
                                        <strong>REPORT PERIOD</strong>
                                    </td>
                                    <td style="border-bottom:0; font-size:18px;">
                                        <strong>{{ $reportPeriod }}</strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="muted" style="border-bottom:0; font-size:18px;">
                                        <strong>GENERATED ON</strong>
                                    </td>
                                    <td style="border-bottom:0; font-size:18px;">
                                        <strong>{{ $generatedOn }}</strong>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="card" style="font-size:16px; margin-top:40px;">
                            <div class="h3" style="margin-bottom:12px;">
                                <img src="{{ $icon2 }}"
                                    style="vertical-align:middle; width:18px; margin-right:6px;">
                                Executive Summary
                            </div>

                            <table style="border-collapse: collapse;">
                                <tr>
                                    <td style="width:60%; vertical-align: top; padding-right:14px; border-bottom:0;">
                                        <div style="color:#475569; line-height:1.5;">
                                            During the report period, a total of
                                            <strong style="color:#b91c1c;">{{ $totalCalls }}</strong>
                                            SOS calls were registered, achieving an average response time of
                                            <strong>{{ $avgResponse }}</strong>.
                                            This report was generated on <strong>{{ $generatedOn }}</strong>,
                                            with <strong>{{ $topLocation }}</strong> identified as the highest SOS
                                            activity location
                                            ({{ $topLocationCalls }} calls).
                                        </div>
                                    </td>

                                    <td style="width:40%; vertical-align: top; border-bottom:0;">
                                        <div
                                            style="background:#f8fafc; border:1px solid #cfd3d8; border-radius:10px; padding:12px;">
                                            <div class="card-title" style="font-size:16px;">Key Metrics at a Glance
                                            </div>
                                            <table>
                                                <tr>
                                                    <td style="padding:6px 0; border:0;">Total Calls</td>
                                                    <td
                                                        style="padding:6px 0; border:0; text-align:right; font-weight:800;">
                                                        {{ $totalCalls }}</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2"
                                                        style="height:1px; background:#e2e8f0; padding:0; border:0;">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:6px 0; border:0;">Avg Response</td>
                                                    <td
                                                        style="padding:6px 0; border:0; text-align:right; font-weight:800;">
                                                        {{ $avgResponse }}</td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2"
                                                        style="height:1px; background:#e2e8f0; padding:0; border:0;">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding:6px 0; border:0;">Top Location</td>
                                                    <td
                                                        style="padding:6px 0; border:0; text-align:right; font-weight:800;">
                                                        {{ $topLocation }}</td>
                                                </tr>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- PAGE 2 --}}
    <div class="page">

        <table class="border-b" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
            <tr>
                <td style="padding-bottom:12px; border-bottom:0;">
                    <div style="font-size:18px; font-weight:800;">CardioSOS</div>
                    <div class="muted small" style="letter-spacing:0.08em; text-transform:uppercase;">Intelligent Nurse
                        Call System</div>
                </td>
                <td style="text-align:right; padding-bottom:12px; border-bottom:0;">
                    <div style="font-size:16px; font-weight:800;">SOS Call Report</div>
                    <div class="muted">Detailed Analytics</div>
                </td>
            </tr>
        </table>

        <table style="margin-bottom:24px;">
            <tr>
                <td style="width:25%; padding-right:10px; border-bottom:0;">
                    <div class="card">
                        <div class="card-title">Total Calls</div>
                        <p class="big">{{ $totalCalls }}</p>
                    </div>
                </td>
                <td style="width:25%; padding-right:10px; border-bottom:0;">
                    <div class="card">
                        <div class="card-title">Avg Response</div>
                        <p class="big">{{ $avgResponse }}</p>
                    </div>
                </td>
                <td style="width:25%; padding-right:10px; border-bottom:0;">
                    <div class="card">
                        <div class="card-title">Resolved</div>
                        <p class="big">{{ $resolvedPct }}</p>
                        <div class="progress" style="margin-top:10px;">
                            <div style="width: {{ rtrim($resolvedPct, '%') }}%;"></div>
                        </div>
                    </div>
                </td>
                <td style="width:25%; border-bottom:0;">
                    <div class="card">
                        <div class="card-title">Top Location</div>
                        <p style="font-size:16px; font-weight:800; margin:0;">{{ $topLocation }}</p>
                        <div class="small muted" style="margin-top:8px;">{{ $topLocationCalls }} calls</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="section-title">Data Visualization2</div>

        <table>

            <tr>
                <td>

                    @if (!empty($chartImage))
                        <div class="card" style="margin-top:16px;">
                            <div class="h3 mb-12">Hourly SOS Calls (0–23)</div>
                            <img src="file://{{ $chartImage }}"
                                style="width:100%; max-height:300px; object-fit:contain;">
                        </div>
                    @else
                        <div class="muted">Chart not available.</div>
                    @endif




                </td>
                <td>

                    @if (!empty($response_hourly_sosImage))
                        <div class="card" style="margin-top:16px;">
                            <div class="h3 mb-12">Response Hourly SOS Calls (0–23)</div>
                            <img src="file://{{ $response_hourly_sosImage }}"
                                style="width:100%; max-height:300px; object-fit:contain;">
                        </div>
                    @else
                        <div class="muted">Chart not available.</div>
                    @endif




                </td>
            </tr>
        </table>

        <table>
            <tr>
                <td style="width:50%; padding-right:10px; vertical-align: top; border-bottom:0;">
                    <div class="card">
                        <div class="h3" style="margin-bottom:12px;">Call Frequency (24h)</div>
                        <div class="bar-wrap">
                            <table class="bars">
                                <tr>
                                    @foreach ($bars as $v)
                                        <td>
                                            <div class="bar" style="height: {{ intval(($v / $maxBars) * 100) }}%;">
                                                <div style="height:100%;"></div>
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            </table>
                            <div class="muted small" style="margin-top:6px;">00 02 04 06 08 10 12 14 16 18 20 22</div>
                        </div>
                    </div>
                </td>

                <td style="width:50%; vertical-align: top; border-bottom:0;">
                    <div class="card">
                        <div class="h3" style="margin-bottom:12px;">Response Time Trends</div>
                        <div style="height:160px; border:1px solid #e2e8f0; border-radius:10px; padding:10px;">
                            <div class="muted small">Mon → Sun trend (simplified)</div>
                            <table style="margin-top:10px;">
                                <tr>
                                    @foreach ($trend as $d => $v)
                                        <td style="text-align:center; border:0;">
                                            <div style="font-weight:800;">{{ $d }}</div>
                                            <div class="muted">{{ $v }}s</div>
                                        </td>
                                    @endforeach
                                </tr>
                            </table>
                        </div>
                    </div>
                </td>
            </tr>

            <tr>
                <td style="width:50%; padding-right:10px; vertical-align: top; padding-top:10px; border-bottom:0;">
                    <div class="card">
                        <div class="h3" style="margin-bottom:12px;">Status Distribution</div>
                        <table>
                            <tr>
                                <td style="border:0;">Resolved</td>
                                <td style="border:0; text-align:right; font-weight:800;">{{ $statusResolved }}</td>
                            </tr>
                            <tr>
                                <td style="border:0;">Ack.</td>
                                <td style="border:0; text-align:right; font-weight:800;">{{ $statusAck }}</td>
                            </tr>
                            <tr>
                                <td style="border:0;">Pending</td>
                                <td style="border:0; text-align:right; font-weight:800;">{{ $statusPending }}</td>
                            </tr>
                        </table>
                    </div>
                </td>

                <td style="width:50%; vertical-align: top; padding-top:10px; border-bottom:0;">
                    <div class="card">
                        <div class="h3" style="margin-bottom:12px;">Source Distribution</div>

                        @foreach ($sources as $s)
                            <div style="margin-bottom:10px;">
                                <table>
                                    <tr>
                                        <td style="border:0; font-weight:700;">{{ $s['label'] }}</td>
                                        <td style="border:0; text-align:right;" class="muted">{{ $s['value'] }}
                                            calls</td>
                                    </tr>
                                </table>
                                <div class="progress">
                                    <div style="width: {{ (int) ($s['pct'] ?? 0) }}%;"></div>
                                </div>
                            </div>
                        @endforeach

                    </div>
                </td>
            </tr>
        </table>

        <div class="footer">
            <table>
                <tr>
                    <td>© {{ date('Y') }} CardioSOS System</td>
                    <td style="text-align:right;">Page 2 of 3</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- PAGE 3 --}}
    <div class="page">

        <table class="border-b" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
            <tr>
                <td style="padding-bottom:12px; border-bottom:0;">
                    <div style="font-weight:800;">CardioSOS Report</div>
                </td>
                <td class="muted small" style="text-align:right; padding-bottom:12px; border-bottom:0;">
                    CONTINUED
                </td>
            </tr>
        </table>

        <div class="h2" style="margin-bottom:12px;">Comprehensive Call Log</div>

        <table>
            <thead>
                <tr>
                    <th style="width:90px;">SOS ID</th>
                    <th>Location</th>
                    <th style="width:90px;">Time</th>
                    <th style="width:90px;">Duration</th>
                    <th style="width:90px;">Status</th>
                    <th style="width:120px;">Staff</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($callLogs ?? [] as $i => $row)
                    @php
                        // Expected row keys:
                        // id, location, time, duration, status, staff, duration_emphasis (bool)
                        $st = strtolower((string) ($row['status'] ?? ''));
                        $cls =
                            $st === 'pending'
                                ? 'pill-red'
                                : ($st === 'ack' || $st === 'ack.'
                                    ? 'pill-amber'
                                    : 'pill-green');
                    @endphp

                    <tr class="{{ $i % 2 ? 'alt' : '' }}">
                        <td style="font-family: monospace; font-size: 11px; color:#475569;">
                            <strong>{{ $row['id'] ?? '-' }}</strong>
                        </td>
                        <td><strong>{{ $row['location'] ?? '-' }}</strong></td>
                        <td class="muted">{{ $row['time'] ?? '-' }}</td>

                        <td
                            style="{{ !empty($row['duration_emphasis']) ? 'color:#b91c1c; font-weight:800;' : 'color:#475569;' }}">
                            {{ $row['duration'] ?? '-' }}
                        </td>

                        <td>
                            <span class="pill {{ $cls }}">{{ $row['status'] ?? '-' }}</span>
                        </td>

                        <td class="{{ ($row['staff'] ?? '') === 'Unassigned' ? 'muted' : '' }}">
                            {{ $row['staff'] ?? '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

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
                    <td>© {{ date('Y') }} CardioSOS System</td>
                    <td style="text-align:right;">Page 3 of 3</td>
                </tr>
            </table>
        </div>
    </div>

</body>

</html>
