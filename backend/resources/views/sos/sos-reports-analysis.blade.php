<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>SOS Call Report</title>
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
        }

        .page:last-child {
            page-break-after: auto;
        }

        .muted {
            color: #64748b;
        }

        .border {
            border: 1px solid #e2e8f0;
        }

        .border-b {
            border-bottom: 1px solid #e2e8f0;
        }

        .rounded {
            border-radius: 10px;
        }

        .p-16 {
            padding: 16px;
        }

        .p-20 {
            padding: 20px;
        }

        .mt-16 {
            margin-top: 16px;
        }

        .mt-24 {
            margin-top: 24px;
        }

        .mb-8 {
            margin-bottom: 8px;
        }

        .mb-12 {
            margin-bottom: 12px;
        }

        .mb-16 {
            margin-bottom: 16px;
        }

        .mb-24 {
            margin-bottom: 24px;
        }

        .h1 {
            font-size: 40px;
            font-weight: 800;
            line-height: 1.0;
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

        .primary {
            color: #137fec;
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

        .section-title {
            font-size: 13px;
            font-weight: 800;
            margin: 0 0 10px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
        }

        /* Simple “cards” */
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

        .big {
            font-size: 22px;
            font-weight: 800;
            margin: 0;
        }

        .small {
            font-size: 11px;
        }

        /* Table */
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

        /* Charts (pure HTML, PDF-safe) */
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
    </style>
</head>

<body>

    {{-- PAGE 1 --}}
    <div class="page">
        <div style="margin-top: 10px;">
            <table style="width:100%;">
                <tr>
                    <td style="width:80px; vertical-align: top;">
                        <div
                            style="width:64px;height:64px;background:#137fec;border-radius:14px; text-align:center; color:#fff; font-weight:800; font-size:28px; line-height:64px;">
                            +
                        </div>
                    </td>
                    <td style="vertical-align: top;">
                        <div style="border-left:4px solid #137fec; padding-left:14px;">
                            <p class="h1">SOS Call<br>Report</p>
                            <p style="font-size:16px; margin:10px 0 0 0;" class="muted">
                                {{ $unitName ?? 'Cardiology Unit A' }}</p>
                        </div>

                        <div class="mt-24">
                            <table style="width:100%;">
                                <tr>
                                    <td style="width:140px;" class="muted small"><strong>REPORT PERIOD</strong></td>
                                    <td style="font-size:13px;">
                                        <strong>{{ $reportPeriod ?? 'October 17 - October 23, 2024' }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="muted small"><strong>GENERATED ON</strong></td>
                                    <td style="font-size:13px;">
                                        <strong>{{ $generatedOn ?? 'Oct 24, 2024 at 14:30' }}</strong></td>
                                </tr>
                                <tr>
                                    <td class="muted small"><strong>PREPARED BY</strong></td>
                                    <td style="font-size:13px;">
                                        <strong>{{ $preparedBy ?? 'Dr. Sarah Jenkins' }}</strong></td>
                                </tr>
                            </table>
                        </div>

                        <div class="card mt-24">
                            <div class="h3 mb-12"><span class="primary">■</span> Executive Summary</div>

                            <table style="width:100%;">
                                <tr>
                                    <td style="width:60%; vertical-align: top; padding-right:14px;">
                                        <div style="color:#475569; line-height:1.5;">
                                            This week showed a <strong>12% increase</strong> in total SOS calls compared
                                            to the previous period.
                                            Despite the higher volume, the average response time improved by <strong
                                                style="color:#047857;">5%</strong>,
                                            demonstrating efficient staff allocation.
                                        </div>
                                        <div class="mt-16">
                                            <div class="small" style="margin-bottom:6px;"><span
                                                    style="color:#10b981;">●</span> 97% Resolution Rate achieved</div>
                                            <div class="small"><span style="color:#f97316;">●</span> Peak activity
                                                observed: 10:00 AM - 12:00 PM</div>
                                        </div>
                                    </td>

                                    <td style="width:40%; vertical-align: top;">
                                        <div
                                            style="background:#f8fafc; border:1px solid #f1f5f9; border-radius:10px; padding:12px;">
                                            <div class="card-title">Key Metrics at a Glance</div>
                                            <table style="width:100%;">
                                                <tr>
                                                    <td style="padding:6px 0; border:0;">Total Calls</td>
                                                    <td
                                                        style="padding:6px 0; border:0; text-align:right; font-weight:800;">
                                                        {{ $totalCalls ?? 142 }}</td>
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
                                                        {{ $avgResponse ?? '1m 12s' }}</td>
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
                                                        {{ $topLocation ?? 'Room 304' }}</td>
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

        <div class="footer">
            <table>
                <tr>
                    <td>© {{ date('Y') }} CardioSOS System</td>
                    <td style="text-align:right;">Page 1 of 3</td>
                </tr>
            </table>
        </div>
    </div>

    {{-- PAGE 2 --}}
    <div class="page">
        <table style="width:100%;" class="border-b" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding-bottom:12px;">
                    <div style="font-size:18px; font-weight:800;">CardioSOS</div>
                    <div class="muted small" style="letter-spacing:0.08em; text-transform:uppercase;">Intelligent Nurse
                        Call System</div>
                </td>
                <td style="text-align:right; padding-bottom:12px;">
                    <div style="font-size:16px; font-weight:800;">SOS Call Report</div>
                    <div class="muted">Detailed Analytics</div>
                </td>
            </tr>
        </table>

        <div class="mt-16">
            <table style="width:100%;">
                <tr>
                    <td style="width:25%; padding-right:10px;">
                        <div class="card">
                            <div class="card-title">Total Calls</div>
                            <p class="big">{{ $totalCalls ?? 142 }}</p>
                            <div class="small" style="color:#047857; font-weight:700;">12% vs prev. period</div>
                        </div>
                    </td>
                    <td style="width:25%; padding-right:10px;">
                        <div class="card">
                            <div class="card-title">Avg Response</div>
                            <p class="big">{{ $avgResponse ?? '1m 12s' }}</p>
                            <div class="small" style="color:#047857; font-weight:700;">5% improvement</div>
                        </div>
                    </td>
                    <td style="width:25%; padding-right:10px;">
                        <div class="card">
                            <div class="card-title">Resolved</div>
                            <p class="big">{{ $resolvedPct ?? '97%' }}</p>
                            <div class="progress" style="margin-top:10px;">
                                <div style="width: {{ rtrim($resolvedPct ?? '97%', '%') }}%;"></div>
                            </div>
                        </div>
                    </td>
                    <td style="width:25%;">
                        <div class="card">
                            <div class="card-title">Top Location</div>
                            <p style="font-size:16px; font-weight:800; margin:0;">{{ $topLocation ?? 'Room 304' }}</p>
                            <div class="small muted" style="margin-top:8px;">
                                {{ $topLocationCalls ?? '12 calls this period' }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="mt-24">
            <div class="section-title">Data Visualization</div>

            <table style="width:100%;">
                <tr>
                    <td style="width:50%; padding-right:10px; vertical-align: top;">
                        <div class="card">
                            <div class="h3 mb-12">Call Frequency (24h)</div>
                            <div class="bar-wrap">
                                <table class="bars">
                                    <tr>
                                        @php
                                            $bars = $hourlyBars ?? [15, 10, 5, 8, 25, 45, 65, 85, 55, 40, 30, 20];
                                            $max = max($bars);
                                        @endphp
                                        @foreach ($bars as $v)
                                            <td>
                                                <div class="bar" style="height: {{ intval(($v / $max) * 100) }}%;">
                                                    <div style="height:100%;"></div>
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                </table>
                                <div class="muted small" style="margin-top:6px;">00 02 04 06 08 10 12 14 16 18 20 22
                                </div>
                            </div>
                        </div>
                    </td>

                    <td style="width:50%; vertical-align: top;">
                        <div class="card">
                            <div class="h3 mb-12">Response Time Trends</div>
                            <div style="height:160px; border:1px solid #e2e8f0; border-radius:10px; padding:10px;">
                                {{-- Keep it simple for PDF: show a small trend table or a basic inline SVG if using wkhtmltopdf --}}
                                <div class="muted small">Mon → Sun trend (visual simplified for PDF compatibility)
                                </div>
                                <table style="width:100%; margin-top:10px;">
                                    <tr>
                                        @foreach ($trend ?? ['Mon' => 72, 'Tue' => 68, 'Wed' => 64, 'Thu' => 66, 'Fri' => 60, 'Sat' => 56, 'Sun' => 52] as $d => $v)
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
                    <td style="width:50%; padding-right:10px; vertical-align: top; padding-top:10px;">
                        <div class="card">
                            <div class="h3 mb-12">Status Distribution</div>
                            <table style="width:100%;">
                                <tr>
                                    <td style="border:0;">Resolved</td>
                                    <td style="border:0; text-align:right; font-weight:800;">
                                        {{ $statusResolved ?? '65%' }}</td>
                                </tr>
                                <tr>
                                    <td style="border:0;">Ack.</td>
                                    <td style="border:0; text-align:right; font-weight:800;">{{ $statusAck ?? '25%' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td style="border:0;">Pending</td>
                                    <td style="border:0; text-align:right; font-weight:800;">
                                        {{ $statusPending ?? '10%' }}</td>
                                </tr>
                            </table>
                        </div>
                    </td>

                    <td style="width:50%; vertical-align: top; padding-top:10px;">
                        <div class="card">
                            <div class="h3 mb-12">Source Distribution</div>

                            @php
                                $sources = $sourceBars ?? [
                                    ['label' => 'Patient Rooms', 'value' => 88, 'pct' => 62],
                                    ['label' => 'Disabled Toilets', 'value' => 42, 'pct' => 30],
                                    ['label' => 'Common Areas', 'value' => 12, 'pct' => 8],
                                ];
                            @endphp

                            @foreach ($sources as $s)
                                <div style="margin-bottom:10px;">
                                    <table style="width:100%;">
                                        <tr>
                                            <td style="border:0; font-weight:700;">{{ $s['label'] }}</td>
                                            <td style="border:0; text-align:right;" class="muted">
                                                {{ $s['value'] }} calls</td>
                                        </tr>
                                    </table>
                                    <div class="progress">
                                        <div style="width: {{ $s['pct'] }}%;"></div>
                                    </div>
                                </div>
                            @endforeach

                        </div>
                    </td>
                </tr>
            </table>
        </div>

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
        <table style="width:100%;" class="border-b" cellpadding="0" cellspacing="0">
            <tr>
                <td style="padding-bottom:12px;">
                    <div style="font-weight:800;">CardioSOS Report</div>
                </td>
                <td style="text-align:right; padding-bottom:12px;" class="muted small">
                    CONTINUED
                </td>
            </tr>
        </table>

        <div class="mt-16">
            <div class="h2 mb-12">Comprehensive Call Log</div>

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
                        <tr class="{{ $i % 2 ? 'alt' : '' }}">
                            <td style="font-family: monospace; font-size: 11px; color:#475569;">
                                <strong>{{ $row['id'] }}</strong></td>
                            <td><strong>{{ $row['location'] }}</strong></td>
                            <td class="muted">{{ $row['time'] }}</td>
                            <td
                                style="{{ $row['duration_emphasis'] ?? false ? 'color:#b91c1c; font-weight:800;' : 'color:#475569;' }}">
                                {{ $row['duration'] }}</td>
                            <td>
                                @php
                                    $st = strtolower($row['status']);
                                    $cls =
                                        $st === 'pending'
                                            ? 'pill-red'
                                            : ($st === 'ack' || $st === 'ack.'
                                                ? 'pill-amber'
                                                : 'pill-green');
                                @endphp
                                <span class="pill {{ $cls }}">{{ $row['status'] }}</span>
                            </td>
                            <td class="{{ ($row['staff'] ?? '') === 'Unassigned' ? 'muted' : '' }}">
                                {{ $row['staff'] ?? '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="note mt-16">
                <strong>Note:</strong>
                This report includes all SOS calls triggered between {{ $periodFrom ?? 'Oct 17' }} and
                {{ $periodTo ?? 'Oct 23' }}.
                Average response time is calculated based on the interval between “Call Time” and “Ack. Time”.
            </div>
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
