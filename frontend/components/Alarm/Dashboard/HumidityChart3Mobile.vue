<template>
  <span>
    <style scoped>
      .chartBox {
        margin: 0 auto;
        width: 180px !important;
        padding: 20px;
        padding-left: 0px;
        border-radius: 20px;
        position: relative;
      }
      .humidity {
        color: #fff;
        position: absolute;
        bottom: 61px;
        left: 48%;
        transform: translateX(-50%);
        font-size: 20px;
        margin: 0;
        text-align: center;
      }

      .chart-time {
        font-size: 12px;
      }
      .chartBox canvas {
        height: 150px !important;
      }
      .temperature-time {
        font-size: 12px;
      }
    </style>
    <div class="chartBox text-center">
      <canvas :id="name + '_canva'" width="300" height="300"></canvas>
      <div :id="name" class="humidity"></div>
      <div class="temperature-time">
        <v-icon color="default-font-color">mdi-clock-outline</v-icon>
        {{ humidity_date_time }}
      </div>
    </div>
  </span>
</template>

<script>
export default {
  props: ["humidity", "humidity_date_time", "name"],
  data() {
    return {
      currentValue: 0,
      chart: null,
    };
  },
  mounted() {
    //setTimeout(() => {
    if (!window.Chart) {
      const script = document.createElement("script");
      script.src = "/scripts/chart.js";
      script.onload = this.initChart;
      document.head.appendChild(script);
    } else {
      this.initChart();
    }
    //}, 1000 * 5);
  },
  methods: {
    initChart() {
      const ctx = document
        .getElementById(this.name + "_canva")
        .getContext("2d");

      if (this.chart) {
        this.chart.destroy();
      }

      // const gradient = ctx.createLinearGradient(0, 0, 300, 0);
      // gradient.addColorStop(0, "green");
      // gradient.addColorStop(0.2, "yellow");
      // gradient.addColorStop(0.8, "yellow");

      // gradient.addColorStop(1, "red");

      const gradient = ctx.createLinearGradient(0, 0, 300, 0);
      gradient.addColorStop(0, "green"); // 0% of the gradient
      gradient.addColorStop(0.1, "green"); // 25%

      gradient.addColorStop(0.2, "yellow"); // up to 70%
      gradient.addColorStop(0.3, "yellow"); // up to 70%

      gradient.addColorStop(0.4, "red"); // transition to red
      gradient.addColorStop(0.5, "red"); // transition to red

      gradient.addColorStop(1, "red"); // 100%

      const gaugeNeedle = {
        id: "gaugeNeedle",
        afterDraw: (chart) => {
          const {
            ctx,
            chartArea: { width },
          } = chart;
          const cx = width / 2;
          const cy = 100; //150 //needle top position //180 is bottom
          const needleLength = 70; //100 //150
          const needleWidth = 20;
          const radius = needleWidth / 2;
          const angle = Math.PI + (this.currentValue / 100) * Math.PI;

          const tipX = cx + Math.cos(angle) * needleLength;
          const tipY = cy + Math.sin(angle) * needleLength;

          const baseX = cx;
          const baseY = cy;

          const perpAngle1 = angle + Math.PI / 2;
          const perpAngle2 = angle - Math.PI / 2;

          const baseX1 = baseX + Math.cos(perpAngle1) * radius;
          const baseY1 = baseY + Math.sin(perpAngle1) * radius;
          const baseX2 = baseX + Math.cos(perpAngle2) * radius;
          const baseY2 = baseY + Math.sin(perpAngle2) * radius;

          ctx.save();
          ctx.shadowColor = "rgba(0, 0, 0, 0.3)";
          ctx.shadowBlur = 5;

          ctx.beginPath();
          ctx.moveTo(baseX1, baseY1);
          ctx.arc(baseX, baseY, radius, perpAngle1, perpAngle2, false);
          ctx.lineTo(tipX, tipY);
          ctx.closePath();
          if (this.humidity < 40) ctx.fillStyle = "green";
          else if (this.humidity < 65) ctx.fillStyle = "yellow";
          else ctx.fillStyle = "red";
          ctx.fill();

          ctx.beginPath();
          ctx.shadowBlur = 0;
          ctx.arc(cx, cy, 8, 0, Math.PI * 2);
          ctx.fill();
          ctx.restore();
        },
      };

      const data = {
        labels: ["Low", "Medium", "High", ""],
        datasets: [
          {
            data: [100],
            backgroundColor: [gradient],
            borderWidth: 0,
            cutout: "80%",
            circumference: 270,
            rotation: 225,
          },
        ],
      };

      const config = {
        type: "doughnut",
        data,
        options: {
          responsive: true,
          plugins: {
            legend: { display: false },
            tooltip: { enabled: false },
          },
        },
        plugins: [gaugeNeedle],
      };

      this.chart = new window.Chart(ctx, config);
      this.animateNeedle(this.humidity);
      this.counterUp(this.humidity);
    },

    animateNeedle(target) {
      const speed = 2;
      const animate = () => {
        const diff = target - this.currentValue;
        if (Math.abs(diff) < 0.1) {
          this.currentValue = target;
          this.chart.update();
          return;
        }
        this.currentValue += diff / speed;
        this.chart.update();
        requestAnimationFrame(animate);
      };
      animate();
    },

    counterUp(target, duration = 1000, steps = 1) {
      if (isNaN(target)) {
        target = 0;
      }
      const element = document.getElementById(this.name);
      let current = 0;
      const increment = target / steps;
      const interval = duration / steps;

      const counter = setInterval(() => {
        current += increment;
        if (current >= target) {
          element.innerHTML = `${target}  <small>%</small>`;
          clearInterval(counter);
        } else {
          const value =
            target < 10
              ? "00.00"
              : (Math.round(current * 100) / 100).toFixed(2);
          element.innerHTML = `${value}  <small>%</small>`;
        }
      }, interval);
    },
  },
};
</script>
