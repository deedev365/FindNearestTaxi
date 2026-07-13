(function () {
  'use strict';

  /* ---------------------------------------------------------
     API base resolution — works both mounted at /find-taxi/
     (via the master router) and standalone (php -S from this
     folder directly, where paths are just /api/...).
  --------------------------------------------------------- */
  function computeApiBase() {
    var base = window.location.pathname;
    base = base.replace(/index\.php$/, '');
    base = base.replace(/\/$/, '');
    return base + '/api';
  }
  var API = computeApiBase();

  var STATE = {
    zones: [],
    drivers: [],
    passengers: [],
    rides: [],
    trackedRideId: null,
    pollTimer: null,
    ratedRides: {},
  };

  var STEPS = ['pending', 'assigned', 'picked_up', 'completed'];
  var STEP_LABELS = { pending: 'Requested', assigned: 'Assigned', picked_up: 'Picked Up', completed: 'Completed' };

  /* ---------------------------------------------------------
     Fetch helpers
  --------------------------------------------------------- */
  function api(path, options) {
    return fetch(API + path, Object.assign({ headers: { 'Content-Type': 'application/json' } }, options || {}))
      .then(function (res) {
        return res.json().then(function (json) {
          if (!res.ok || json.success === false) {
            throw new Error((json && json.error) || ('Request failed (' + res.status + ')'));
          }
          return json.data;
        });
      });
  }

  function apiGet(path) { return api(path); }
  function apiPost(path, body) {
    return api(path, { method: 'POST', body: body ? JSON.stringify(body) : undefined });
  }

  /* ---------------------------------------------------------
     Clock + connection indicator
  --------------------------------------------------------- */
  function tickClock() {
    var el = document.getElementById('hudClock');
    if (!el) return;
    var now = new Date();
    var hh = String(now.getHours()).padStart(2, '0');
    var mm = String(now.getMinutes()).padStart(2, '0');
    var ss = String(now.getSeconds()).padStart(2, '0');
    el.textContent = hh + ':' + mm + ':' + ss;
  }

  function setLive(online) {
    var el = document.getElementById('hudLive');
    if (!el) return;
    el.classList.toggle('online', online);
    el.querySelector('.label').textContent = online ? 'LIVE' : 'OFFLINE';
  }

  /* ---------------------------------------------------------
     Number roll-up animation
  --------------------------------------------------------- */
  function animateNumber(el, target, isMoney) {
    var start = parseFloat(el.dataset.raw || '0');
    var duration = 600;
    var startTime = performance.now();
    function frame(now) {
      var p = Math.min(1, (now - startTime) / duration);
      var eased = 1 - Math.pow(1 - p, 3);
      var val = start + (target - start) * eased;
      el.textContent = isMoney ? ('$' + val.toFixed(2)) : Math.round(val).toLocaleString();
      if (p < 1) requestAnimationFrame(frame);
      else el.dataset.raw = String(target);
    }
    requestAnimationFrame(frame);
  }

  /* ---------------------------------------------------------
     Stats
  --------------------------------------------------------- */
  function renderStats(stats) {
    animateNumber(document.getElementById('statTotal'), stats.total_rides);
    animateNumber(document.getElementById('statCompleted'), stats.completed_rides);
    animateNumber(document.getElementById('statActive'), stats.active_rides);
    animateNumber(document.getElementById('statCancelled'), stats.cancelled_rides);
    animateNumber(document.getElementById('statRevenue'), stats.total_revenue, true);
    animateNumber(document.getElementById('statDrivers'), stats.online_drivers);
  }

  /* ---------------------------------------------------------
     Highway radar
  --------------------------------------------------------- */
  function renderZoneBands() {
    var track = document.getElementById('zoneBands');
    track.innerHTML = '';
    STATE.zones.forEach(function (zone) {
      var band = document.createElement('div');
      band.className = 'zone-band';
      var left = (zone.min_position / 1000) * 100;
      var width = ((zone.max_position - zone.min_position) / 1000) * 100;
      band.style.left = left + '%';
      band.style.width = width + '%';
      var label = document.createElement('div');
      label.className = 'zone-label';
      label.textContent = zone.name;
      band.appendChild(label);
      track.appendChild(band);
    });
  }

  function renderKmTicks() {
    var wrap = document.getElementById('kmTicks');
    wrap.innerHTML = '';
    for (var km = 0; km <= 1000; km += 100) {
      var tick = document.createElement('div');
      tick.className = 'km-tick';
      tick.style.left = (km / 1000) * 100 + '%';
      tick.textContent = km;
      wrap.appendChild(tick);
    }
  }

  function renderDriverBlips() {
    var wrap = document.getElementById('driverBlips');
    wrap.innerHTML = '';
    var trackedRide = STATE.rides.find(function (r) { return r.id === STATE.trackedRideId; });
    STATE.drivers.forEach(function (d) {
      var blip = document.createElement('div');
      blip.className = 'blip' + (d.status === 'online' ? ' online' : '');
      blip.style.left = (d.position / 1000) * 100 + '%';
      if (trackedRide && trackedRide.driver_id === d.id) {
        blip.style.boxShadow = '0 0 10px var(--cyan), 0 0 22px var(--cyan-glow)';
        blip.style.background = 'var(--cyan)';
      }
      var tip = document.createElement('div');
      tip.className = 'tip';
      tip.textContent = '#' + d.id + ' ' + d.name + ' · ' + d.position + 'km · ' + Number(d.rating).toFixed(2) + '★';
      blip.appendChild(tip);
      wrap.appendChild(blip);
    });
  }

  function renderTrackedPins() {
    var wrap = document.getElementById('ridePins');
    wrap.innerHTML = '';
    var ride = STATE.rides.find(function (r) { return r.id === STATE.trackedRideId; }) || STATE.activeRide;
    if (!ride) return;

    var p1 = (ride.pickup_position / 1000) * 100;
    var p2 = (ride.dropoff_position / 1000) * 100;
    var left = Math.min(p1, p2);
    var width = Math.abs(p2 - p1);

    var line = document.createElement('div');
    line.className = 'pin-line';
    line.style.left = left + '%';
    line.style.width = width + '%';
    wrap.appendChild(line);

    var pickupPin = document.createElement('div');
    pickupPin.className = 'pin pickup';
    pickupPin.style.left = p1 + '%';
    wrap.appendChild(pickupPin);

    var dropoffPin = document.createElement('div');
    dropoffPin.className = 'pin dropoff';
    dropoffPin.style.left = p2 + '%';
    wrap.appendChild(dropoffPin);
  }

  /* ---------------------------------------------------------
     Dispatch log
  --------------------------------------------------------- */
  function renderLog() {
    var list = document.getElementById('logList');
    list.innerHTML = '';

    if (!STATE.rides.length) {
      list.innerHTML = '<div class="log-empty">No dispatches yet. Request a ride from the console →</div>';
      return;
    }

    STATE.rides.forEach(function (ride) {
      var row = document.createElement('div');
      row.className = 'log-row' + (ride.id === STATE.trackedRideId ? ' active' : '');
      row.innerHTML =
        '<span class="log-id">#' + ride.id + '</span>' +
        '<span class="pill ' + ride.status + '">' + ride.status.replace('_', ' ') + '</span>' +
        '<span class="log-route">' + ride.pickup_position + 'km → ' + ride.dropoff_position + 'km · ' + ride.distance + 'km</span>' +
        '<span class="log-price">$' + Number(ride.price).toFixed(2) + '</span>';
      row.addEventListener('click', function () { trackRide(ride.id); });
      list.appendChild(row);
    });
  }

  /* ---------------------------------------------------------
     Console — passenger select
  --------------------------------------------------------- */
  function renderPassengerSelect() {
    var sel = document.getElementById('passengerSelect');
    sel.innerHTML = '';
    STATE.passengers.forEach(function (p) {
      var opt = document.createElement('option');
      opt.value = p.id;
      opt.textContent = '#' + p.id + ' ' + p.name + ' (★' + Number(p.rating).toFixed(2) + ')';
      sel.appendChild(opt);
    });
  }

  /* ---------------------------------------------------------
     Active ride tracker
  --------------------------------------------------------- */
  function trackRide(rideId) {
    STATE.trackedRideId = rideId;
    renderLog();
    refreshTracker().then(renderDriverBlips);
  }

  function refreshTracker() {
    if (!STATE.trackedRideId) {
      renderTrackerEmpty();
      return Promise.resolve();
    }
    return apiGet('/ride/' + STATE.trackedRideId).then(function (data) {
      STATE.activeRide = data.ride;
      return ensureRatingKnown(data.ride).then(function () {
        renderTracker(data.ride);
        renderTrackedPins();
      });
    }).catch(function (err) {
      showToast(err.message, 'error');
    });
  }

  // Completed rides may already carry a rating from a previous visit/session —
  // check once per ride so the tracker shows the static "already rated" view
  // instead of clickable stars that would silently fail (or double-post) on
  // re-submit.
  function ensureRatingKnown(ride) {
    if (ride.status !== 'completed' || !ride.driver_id || (ride.id in STATE.ratedRides)) {
      return Promise.resolve();
    }
    return apiGet('/ride/' + ride.id + '/ratings').then(function (data) {
      var mine = (data.ratings || []).find(function (r) {
        return r.from_user_id === ride.passenger_id && r.user_type === 'driver';
      });
      if (mine) {
        STATE.ratedRides[ride.id] = mine.score;
      }
    }).catch(function () { /* non-fatal — leave interactive stars as fallback */ });
  }

  function renderTrackerEmpty() {
    document.getElementById('trackerBody').innerHTML =
      '<div class="tracker-empty">No ride selected.<br>Dispatch a new ride or click a log entry to track it here.</div>';
    document.getElementById('ridePins').innerHTML = '';
  }

  function renderTracker(ride) {
    var stepIndex = STEPS.indexOf(ride.status);
    var isCancelled = ride.status === 'cancelled';

    var stepperHtml = '<div class="stepper">' + STEPS.map(function (s, i) {
      var cls = isCancelled ? '' : (i < stepIndex ? 'done' : (i === stepIndex ? 'current' : ''));
      return '<div class="step ' + cls + '"><div class="step-line"></div><div class="step-dot"></div><div class="step-label">' + STEP_LABELS[s] + '</div></div>';
    }).join('') + '</div>';

    if (isCancelled) {
      stepperHtml = '<div class="tracker-status-msg" style="color:var(--red)">✖ CANCELLED' + (ride.cancelled_at ? ' · ' + ride.cancelled_at : '') + '</div>';
    }

    var driverLine = ride.driver_id ? ('#' + ride.driver_id) : '— unassigned —';

    var gridHtml =
      '<div class="tracker-grid">' +
      kv('Passenger', '#' + ride.passenger_id) +
      kv('Driver', driverLine) +
      kv('Route', ride.pickup_position + 'km → ' + ride.dropoff_position + 'km') +
      kv('Distance', ride.distance + ' km') +
      kv('Surge', ride.surge_coefficient + '×') +
      kv('Price', '$' + Number(ride.price).toFixed(2)) +
      '</div>';

    var actionsHtml = renderActions(ride);

    document.getElementById('trackerBody').innerHTML =
      '<div class="tracker-head"><span class="panel-title" style="font-size:16px">Ride #' + ride.id + '</span><span class="tracker-id">' + ride.created_at + '</span></div>' +
      stepperHtml + gridHtml + '<div class="btn-row" id="trackerActions">' + actionsHtml + '</div>' +
      '<div id="ratingBlock"></div>' +
      '<div class="toast" id="trackerToast"></div>';

    wireActions(ride);

    if (ride.status === 'completed') {
      renderRatingBlock(ride);
    }
  }

  function kv(k, v) {
    return '<div class="tracker-kv"><span class="k">' + k + '</span><span class="v">' + v + '</span></div>';
  }

  function renderActions(ride) {
    switch (ride.status) {
      case 'pending':
        return '<button class="btn btn-amber" data-action="accept">Assign Driver</button>' +
               '<button class="btn btn-red" data-action="cancel">Cancel</button>';
      case 'assigned':
        return '<button class="btn btn-amber" data-action="pickup">Pickup Passenger</button>' +
               '<button class="btn btn-red" data-action="cancel">Cancel</button>';
      case 'picked_up':
      case 'in_progress':
        return '<button class="btn btn-amber" data-action="complete">Complete Ride</button>';
      case 'completed':
        return '<button class="btn btn-ghost" data-action="deselect">Clear Tracker</button>';
      case 'cancelled':
        return '<button class="btn btn-ghost" data-action="deselect">Clear Tracker</button>';
      default:
        return '';
    }
  }

  function wireActions(ride) {
    var box = document.getElementById('trackerActions');
    if (!box) return;
    box.querySelectorAll('button[data-action]').forEach(function (btn) {
      btn.addEventListener('click', function () { runAction(ride, btn.dataset.action, btn); });
    });
  }

  function runAction(ride, action, btn) {
    if (action === 'deselect') {
      STATE.trackedRideId = null;
      renderLog();
      renderTrackerEmpty();
      renderDriverBlips();
      return;
    }

    btn.disabled = true;
    var req;
    if (action === 'accept') req = apiPost('/ride/' + ride.id + '/accept');
    else if (action === 'pickup') req = apiPost('/ride/' + ride.id + '/pickup');
    else if (action === 'complete') req = apiPost('/ride/' + ride.id + '/complete');
    else if (action === 'cancel') req = apiPost('/ride/' + ride.id + '/cancel', { reason: 'dispatch_console' });
    else return;

    req.then(function () {
      showTrackerToast('Updated.', 'success');
      return refreshAll();
    }).catch(function (err) {
      showTrackerToast(err.message, 'error');
    }).finally(function () {
      btn.disabled = false;
    });
  }

  function renderRatingBlock(ride) {
    var el = document.getElementById('ratingBlock');
    if (!el) return;
    if (!ride.driver_id) { el.innerHTML = ''; return; }

    var alreadyRated = STATE.ratedRides[ride.id];

    if (alreadyRated) {
      el.innerHTML =
        '<div class="panel-note" style="text-align:center;margin-top:8px">✓ Rated</div>' +
        '<div class="rating-stars">' +
        [1, 2, 3, 4, 5].map(function (n) {
          return '<span class="star' + (n <= alreadyRated ? ' active' : '') + '" style="cursor:default;pointer-events:none">★</span>';
        }).join('') +
        '</div>';
      return;
    }

    el.innerHTML =
      '<div class="panel-note" style="text-align:center;margin-top:8px">Rate the driver</div>' +
      '<div class="rating-stars" id="stars">' +
      [1, 2, 3, 4, 5].map(function (n) { return '<span class="star" data-n="' + n + '">★</span>'; }).join('') +
      '</div>';

    var stars = el.querySelectorAll('.star');
    stars.forEach(function (star) {
      star.addEventListener('click', function () {
        var n = parseInt(star.dataset.n, 10);
        stars.forEach(function (s) { s.classList.toggle('active', parseInt(s.dataset.n, 10) <= n); });
        apiPost('/ride/' + ride.id + '/rate', {
          from_user_id: ride.passenger_id,
          to_user_id: ride.driver_id,
          user_type: 'driver',
          score: n,
          comment: 'Rated from Find Taxi console',
        }).then(function () {
          STATE.ratedRides[ride.id] = n;
          showTrackerToast('Thanks — ' + n + '★ submitted.', 'success');
          renderRatingBlock(ride);
        }).catch(function (err) {
          showTrackerToast(err.message, 'error');
        });
      });
    });
  }

  function showTrackerToast(msg, type) {
    var el = document.getElementById('trackerToast');
    if (!el) return;
    el.textContent = msg;
    el.className = 'toast show ' + type;
    setTimeout(function () { el.classList.remove('show'); }, 3200);
  }

  function showToast(msg) {
    // eslint-disable-next-line no-console
    console.warn(msg);
  }

  /* ---------------------------------------------------------
     Request-ride form
  --------------------------------------------------------- */
  function wireRequestForm() {
    var form = document.getElementById('requestForm');
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var btn = document.getElementById('requestBtn');
      btn.disabled = true;

      var passengerId = parseInt(document.getElementById('passengerSelect').value, 10);
      var pickup = parseInt(document.getElementById('pickupInput').value, 10);
      var dropoff = parseInt(document.getElementById('dropoffInput').value, 10);

      apiPost('/ride/request', {
        passenger_id: passengerId,
        pickup_position: pickup,
        dropoff_position: dropoff,
      }).then(function (data) {
        return refreshAll().then(function () {
          trackRide(data.ride.id);
        });
      }).catch(function (err) {
        showFormToast(err.message);
      }).finally(function () {
        btn.disabled = false;
      });
    });
  }

  function showFormToast(msg) {
    var el = document.getElementById('formToast');
    el.textContent = msg;
    el.className = 'toast show error';
    setTimeout(function () { el.classList.remove('show'); }, 3200);
  }

  /* ---------------------------------------------------------
     Refresh cycle
  --------------------------------------------------------- */
  function refreshAll() {
    return Promise.all([
      apiGet('/analytics').then(function (d) { renderStats(d.stats); }),
      apiGet('/drivers').then(function (d) { STATE.drivers = d.drivers; }),
      apiGet('/rides?limit=20').then(function (d) { STATE.rides = d.rides; renderLog(); }),
    ]).then(function () {
      renderDriverBlips();
      if (STATE.trackedRideId) {
        return refreshTracker();
      }
    }).then(function () {
      setLive(true);
    }).catch(function (err) {
      setLive(false);
      showToast(err.message);
    });
  }

  function bootstrap() {
    tickClock();
    setInterval(tickClock, 1000);

    renderKmTicks();
    renderTrackerEmpty();
    wireRequestForm();

    Promise.all([
      apiGet('/zones').then(function (d) { STATE.zones = d.zones; renderZoneBands(); renderZoneLegend(); }),
      apiGet('/passengers').then(function (d) { STATE.passengers = d.passengers; renderPassengerSelect(); }),
    ]).then(refreshAll).catch(function (err) {
      setLive(false);
      showToast(err.message);
    });

    document.getElementById('refreshBtn').addEventListener('click', function () {
      refreshAll();
    });

    STATE.pollTimer = setInterval(refreshAll, 7000);
  }

  function renderZoneLegend() {
    var wrap = document.getElementById('zoneLegend');
    if (!wrap) return;
    var classes = ['z1', 'z2', 'z3'];
    wrap.innerHTML = STATE.zones.map(function (z, i) {
      return '<div class="zone-chip ' + (classes[i] || '') + '">' +
        '<div class="name">' + z.name + '</div>' +
        '<div class="range">' + z.min_position + 'km – ' + z.max_position + 'km</div>' +
        '<div class="rates"><span>Base <b>$' + Number(z.base_fare).toFixed(2) + '</b></span><span>/km <b>$' + Number(z.price_per_km).toFixed(2) + '</b></span></div>' +
        '</div>';
    }).join('');
  }

  document.addEventListener('DOMContentLoaded', bootstrap);
})();
