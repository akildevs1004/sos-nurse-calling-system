  <!doctype html>
  <html lang="en">

  <head>
      <meta charset="utf-8">
      <title>SOS Dashboard Charts</title>
      <meta name="viewport" content="width=device-width, initial-scale=1">

      {{-- Load ApexCharts ONCE --}}
      <script src="{{ asset('vendor/apexcharts/apexcharts.min.js') }}"></script>

      <style>
          :root {
              --border: #e5e7eb;
              --text: #0f172a;
              --muted: #64748b;
              --bg: #fff;
          }

          html,
          body {
              background: var(--bg);
              margin: 0;
              padding: 0;
              font-family: Arial, sans-serif;
              color: var(--text);
          }

          .page {
              width: 1100px;
              padding: 18px;
              box-sizing: border-box;
              background: var(--bg);
          }

          .title {
              font-size: 18px;
              font-weight: 700;
              margin: 0 0 14px 0;
          }

          .grid {
              display: grid;
              grid-template-columns: 1fr 1fr;
              gap: 16px;
          }

          .card {
              border: 1px solid var(--border);
              border-radius: 12px;
              background: #fff;
              padding: 14px;
              box-sizing: border-box;
              min-height: 310px;
          }

          .card-title {
              font-size: 14px;
              font-weight: 700;
              margin-bottom: 8px;
          }

          .chart {
              height: 260px;
          }

          .list {
              margin-top: 8px;
          }

          .row {
              display: grid;
              grid-template-columns: 1fr auto;
              gap: 12px;
              align-items: center;
              margin: 10px 0 12px;
          }

          .label {
              font-size: 13px;
              font-weight: 600;
          }

          .count {
              font-size: 12px;
              color: var(--muted);
              white-space: nowrap;
          }

          .track {
              grid-column: 1 / -1;
              height: 8px;
              border-radius: 999px;
              background: #eef2f7;
              overflow: hidden;
          }

          .fill {
              height: 100%;
              border-radius: 999px;
              width: 0%;
          }

          .muted {
              color: var(--muted);
              font-size: 13px;
          }
      </style>
  </head>

  <body>
      <div class="page">
          <div class="title">Data Visualization</div>

          <div class="grid">
              {{-- INCLUDE your 4 blades here --}}
              @include('sos.sos-chart-render-sos-donut-stats')
              @include('sos.sos-chart-render-sos-hourly-barchart')
              @include('sos.sos-chart-render-sos-response-minutes')
              @include('sos.sos-chart-render-sos-rooms-type')

          </div>
      </div>

      <script>
          // For Browsershot: wait until all included partials finished rendering charts
          //   (function() {
          //       window.__chartPromises = window.__chartPromises || [];
          //       Promise.all(window.__chartPromises)
          //           .then(() => {
          //               window.__allChartsReady = true;
          //           })
          //           .catch(() => {
          //               window.__allChartsReady = true;
          //           }); // don't hang forever
          //   })();
      </script>
  </body>

  </html>
