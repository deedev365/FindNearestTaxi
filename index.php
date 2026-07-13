<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Find Taxi — Dispatch Console</title>
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Ctext y='.9em' font-size='90'%3E%F0%9F%9A%95%3C/text%3E%3C/svg%3E">
<link rel="stylesheet" href="css/dispatch.css">
</head>
<body>

<div class="bg-grid"></div>
<div class="bg-scan"></div>
<div class="bg-vignette"></div>

<div class="shell">

  <header class="hud-header">
    <div class="hud-brand">
      <div class="hud-mark">🚕</div>
      <div class="hud-title-group">
        <h1>FIND <span>TAXI</span></h1>
        <div class="hud-subtitle">Taxi Fleet Control · Highway 0–1000km</div>
      </div>
    </div>
    <div class="hud-status">
      <div class="hud-clock" id="hudClock">00:00:00</div>
      <div class="hud-live" id="hudLive"><span class="dot"></span><span class="label">CONNECTING</span></div>
    </div>
  </header>

  <section class="stat-strip">
    <div class="stat-card amber">
      <div class="stat-label">Total Rides</div>
      <div class="stat-value" id="statTotal" data-raw="0">0</div>
    </div>
    <div class="stat-card cyan">
      <div class="stat-label">Completed</div>
      <div class="stat-value" id="statCompleted" data-raw="0">0</div>
    </div>
    <div class="stat-card">
      <div class="stat-label">Active Now</div>
      <div class="stat-value" id="statActive" data-raw="0">0</div>
    </div>
    <div class="stat-card red">
      <div class="stat-label">Cancelled</div>
      <div class="stat-value" id="statCancelled" data-raw="0">0</div>
    </div>
    <div class="stat-card amber">
      <div class="stat-label">Revenue</div>
      <div class="stat-value" id="statRevenue" data-raw="0">$0</div>
    </div>
    <div class="stat-card pink">
      <div class="stat-label">Online Drivers</div>
      <div class="stat-value" id="statDrivers" data-raw="0">0</div>
    </div>
  </section>

  <section class="panel highway-panel">
    <span class="panel-corner tl"></span>
    <span class="panel-corner br"></span>
    <div class="panel-header">
      <div class="panel-title">Highway Radar <small>fleet positions · live</small></div>
      <div class="panel-note">0 ────────────────────── 1000 KM</div>
    </div>
    <div class="highway-track">
      <div id="zoneBands"></div>
      <div class="highway-centerline"></div>
      <div id="ridePins"></div>
      <div id="driverBlips"></div>
      <div id="kmTicks"></div>
    </div>
  </section>

  <section class="grid-cols">
    <div class="panel">
      <span class="panel-corner tl"></span>
      <span class="panel-corner br"></span>
      <div class="panel-header">
        <div class="panel-title">Dispatch Log</div>
        <button class="btn btn-ghost" id="refreshBtn" style="width:auto;padding:6px 12px;font-size:11px">↻ Refresh</button>
      </div>
      <div class="log-list" id="logList"></div>
    </div>

    <div class="panel">
      <span class="panel-corner tl"></span>
      <span class="panel-corner br"></span>
      <div class="panel-header">
        <div class="panel-title">Console</div>
      </div>

      <div class="console-stack">
        <form id="requestForm">
          <div class="field" style="margin-bottom:10px">
            <label for="passengerSelect">Passenger</label>
            <select id="passengerSelect"></select>
          </div>
          <div class="field-row">
            <div class="field">
              <label for="pickupInput">Pickup (km)</label>
              <input type="number" id="pickupInput" min="0" max="1000" value="150" required>
            </div>
            <div class="field">
              <label for="dropoffInput">Dropoff (km)</label>
              <input type="number" id="dropoffInput" min="0" max="1000" value="620" required>
            </div>
          </div>
          <button type="submit" class="btn btn-amber" id="requestBtn">📻 Request Ride</button>
          <div class="toast" id="formToast"></div>
        </form>

        <div class="tracker">
          <div id="trackerBody"></div>
        </div>
      </div>
    </div>
  </section>

  <section class="zone-legend" id="zoneLegend"></section>

  <footer class="site-footer">
    FIND TAXI · JSON-BACKED DISPATCH SYSTEM · <a href="api/analytics" target="_blank">/api/analytics</a>
  </footer>

</div>

<script src="js/dispatch.js"></script>
</body>
</html>
