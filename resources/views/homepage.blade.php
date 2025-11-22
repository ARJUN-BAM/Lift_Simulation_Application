<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Lift Control UI</title>
    <style>
        /* ---- Styling (same as earlier, shortened for readability) ---- */
        body {
            margin: 0;
            background: #111827;
            color: #e5e7eb;
            font-family: Arial;
            display: flex;
        }

        .app {
            display: flex;
            gap: 16px;
            flex: 1;
            padding: 12px;
        }

        .lifts {
            flex: 3;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .card {
            background: #1f2937;
            padding: 10px;
            border-radius: 10px;
            box-shadow: 0 0 8px #0008;
        }

        .shaft {
            background: #020617;
            height: 350px;
            overflow: hidden;
            display: flex;
            flex-direction: column-reverse;
            border-radius: 6px;
        }

        .cell {
            flex: 1;
            display: flex;
            justify-content: space-between;
            padding: 0 6px;
            font-size: 11px;
            color: #9ca3af;
            border: 1px solid transparent;
        }

        .cell.active {
            background: #1e3a8a;
            border-color: #38bdf8;
            color: white;
        }

        .side {
            flex: 2;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .panel {
            background: #1f2937;
            padding: 12px;
            border-radius: 10px;
            box-shadow: 0 0 8px #0008;
        }

        select,
        button {
            border-radius: 6px;
            padding: 6px 8px;
            font-size: 14px;
        }

        .btn {
            cursor: pointer;
            border: 1px solid #374151;
            background: #111827;
            color: #e5e7eb;
        }

        .btn:hover {
            background: #1f2937;
            border-color: #22c55e;
        }

        .btn-primary {
            background: #22c55e;
            color: #022c22;
        }

        .floor-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: 8px;
        }

        .floor-btn {
            min-width: 30px;
            padding: 4px;
            border: 1px solid #374151;
            background: #020617;
            color: #e5e7eb;
            font-size: 11px;
            border-radius: 5px;
            cursor: pointer;
        }

        .floor-btn.active {
            background: #22c55e;
            border-color: #16a34a;
            color: #022c22;
        }

        .log {
            height: 100px;
            overflow-y: auto;
            font-size: 12px;
        }
    </style>
</head>

<body>

    <div class="app">
        <!-- Lifts Display -->
        <div class="lifts" id="liftsPanel"></div>

        <!-- Controls -->
        <div class="side">

            <!-- Outside Call -->
            <div class="panel">
                <h3>Call a Lift</h3>
                <label>Your Floor:</label>
                <select id="callFloorSelect"></select>
                <br><br>
                <button class="btn btn-primary" id="btnCallUp">Call ↑</button>
                <button class="btn btn-primary" id="btnCallDown">Call ↓</button>
            </div>

            <!-- Inside Lift -->
            <div class="panel">
                <h3>Inside Controls</h3>
                <label>Select Lift:</label>
                <select id="liftSelect">
                    <option value="1">Lift 1</option>
                    <option value="2">Lift 2</option>
                    <option value="3">Lift 3</option>
                    <option value="4">Lift 4</option>
                </select>

                <div class="floor-buttons" id="floorButtons"></div>
                <p style="font-size: 11px; opacity: 0.7;">
                    Single click = request floor<br>
                    Double-click within 0.5 sec = cancel floor
                </p>
            </div>

            <!-- Logs -->
            <div class="panel">
                <h3>Status Log</h3>
                <div class="log" id="statusLog"></div>
            </div>
        </div>
    </div>

    <script>
        // ====== CONFIG ======
        const FLOORS = [];
        for (let f = 12; f >= -2; f--) FLOORS.push(f);
        let lastJsonHash = "";

        // ====== DOM refs ======
        const liftsPanel = document.getElementById("liftsPanel");
        const callFloorSelect = document.getElementById("callFloorSelect");
        const btnCallUp = document.getElementById("btnCallUp");
        const btnCallDown = document.getElementById("btnCallDown");
        const liftSelect = document.getElementById("liftSelect");
        const floorButtons = document.getElementById("floorButtons");
        const statusLog = document.getElementById("statusLog");

        const lastClick = {};

        // ====== Floor labels ======
        const label = f => f === 0 ? "G" : f === -1 ? "B1" : f === -2 ? "B2" : f;

        // ====== Init UI ======
        function initFloorSelector() {
            FLOORS.slice().reverse().forEach(f => {
                const opt = document.createElement("option");
                opt.value = f;
                opt.textContent = label(f);
                callFloorSelect.appendChild(opt);
            });
        }

        function initLiftGrid() {
            liftsPanel.innerHTML = "";
            for (let id = 1; id <= 4; id++) {
                const card = document.createElement("div");
                card.className = "card";
                card.innerHTML = `
            <h4>Lift ${id}</h4>
            <div id="header-${id}">Floor ?, Idle</div>
            <div class="shaft">
                ${FLOORS.map(f => `
                  <div class="cell" id="c-${id}-${f}">
                    <span>${label(f)}</span>
                    <span class="car"></span>
                  </div>`).join("")}
            </div>`;
                liftsPanel.appendChild(card);
            }
        }

        function initInsideButtons() {
            FLOORS.slice().reverse().forEach(f => {
                const btn = document.createElement("button");
                btn.className = "floor-btn";
                btn.textContent = label(f);
                btn.dataset.floor = f;
                btn.onclick = handleFloorBtn;
                floorButtons.appendChild(btn);
            });
        }

        // ====== Outside Lift Call ======
        async function callLift(dir) {
            const floor = parseInt(callFloorSelect.value);
            log(`Calling lift at floor ${label(floor)} (${dir})...`);

            const res = await fetch("/lifts", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    current_floor: floor,
                    direction: dir
                })
            });
            const data = await res.json();
            if (!res.ok) return log("❌ " + data.error, true);

            liftSelect.value = data.lift_id;
            log(`✔ Lift ${data.lift_id} assigned (ETA: ${data.arrival_time}s)`);
        }

        // ====== Inside lift button ======
        async function handleFloorBtn(e) {
            const floor = parseInt(e.target.dataset.floor);
            const lift = parseInt(liftSelect.value);
            const key = `${lift}-${floor}`;
            const now = Date.now();

            // double-click cancel
            if (now - (lastClick[key] || 0) < 500) {
                lastClick[key] = 0;
                cancelFloor(lift, floor);
            } else {
                lastClick[key] = now;
                requestFloor(lift, floor);
            }
        }

        async function requestFloor(lift, floor) {
            log(`Requesting floor ${label(floor)} on Lift ${lift}`);
            const res = await fetch(`/lifts/${lift}`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    destinations: [floor]
                })
            });
            const data = await res.json();
            if (!res.ok) return log("❌ " + data.error, true);
            log(`⬆ Added floor ${label(floor)} to Lift ${lift}`);
        }

        async function cancelFloor(lift, floor) {
            log(`Cancelling floor ${label(floor)} on Lift ${lift}`);
            const res = await fetch(`/lifts/${lift}/cancel`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    destinations: [floor]
                })
            });
            const data = await res.json();
            if (!res.ok) return log("❌ " + data.error, true);
            log(`✖ Removed floor ${label(floor)} from Lift ${lift}`);
        }

        // ====== Polling Status ======
        async function pollStatus() {
            const res = await fetch("/lifts/all-lifts");
            const lifts = await res.json(); // pure array
            console.log(lifts);
            const hash = JSON.stringify(lifts);
            if (hash === lastJsonHash) return; // no change — do nothing
            lastJsonHash = hash;

            updateUI(lifts);
        }

        function updateUI(lifts) {
            lifts.forEach(l => {
                document.getElementById(`header-${l.id}`).textContent =
                    `Floor ${label(l.position)}, ` + (l.direction === "idle" ? "Idle" : l.direction.toUpperCase());

                FLOORS.forEach(f => {
                    const c = document.getElementById(`c-${l.id}-${f}`);
                    c.classList.toggle("active", f === l.position);
                });
            });
        }

        // ====== Logging ======
        function log(msg, err = false) {
            const row = document.createElement("div");
            row.textContent = `[${new Date().toLocaleTimeString()}] ${msg}`;
            if (err) row.style.color = "#e57373";
            statusLog.prepend(row);
        }

        // ====== Start App ======
        document.addEventListener("DOMContentLoaded", () => {
            initFloorSelector();
            initLiftGrid();
            initInsideButtons();
            btnCallUp.onclick = () => callLift("up");
            btnCallDown.onclick = () => callLift("down");
            pollStatus();
            setInterval(pollStatus, 1000);
        });
    </script>
</body>

</html>