const net = require("net");
const express = require("express");
const fs = require("fs");
const path = require("path");
const axios = require("axios");
const { json } = require("stream/consumers");

const app = express();
const api_port = 6000; //SDK Port - Website to Socket (SDK)
const socket_port = 6002; // Device Port - Device to Socket server

app.use(express.json());

// Utils
const today = new Date().toISOString().split("T")[0];
const logFilePath = path.join(__dirname, `${today}.log`);
const rawLogFilePath = path.join(__dirname, `raw_${today}.log`);
const sensorLogFilePath = path.join(__dirname, `/logs/sensor_${today}.log`);
const alarmLogFilePath = path.join(__dirname, `/logs/alarm_${today}.log`);

const logStream = fs.createWriteStream(logFilePath, { flags: "a" });
const rawLogStream = fs.createWriteStream(rawLogFilePath, { flags: "a" });

const originalConsoleLog = console.log;

console.log = (...args) => {
  const logMessage = args
    .map((a) => (typeof a === "object" ? JSON.stringify(a) : a))
    .join(" ");
  const timestamp = new Date().toISOString();
  originalConsoleLog.apply(console, args);
  logStream.write(`[${timestamp}] ${logMessage}\n`);
};

function logAlarmData(serialNumber, object) {
  console.log(object);

  fs.appendFileSync(alarmLogFilePath, JSON.stringify(object) + "\n");

  console.log(`📁 Logged  alarm for ${serialNumber} @ ${timestamp}`);
}

const sensorLogs = {}; // To store last temperature values in memory

function logSensorDataIfChanged(serialNumber, sensorArray) {
  const today = new Date().toISOString().slice(0, 10); // YYYY-MM-DD
  const logDir = path.join(__dirname, "logs");
  const logFile = sensorLogFilePath; //path.join(logDir, `${serialNumber}_${today}.log`);

  if (!fs.existsSync(logDir)) fs.mkdirSync(logDir, { recursive: true });

  const lastTemps = (sensorLogs[serialNumber] ||= []);
  const timestamp = new Date().toISOString();

  let logsToWrite = [];

  sensorArray.forEach((sensor, i) => {
    const newTemp = sensor.temperature;

    if (sensor.online) {
      const lastTemp = lastTemps[i] ?? null;

      if (lastTemp === null || Math.abs(newTemp - lastTemp) >= 0.2) {
        lastTemps[i] = newTemp;

        logsToWrite.push({
          timestamp,
          serial_number: serialNumber,
          sensor: sensor,
        });
      }
    }
  });

  if (logsToWrite.length > 0) {
    logsToWrite.forEach((entry) => {
      fs.appendFileSync(logFile, JSON.stringify(entry) + "\n");
    });
    console.log(
      `📁 Logged ${logsToWrite.length} sensor(s) for ${serialNumber} @ ${timestamp}`
    );
  }
}

function logWithTime(...args) {
  const timestamp = new Date().toISOString();
  console.log(`[${timestamp}]`, ...args);
}

// In-memory storage
const deviceConfigs = {};

// TCP Socket Server
const server = net.createServer((socket) => {
  logWithTime("New device connected");

  let buffer = "";
  //Socket for Device Heartbeat communication
  socket.on("data", (data) => {
    buffer += data.toString();
    let boundary = buffer.indexOf("\n");

    while (boundary > -1) {
      const message = buffer.substring(0, boundary);
      buffer = buffer.substring(boundary + 1);

      try {
        const dataObject = JSON.parse(message);
        const serialNumber = dataObject.serialNumber;

        deviceConfigs[serialNumber] = deviceConfigs[serialNumber] || {};
        deviceConfigs[serialNumber].socket = socket;
        deviceConfigs[serialNumber].last_heartbeat = new Date().toISOString();
        deviceConfigs[serialNumber].config_data = dataObject.config;
        deviceConfigs[serialNumber].sensor_data = dataObject.sensor_data;

        // rawLogStream.write(message + "\n");
        if (dataObject.sensor_data && dataObject.sensor_data) {
          let sensorsList = JSON.parse(dataObject.sensor_data);
          if (sensorsList.sensors)
            logSensorDataIfChanged(serialNumber, sensorsList.sensors);
        }

        if (dataObject.type === "heartbeat") {
          logWithTime("Received heartbeat from device:", serialNumber);
        } else if (dataObject.type === "config") {
          logWithTime("Received config from device:", serialNumber);
          // rawLogStream.write(message + "\n");
        } else if (dataObject.type === "alarm") {
          logWithTime("Received alarm from device:", serialNumber);
          logAlarmData(serialNumber, dataObject);
          // rawLogStream.write(message + "\n");
        } else {
          logWithTime(
            "Received",
            dataObject.type,
            "from device:",
            serialNumber
          );
        }

        // Optional action: switchStatus
        // if (dataObject.type === "switchStatus") {
        //   const postData = {
        //     room_number: serialNumber,
        //     status: dataObject.switchStatus,
        //   };
        //   axios.post("https://backend.ezhms.com/api/update_device_room_fill_status", postData)
        //     .then(response => logWithTime("Backend response:", response.data))
        //     .catch(error => logWithTime("Error posting data:", error.message));
        // }

        // socket.write(
        //   JSON.stringify({ status: "ok", received: dataObject.type }) + "\n"
        // );
      } catch (err) {
        logWithTime("Error parsing message:", err.message);
      }

      boundary = buffer.indexOf("\n");
    }
  });

  socket.on("end", () => {
    console.log("Device socket closed", hadError ? "with error" : "gracefully");
    logWithTime("Device disconnected");
  });

  socket.on("close", (hadError) => {
    console.log("Device socket closed", hadError ? "with error" : "gracefully");

    // Find and remove the device from memory
    for (const serial in deviceConfigs) {
      if (deviceConfigs[serial].socket === socket) {
        console.log("Device disconnected:", serial);
        delete deviceConfigs[serial];
        break;
      }
    }
  });

  // socket.setTimeout(30000); // 30 seconds

  // socket.on("timeout", () => {
  //   logWithTime("Socket timeout, destroying connection");
  //   socket.destroy();
  // });

  socket.on("error", (err) => {
    if (err.code === "ECONNRESET") {
      logWithTime("Device connection was reset");
    } else {
      logWithTime("Socket error:", err.message);
    }
  });
});

