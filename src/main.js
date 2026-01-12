const { app, BrowserWindow, screen, ipcMain, dialog } = require("electron");
const fs = require("fs");
const path = require("path");
const { execSync } = require("child_process");
const {
  logger,
  spawnWrapper,
  spawnPhpCgiWorker,
  runInstaller,
  ipv4Address,
  setMenu,
  stopServices,
  getCachedMachineId,
  isClockTampered,
} = require("./helpers");

app.setName("XtremeGuardSOS");
app.setAppUserModelId("XtremeGuardSOS");

let isQuitting = false;

const isDev = !app.isPackaged;
const appDir = isDev ? process.cwd() : process.resourcesPath;

const srcDirectory = path.join(appDir, "www");

const nginxPath = path.join(appDir, "nginx.exe");
const phpPath = path.join(srcDirectory, "php");
const phpPathCli = path.join(phpPath, "php.exe");
const phpCGi = path.join(phpPath, "php-cgi.exe");

// // ✅ Mosquitto
// const mosquittoPath = `C:\\Program Files\\mosquitto\\mosquitto.exe`;
// const mosquittoConf = `C:\\Program Files\\mosquitto\\mosquitto.conf`;
function firstExists(list) {
  return list.find((p) => p && fs.existsSync(p)) || null;
}

// Windows Program Files (respects x64/x86 automatically)
const PROGRAM_FILES = process.env.ProgramFiles || "C:\\Program Files111";
// Prefer installed copy → fallback to app resources
const mosquittoPath = firstExists([
  path.join(PROGRAM_FILES, "mosquitto", "mosquitto.exe"),
  path.join(appDir, "mosquitto", "mosquitto.exe"),
]);

const mosquittoConf = firstExists([
  path.join(PROGRAM_FILES, "mosquitto", "mosquitto.conf"),
  path.join(appDir, "mosquitto", "mosquitto.conf"),
]);

let nginxWindow;

let nginxPID = null;
let schedulePID = null;
let queuePID = null;
let serverPID = null;
let mqttPID = null;
let mosquittoPID = null;

let MACHINE_ID = null;

// -------------------- HELPERS --------------------
function ensureLogsDir() {
  try {
    fs.mkdirSync(path.join(appDir, "logs"), { recursive: true });
  } catch {}
}

function isPortListening(port) {
  try {
    const out = execSync(
      `powershell -NoProfile -Command "(Get-NetTCPConnection -LocalPort ${port} -State Listen -ErrorAction SilentlyContinue).Count"`,
      { stdio: ["ignore", "pipe", "ignore"] }
    )
      .toString()
      .trim();
    return parseInt(out || "0", 10) > 0;
  } catch {
    return false;
  }
}

// -------------------- SOCKET CLEANUP --------------------
const socketPort = 7777;

try {
  execSync(
    `powershell -NoProfile -ExecutionPolicy Bypass -Command "$p=${socketPort}; ` +
      `$c=Get-NetTCPConnection -LocalPort $p -State Listen -ErrorAction SilentlyContinue; ` +
      `if($c){Stop-Process -Id $c.OwningProcess -Force}"`,
    { stdio: "ignore" }
  );
  logger(
    "SOCKET",
    `✅ Freed port ${socketPort} before starting socket server.`
  );
} catch (err) {
  logger("SOCKET", `ℹ️ Port ${socketPort} was free, continuing...`);
}

// Initialize socket (after cleanup)
require("./socket");

function startServices() {
  // Prevent double-start
  if (
    nginxPID ||
    schedulePID ||
    queuePID ||
    serverPID ||
    mqttPID ||
    mosquittoPID
  ) {
    logger(
      "Application",
      "ℹ️ Services already started; skipping duplicate start."
    );
    // return;
  }

  // 1) ✅ Mosquitto Broker (only if not already running)
  if (!isPortListening(1883)) {
    mosquittoPID = spawnWrapper(
      "[Mosquitto]",
      mosquittoPath,
      ["-c", mosquittoConf, "-v"],
      { cwd: path.dirname(mosquittoPath) }
    );
    logger("Mosquitto", "✅ Starting Mosquitto broker...");
  } else {
    logger(
      "Mosquitto",
      "ℹ️ Port 1883 already listening; broker is already running."
    );
  }

  // 3) PHP-CGI worker(s)
  serverPID = spawnPhpCgiWorker(phpCGi, 9000);

  // 4) Laravel scheduler
  schedulePID = spawnWrapper(
    "[Scheduler]",
    phpPathCli,
    ["artisan", "schedule:work"],
    { cwd: srcDirectory }
  );

  // 5) Laravel queue
  queuePID = spawnWrapper("[Queue]", phpPathCli, ["artisan", "queue:work"], {
    cwd: srcDirectory,
  });

  // 6) ✅ Laravel MQTT subscriber
  mqttPID = spawnWrapper("[MQTT]", phpPathCli, ["artisan", "mqtt:subscribe"], {
    cwd: srcDirectory,
  });

  // 2) Nginx
  nginxPID = spawnWrapper("[Nginx]", nginxPath, { cwd: appDir });

  logger("Application", `Application started at http://${ipv4Address}:8000`);
}

