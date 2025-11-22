<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Lift Control Panel</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            margin: 0;
            padding: 0;
            background: #111827;
            color: #e5e7eb;
            display: flex;
            height: 100vh;
        }

        .app-container {
            display: flex;
            flex: 1;
            padding: 16px;
            gap: 16px;
        }

        .lifts-panel {
            flex: 3;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            border-right: 1px solid #374151;
            padding-right: 12px;
        }

        .lift-card {
            background: #1f2937;
            border-radius: 12px;
            padding: 10px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            box-shadow: 0 0 0 1px #111827, 0 8px 16px rgba(0, 0, 0, 0.5);
        }

        .lift-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        }

        .lift-name {
            font-weight: 600;
        }

        .lift-status {
            font-size: 12px;
            opacity: 0.8;
        }

        .lift-shaft {
            position: relative;
            background: #020617;
            border-radius: 8px;
            padding: 6px;
            display: flex;
            flex-direction: column-reverse;
            gap: 2px;
            height: 75%;
            overflow: hidden;
        }

        .floor-cell {
            flex: 1;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 6px;
            font-size: 11px;
            color: #9ca3af;
            border: 1px solid transparent;
        }

        .floor-label {
            opacity: 0.8;
            font-size: 20px;
        }

        .floor-cell.active {
            background: #22c55e22;
            border-color: #22c55e;
            color: #bbf7d0;
        }

        .car-indicator {
            font-size: 10px;
            opacity: 0.8;
        }

        .side-panel {
            flex: 2;
            display: flex;
            flex-direction: column;
            gap: 14px;
            padding-left: 8px;
        }

        .panel-card {
            background: #1f2937;
            border-radius: 12px;
            padding: 12px;
            box-shadow: 0 0 0 1px #111827, 0 8px 16px rgba(0, 0, 0, 0.5);
        }

        .panel-title {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .field-group {
            margin-bottom: 8px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            font-size: 13px;
        }

        label {
            font-size: 12px;
            opacity: 0.8;
        }

        select,
        input[type="number"] {
            background: #020617;
            border-radius: 8px;
            border: 1px solid #374151;
            padding: 4px 8px;
            color: #e5e7eb;
            font-size: 13px;
            outline: none;
        }

        select:focus,
        input[type="number"]:focus {
            border-color: #22c55e;
        }

        .btn-row {
            display: flex;
            gap: 8px;
            margin-top: 4px;
        }

        .btn {
            padding: 4px 10px;
            border-radius: 999px;
            border: 1px solid #374151;
            background: #111827;
            color: #e5e7eb;
            font-size: 12px;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s, transform 0.05s;
        }

        .btn:hover {
            background: #1f2937;
            border-color: #22c55e;
        }

        .btn:active {
            transform: scale(0.97);
        }

        .btn-primary {
            background: #22c55e;
            border-color: #16a34a;
            color: #022c22;
            font-weight: 600;
        }

        .btn-primary:hover {
            background: #16a34a;
        }

        .btn-danger {
            background: #b91c1c;
            border-color: #ef4444;
            color: #fee2e2;
        }

        .floor-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 6px;
        }

        .floor-btn {
            min-width: 32px;
            padding: 4px 6px;
            border-radius: 999px;
            border: 1px solid #374151;
            background: #020617;
            color: #e5e7eb;
            font-size: 11px;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s, transform 0.05s;
        }

        .floor-btn.active {
            background: #22c55e;
            border-color: #16a34a;
            color: #022c22;
        }

        .floor-btn:hover {
            background: #111827;
        }

        .hint {
            font-size: 11px;
            opacity: 0.7;
            margin-top: 4px;
        }

        .status-log {
            font-size: 11px;
            max-height: 120px;
            overflow-y: auto;
            padding-right: 4px;
            margin-top: 4px;
            scroll-behavior: smooth;
            /* 🔥 enables smooth auto scroll */
        }


        .status-line {
            opacity: 0.8;
            margin-bottom: 2px;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <div class="app-container">
        <!-- Lifts display -->
        <div class="lifts-panel" id="liftsPanel">
            <!-- JS will fill -->
        </div>

        <!-- Side controls -->
        <div class="side-panel">
            <!-- Lift selection + outside call -->
            <div class="panel-card">
                <div class="panel-title">Call a Lift (Outside)</div>

                <div class="field-group">
                    <label for="callFloorSelect">Your Floor</label>
                    <select id="callFloorSelect">
                        <!-- JS fills floors B2–12 -->
                    </select>
                </div>

                <div class="btn-row">
                    <button class="btn btn-primary" id="btnCallUp">Call ↑</button>
                    <button class="btn btn-primary" id="btnCallDown">Call ↓</button>
                </div>

                <p class="hint">
                    Calls the best lift using <code>POST /lifts</code>.
                    After calling, the floor panel below will target the assigned lift.
                </p>
            </div>

            <!-- Inside panel -->
            <div class="panel-card">
                <div class="panel-title">Inside Lift Panel</div>

                <div class="field-group">
                    <label for="liftSelect">Control Lift ID</label>
                    <select id="liftSelect">
                        <option value="1">Lift 1</option>
                        <option value="2">Lift 2</option>
                        <option value="3">Lift 3</option>
                        <option value="4">Lift 4</option>
                    </select>
                </div>

                <div class="field-group">
                    <label>Floor Buttons (click = request, double-click = cancel)</label>
                    <div class="floor-buttons" id="floorButtons">
                        <!-- JS fills B2–12 -->
                    </div>
                    <p class="hint">
                        Single click → <code>POST /lifts/{lift_id}</code> with <code>{ destinations: [floor] }</code>.<br>
                        Double click within 0.5s → <code>POST /lifts/{lift_id}/cancel</code>.
                    </p>
                </div>
            </div>

            <!-- Status panel -->
            <div class="panel-card">
                <div class="panel-title">Live Status</div>
                <div class="status-log" id="statusLog"></div>
                <p class="hint">
                    Polls <code>GET /lifts/status</code> every second and updates lift positions
                    without refreshing the page or clearing your inputs.
                </p>
            </div>
        </div>
    </div>

    <script>
        function refreshFloorButtonGlow(selectedLiftId, lifts) {
            const lift = lifts.find(l => l.id == selectedLiftId);
            if (!lift) return;

            const queue = lift.queue || [];

            FLOORS.forEach(floor => {
                const btn = floorButtonsContainer.querySelector(`.floor-btn[data-floor="${floor}"]`);
                if (!btn) return;

                if (queue.includes(floor)) {
                    btn.classList.add("active");
                } else {
                    btn.classList.remove("active");
                }
            });
        }

        // ====== CONFIG ======
        // If your routes are under /api, change to '/api'
        const API_BASE = '/api'; // '' if routes like /lifts, '/api' if /api/lifts

        // Floors from B2 (-2) to 12
        const FLOORS = [];
        for (let f = -4; f <= 12; f++) {
            FLOORS.push(f);
        }

        function floorLabel(f) {
            if (f === -4) return 'B2';
            if (f === -3) return 'B1';
            if (f === -2) return 'LG';
            if (f === -1) return 'G';
            if (f === 0) return 'UG';
            return String(f);
        }

        // ====== DOM ELEMENTS ======
        const liftsPanel = document.getElementById('liftsPanel');
        const callFloorSelect = document.getElementById('callFloorSelect');
        const btnCallUp = document.getElementById('btnCallUp');
        const btnCallDown = document.getElementById('btnCallDown');
        const liftSelect = document.getElementById('liftSelect');
        const floorButtonsContainer = document.getElementById('floorButtons');
        const statusLog = document.getElementById('statusLog');

        // For double-click (within 500ms) cancel detection
        const lastClickMap = {}; // key: `${liftId}:${floor}` → timestamp

        // For UI active states of floor buttons per lift
        const activeFloorRequests = {}; // liftId => Set of floors

        // ====== INITIAL RENDER OF UI STRUCTURE ======

        function initFloorSelect() {
            FLOORS.slice().reverse().forEach(floor => {
                const opt = document.createElement('option');
                opt.value = floor;
                opt.textContent = floorLabel(floor);
                callFloorSelect.appendChild(opt);
            });
        }

        function initLiftsGrid() {
            liftsPanel.innerHTML = '';
            for (let id = 1; id <= 4; id++) {
                const card = document.createElement('div');
                card.className = 'lift-card';
                card.id = `lift-card-${id}`;

                const header = document.createElement('div');
                header.className = 'lift-header';
                header.innerHTML = `
        <div class="lift-name">Lift ${id}</div>
        <div class="lift-status">
          <span id="lift-${id}-pos">Floor ?</span> ·
          <span id="lift-${id}-dir">Idle</span>
        </div>
      `;

                const shaft = document.createElement('div');
                shaft.className = 'lift-shaft';

                FLOORS.forEach(floor => {
                    const cell = document.createElement('div');
                    cell.className = 'floor-cell';
                    cell.dataset.floor = floor;
                    cell.id = `lift-${id}-floor-${floor}`;
                    cell.innerHTML = `
          <span class="floor-label">${floorLabel(floor)}</span>
          <span class="car-indicator"></span>
        `;
                    shaft.appendChild(cell);
                });

                card.appendChild(header);
                card.appendChild(shaft);
                liftsPanel.appendChild(card);
            }
        }

        function initFloorButtonsPanel() {
            floorButtonsContainer.innerHTML = '';
            FLOORS.slice().reverse().forEach(floor => {
                const btn = document.createElement('button');
                btn.className = 'floor-btn';
                btn.dataset.floor = floor;
                btn.textContent = floorLabel(floor);
                btn.addEventListener('click', onFloorButtonClick);
                floorButtonsContainer.appendChild(btn);
            });
        }

        // ====== EVENT HANDLERS ======

        async function callLift(direction) {
            const floorValue = parseInt(callFloorSelect.value, 10);

            try {
                const res = await fetch(`${API_BASE}/lifts`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        current_floor: floorValue,
                        direction: direction
                    })
                });

                const data = await res.json();

                if (!res.ok) {
                    logStatus(`❌ Call error: ${data.error || res.status}`, true);
                    return;
                }

                logStatus(`✔ Called lift from floor ${floorLabel(floorValue)} (${direction.toUpperCase()}), assigned Lift ${data.lift_id}`);

                // Set active lift to the one assigned by backend
                liftSelect.value = data.lift_id;
            } catch (err) {
                console.error(err);
                logStatus('❌ Network error while calling lift', true);
            }
        }

        async function onFloorButtonClick(e) {
            const floor = parseInt(e.currentTarget.dataset.floor, 10);
            const liftId = parseInt(liftSelect.value, 10);
            const key = `${liftId}:${floor}`;
            const now = Date.now();
            const last = lastClickMap[key] || 0;

            if (now - last < 500) {
                // Double click within 0.5s → cancel
                delete lastClickMap[key];
                await cancelFloor(liftId, floor);
            } else {
                lastClickMap[key] = now;
                await requestFloor(liftId, floor);
            }
        }

        async function requestFloor(liftId, floor) {
            try {
                const res = await fetch(`${API_BASE}/lifts/${liftId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        destinations: [floor]
                    })
                });

                const data = await res.json();

                if (!res.ok) {
                    logStatus(`❌ Inside request error: ${data.error || res.status}`, true);
                    return;
                }

                logStatus(`⬆ Added floor ${floorLabel(floor)} to Lift ${liftId} queue`);
                toggleFloorButtonActive(liftId, floor, true);
            } catch (err) {
                console.error(err);
                logStatus('❌ Network error while requesting floor', true);
            }
        }

        async function cancelFloor(liftId, floor) {
            try {
                const res = await fetch(`${API_BASE}/lifts/${liftId}/cancel`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        destinations: [floor]
                    })
                });

                const data = await res.json();

                if (!res.ok) {
                    logStatus(`❌ Cancel error: ${data.error || res.status}`, true);
                    return;
                }

                logStatus(`✖ Cancelled floor ${floorLabel(floor)} from Lift ${liftId}`);
                toggleFloorButtonActive(liftId, floor, false);
            } catch (err) {
                console.error(err);
                logStatus('❌ Network error while cancelling floor', true);
            }
        }

        // ====== STATUS POLLING ======

        async function fetchStatus() {
            try {
                const res = await fetch(`${API_BASE}/lifts/all-lifts`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                const data = await res.json();
                if (!res.ok) {
                    logStatus(`❌ Status error: ${data.error || res.status}`, true);
                    return;
                }

                updateLiftsUI(data.lifts || []);
            } catch (err) {
                console.error(err);
                logStatus('❌ Network error while fetching status', true);
            }
        }

        function updateLiftsUI(lifts) {
            lifts.forEach(lift => {
                const id = lift.id;
                const pos = lift.position;
                const dir = lift.direction;

                // Update header
                const posEl = document.getElementById(`lift-${id}-pos`);
                const dirEl = document.getElementById(`lift-${id}-dir`);
                if (posEl) posEl.textContent = `Floor ${floorLabel(pos)}`;
                if (dirEl) dirEl.textContent = dir === 'idle' ?
                    'Idle' :
                    (dir === 'up' ? '↑ Up' : '↓ Down');

                // Update floors in shaft
                FLOORS.forEach(floor => {
                    const cell = document.getElementById(`lift-${id}-floor-${floor}`);
                    if (!cell) return;
                    const indicator = cell.querySelector('.car-indicator');

                    if (floor === pos) {
                        cell.classList.add('active');
                        indicator.textContent = '🟩';
                    } else {
                        cell.classList.remove('active');
                        indicator.textContent = '';
                    }
                    refreshFloorButtonGlow(parseInt(liftSelect.value), lifts);

                });

                // Also sync active floor buttons with queue if you want:
                // if (Array.isArray(lift.queue)) { ... }
            });
        }

        // ====== UI HELPERS ======

        function toggleFloorButtonActive(liftId, floor, isActive) {
            const btn = floorButtonsContainer.querySelector(`.floor-btn[data-floor="${floor}"]`);
            if (!btn) return;

            const key = String(liftId);
            if (!activeFloorRequests[key]) {
                activeFloorRequests[key] = new Set();
            }

            if (isActive) {
                activeFloorRequests[key].add(floor);
                btn.classList.add('active');
            } else {
                activeFloorRequests[key].delete(floor);
                btn.classList.remove('active');
            }
        }

        function logStatus(msg, isError = false) {
            const line = document.createElement('div');
            line.className = 'status-line';
            line.textContent = `[${new Date().toLocaleTimeString()}] ${msg}`;
            if (isError) {
                line.style.color = '#fca5a5';
            }
            statusLog.prepend(line);

            // Limit log size
            const children = statusLog.children;
            if (children.length > 80) {
                statusLog.removeChild(children[children.length - 1]);
            }
        }

        // ====== BOOTSTRAP ======

        function init() {
            initFloorSelect();
            initLiftsGrid();
            initFloorButtonsPanel();

            btnCallUp.addEventListener('click', () => callLift('up'));
            btnCallDown.addEventListener('click', () => callLift('down'));

            // 🔥 When user changes the selected lift, update floor button glow
            liftSelect.addEventListener("change", () => {
                fetchStatus(); // re-poll status to update glow instantly
            });

            // Start polling status
            fetchStatus();
            setInterval(fetchStatus, 1000);
        }


        document.addEventListener('DOMContentLoaded', init);
    </script>
</body>

</html>