server.listen(socket_port, "0.0.0.0", () => {
  logWithTime(`SDK server listening on port ${socket_port}`);
});

// Periodic cleanup for stale devices
setInterval(() => {
  const now = Date.now();
  for (const [serial, data] of Object.entries(deviceConfigs)) {
    const lastSeen = new Date(data.last_heartbeat).getTime();
    if (now - lastSeen > 60 * 1000) {
      // 5 mins
      logWithTime(
        `----Removing Inactive device: ${serial} Diff ${
          (now - lastSeen) / 1000
        } Last: ${data.last_heartbeat}`
      );
      delete deviceConfigs[serial];
    }
  }
}, 5 * 1000); // every 10 mins

// Express API for We requests from Cloud Software
app.get("/device-sensordata/:serialNumber", (req, res) => {
  const serialNumber = req.params.serialNumber;
  const deviceData = deviceConfigs[serialNumber];

  if (deviceData) {
    // Send sensor data (you can customize this structure)
    res.json({
      error: false,
      serialNumber: serialNumber,
      timestamp: new Date().toISOString(),
      sensors: deviceData.sensors, // e.g. array of { id, temperature, humidity, isOnline }
    });
  } else {
    // Handle case where device is not found
    res.status(404).json({
      error: true,
      message: `Device with serial number ${serialNumber} not found.`,
    });
  }
});

// Express API for We requests from Cloud Software
app.get("/device-config/:serialNumber", (req, res) => {
  const serialNumber = req.params.serialNumber;
  const deviceData = deviceConfigs[serialNumber];

  if (deviceData) {
    if (deviceData.socket && !deviceData.socket.destroyed) {
      const message = {
        action: "GET_CONFIG",
        serialNumber: serialNumber,
      };

      try {
        deviceData.socket.write(JSON.stringify(message) + "\n");
        logWithTime("Sent GET_CONFIG to device:", serialNumber);
        rawLogStream.write(JSON.stringify(message) + "\n");
      } catch (e) {
        logWithTime("Failed to write to socket:", e.message);
        return res
          .status(500)
          .json({ error: "Failed to send message to device" });
      }
    } else {
      return res.status(404).json({ error: "Device SDK not responding" });
    }

    return res.json({
      serialNumber: serialNumber,
      config: deviceData.config_data || {},
      sensor_data: deviceData.sensor_data || {},
      last_heartbeat: deviceData.last_heartbeat || "No heartbeat received",
    });
  } else {
    return res.status(404).json({ error: "Device not found or disconnected" });
  }
});

app.post("/device-config-update/:serialNumber", (req, res) => {
  const serialNumber = req.params.serialNumber;
  const newConfig = req.body.config;

  logWithTime("Device config update request for:", serialNumber);

  if (!newConfig) {
    return res.status(400).json({ error: "Missing config data" });
  }

  newConfig.lastUpdated = new Date().toISOString();
  const deviceData = deviceConfigs[serialNumber];

  if (deviceData && deviceData.socket && !deviceData.socket.destroyed) {
    const message = {
      action: "UPDATE_CONFIG",
      serialNumber: serialNumber,
      config: newConfig,
    };

    try {
      deviceData.socket.write(JSON.stringify(message) + "\n");
      logWithTime("Sent updated config to device:", serialNumber);
      return res.json({
        success: true,
        message: `Config sent to ${serialNumber}`,
      });
    } catch (err) {
      logWithTime("Error sending config:", err.message);
      return res.status(500).json({ error: "Failed to send config to device" });
    }
  } else {
    return res.status(404).json({ error: "Device not found or disconnected" });
  }
});

// Optional test endpoint
app.get("/ping", (req, res) => {
  res.json({ status: "OK", time: new Date().toISOString() });
});

app.listen(api_port, () => {
  logWithTime(`API server listening at http://localhost:${api_port}`);
});
