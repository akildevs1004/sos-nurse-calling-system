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

    <div class="section-title">Data Visualization</div>

    {{-- 4 CHARTS (2x2) --}}
    <table class="chart-grid">
        <tr>


            <td class="chart-cell" style="border-bottom:0;">
                <div class="chart-card">
                    <div class="chart-title">Hourly SOS Calls </div>
                    <img src="{{ $chartHourlySos }}" class="chart-img" alt="Hourly SOS">
                </div>
            </td>
            <td class="chart-cell" style="border-bottom:0;">
                <div class="chart-card">
                    <div class="chart-title">Response/Acknowledgement Hourly </div>
                    <img src="{{ $chartResponseHourly }}" class="chart-img" alt="Response Hourly SOS">
                </div>
            </td>
        </tr>

        <tr>
            <td class="chart-cell" style="border-bottom:0;">
                <div class="chart-card">
                    <div class="chart-title">SOS Status Breakdown</div>
                    <img src="{{ $chartStatusDonut }}" class="chart-img" alt="SOS Status Donut">
                </div>
            </td>

            <td class="chart-cell" style="border-bottom:0;">
                <div class="chart-card">
                    <div class="chart-title">SOS Rooms / Sources</div>
                    @include('sos.sos-chart-render-sos-rooms-type')
                </div>
            </td>
        </tr>
    </table>

    {{-- If you want, you can add a small note under charts --}}
    <div class="muted small" style="margin-top:8px;">
        Charts are rendered from pre-generated PNG images stored under
        <strong>public/storage/reports/charts</strong>.
    </div>

</div>
