module.exports = {
  apps: [
    {
      name: "sosalarm-frontend",
      script: "npx",
      args: "http-server dist -p 6061",
      cwd: "/var/www/sosalarm/frontend",
      interpreter: "none",
    },
    {
      name: "sosalarm-mqtt-backend",
      script: "artisan",
      args: "mqtt:subscribe",
      cwd: "/var/www/sosalarm/backend",
      interpreter: "php",
    },
    {
      name: "sosalarm-work",
      script: "artisan",
      args: "queue:work --sleep=3 --tries=3 --timeout=90",
      cwd: "/var/www/sosalarm/backend",
      interpreter: "php",
    },
    {
      name: "sosalarm-schedule",
      script: "artisan",
      args: "schedule:work",
      cwd: "/var/www/sosalarm/backend",
      interpreter: "php",
    },
  ],
};