ipcMain.handle("open-report-window", (event, url) => {
  ensureLogsDir();
  try {
    fs.appendFileSync(path.join(appDir, "logs", "ips.txt"), `${url}\n`);
  } catch {}

  const { width, height } = screen.getPrimaryDisplay().workAreaSize;

  const reportWindow = new BrowserWindow({
    width,
    height,
    fullscreen: true,
    webPreferences: {
      nodeIntegration: true,
      contextIsolation: false,
    },
  });

  reportWindow.loadURL(url);
});

function createNginxWindow() {
  const { width, height } = screen.getPrimaryDisplay().workAreaSize;

  nginxWindow = new BrowserWindow({
    width,
    height,
    autoHideMenuBar: true,
    webPreferences: {
      nodeIntegration: true,
      contextIsolation: false,
    },
  });

  // If you want "UI immediately", load a local splash first:
  // nginxWindow.loadFile(path.join(appDir, "splash.html"));

  // Your current behavior (requires nginx to be running to load properly):
  nginxWindow.loadURL(`http://${ipv4Address}:3000`);
  nginxWindow.maximize();

  nginxWindow.on("closed", () => {
    nginxWindow = null;
  });

  return nginxWindow;
}

app.whenReady().then(async () => {
  ensureLogsDir();

  if (isClockTampered()) {
    dialog.showErrorBox(
      "System Time Error",
      "System date/time appears to have been changed.\n\nPlease correct your system clock and restart the application."
    );
    app.exit(1);
    return;
  }

  MACHINE_ID = await getCachedMachineId();
  ipcMain.handle("get-machine-id", () => MACHINE_ID);

  setMenu();

  // Show UI
  const mainWindow = createNginxWindow();

  // Heavy tasks AFTER UI
  setImmediate(() => {
    runInstaller(path.join(appDir, "vs_redist.exe"))
      .then(() => {
        startServices();

        if (mainWindow && !mainWindow.isDestroyed()) {
          mainWindow.webContents.send("nginx-ready");

          // If using splash.html, switch URL here:
          // mainWindow.loadURL(`http://${ipv4Address}:3000`);
        }
      })
      .catch((err) => {
        if (mainWindow && !mainWindow.isDestroyed()) {
          mainWindow.webContents.send(
            "nginx-error",
            err?.message || String(err)
          );
        }
      });
  });
});

// Ensure app quits cleanly and stops services
app.on("before-quit", async (e) => {
  if (isQuitting) return;

  e.preventDefault();
  isQuitting = true;

  ensureLogsDir();
  try {
    fs.appendFileSync(
      path.join(appDir, "logs", "XtremeGuardSOS_SHUTDOWN.txt"),
      "before-quit fired\n"
    );
  } catch {}

  await stopServices(mqttPID);
  await stopServices(queuePID);
  await stopServices(schedulePID);
  await stopServices(serverPID);
  await stopServices(nginxPID);
  await stopServices(mosquittoPID);

  app.exit(0);
});

app.on("will-quit", async () => {
  ensureLogsDir();
  try {
    fs.appendFileSync(
      path.join(appDir, "logs", "XtremeGuardSOS_SHUTDOWN.txt"),
      "will-quit fired\n"
    );
  } catch {}

  await stopServices(mqttPID);
  await stopServices(queuePID);
  await stopServices(schedulePID);
  await stopServices(serverPID);
  await stopServices(nginxPID);
  await stopServices(mosquittoPID);
});
