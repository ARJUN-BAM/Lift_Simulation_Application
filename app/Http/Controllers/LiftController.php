<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LiftController extends Controller
{


    /** ─────────────────────────────────────────────
     *  BEST LIFT SELECTOR (Future-aware by next reqDirection)
     *  ───────────────────────────────────────────── */
    private function selectBestLift($lifts, int $floor, string $direction)
    {
        $best = null;
        $bestScore = INF;

        foreach ($lifts as $i => $lift) {

            // ✅ If this lift already contains the exact same request → return immediately
            foreach ($lift['queue'] as $req) {
                if ($req['reqFloor'] == $floor && $req['reqDirection'] == $direction) {
                    return $i; // same request already stored → no need to choose another lift
                }
            }

            $pos = $lift['position'];
            $currDir = $lift['direction'];
            $queue = $lift['queue'];

            // Predict future direction of lift
            if (!empty($queue)) {
                $futureDir = $queue[0]['reqDirection'] ?? $currDir;
            } else {
                $futureDir = $currDir;
            }

            // Score calculation
            if ($futureDir === "idle") {
                $score = abs($pos - $floor);
            } elseif ($futureDir === $direction) {
                if (($direction === "up"   && $pos <= $floor) ||
                    ($direction === "down" && $pos >= $floor)
                ) {
                    $score = abs($pos - $floor);
                } else {
                    $score = abs($pos - $floor) + 25;
                }
            } else {
                $score = abs($pos - $floor) + 60;
            }

            if ($score < $bestScore) {
                $bestScore = $score;
                $best = $i;
            }
        }

        return $best;
    }


    /** ─────────────────────────────────────────────
     *  OUTSIDE REQUEST → /lifts  (User waiting on floor)
     *  ───────────────────────────────────────────── */
    public function requestLift(Request $req)
    {
        $validated = $req->validate([
            'current_floor' => 'required|integer|between:-4,12',
            'direction'     => 'required|in:up,down'
        ]);

        $floor     = (int)$validated['current_floor'];
        $direction = $validated['direction'];

        if ($floor == 12 && $direction == 'up')  return response()->json(['error' => 'Top floor — cannot go up'], 400);
        if ($floor == -4 && $direction == 'down') return response()->json(['error' => 'Bottom floor — cannot go down'], 400);

        $lock = fopen(storage_path('app/lifts.lock'), 'c+');
        flock($lock, LOCK_EX);

        $liftsPath = storage_path('app/lifts.json');
        $lifts     = json_decode(file_get_contents($liftsPath), true);

        // 🔥 Choose best lift by future-aware scoring
        $index  = $this->selectBestLift($lifts, $floor, $direction);
        $liftId = $lifts[$index]['id'];

        // 🔥 Prevent duplicates
        foreach ($lifts[$index]['queue'] as $reqItem) {
            if ($reqItem['reqFloor'] == $floor && $reqItem['reqDirection'] == $direction) {
                flock($lock, LOCK_UN);
                fclose($lock);
                return response()->json(['lift_id' => $liftId, 'status' => 'already_queued']);
            }
        }

        // 🔥 Add request to queue
        $lifts[$index]['queue'][] = [
            "reqFloor"     => $floor,
            "reqDirection" => $direction
        ];

        // 🔥 First request decides direction immediately
        if (count($lifts[$index]['queue']) === 1) {
            $lifts[$index]['direction'] = $direction;
        }

        file_put_contents($liftsPath, json_encode($lifts, JSON_PRETTY_PRINT));
        flock($lock, LOCK_UN);
        fclose($lock);

        return response()->json([
            "lift_id" => $liftId,
            "status"  => "queued"
        ]);
    }



    /** ─────────────────────────────────────────────
     *  INSIDE REQUEST  → /lifts/{id}
     *  reqDirection = 'idle'
     *  Does NOT change lift direction — engine handles it
     *  ───────────────────────────────────────────── */
    public function insideLift(Request $req, $id)
    {
        $destinations = $req->input("destinations", []);
        if (!is_array($destinations)) return response()->json(['error' => 'destinations must be an array'], 400);

        $lock = fopen(storage_path('app/lifts.lock'), 'c+');
        flock($lock, LOCK_EX);

        $liftsPath = storage_path('app/lifts.json');
        $lifts = json_decode(file_get_contents($liftsPath), true);

        $index = $id - 1;
        if (!isset($lifts[$index])) return response()->json(['error' => 'invalid lift id'], 404);

        foreach ($destinations as $floor) {
            $exists = false;
            foreach ($lifts[$index]['queue'] as $task) {
                if ($task['reqFloor'] == $floor) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $lifts[$index]['queue'][] = [
                    "reqFloor"     => $floor,
                    "reqDirection" => "idle"    // ❗ inside request does not affect lift direction
                ];
            }
        }

        file_put_contents($liftsPath, json_encode($lifts, JSON_PRETTY_PRINT));
        flock($lock, LOCK_UN);
        fclose($lock);

        return response()->json([
            "lift_id" => $id,
            "queue"   => $lifts[$index]['queue']
        ]);
    }




    /** ─────────────────────────────────────────────
     *  CANCEL REQUEST  → /lifts/{id}/cancel
     *  ───────────────────────────────────────────── */
    public function cancelLift(Request $req, $id)
    {
        $destinations = $req->input("destinations", []);
        if (!is_array($destinations)) return response()->json(['error' => 'destinations must be an array'], 400);

        $lock = fopen(storage_path('app/lifts.lock'), 'c+');
        flock($lock, LOCK_EX);

        $liftsPath = storage_path('app/lifts.json');
        $lifts = json_decode(file_get_contents($liftsPath), true);

        $index = $id - 1;
        if (!isset($lifts[$index])) return response()->json(['error' => 'invalid lift id'], 404);

        $lifts[$index]['queue'] = array_values(array_filter($lifts[$index]['queue'], function ($task) use ($destinations) {
            return !in_array($task['reqFloor'], $destinations);
        }));

        if (empty($lifts[$index]['queue'])) {
            $lifts[$index]['direction'] = "idle";
        }

        file_put_contents($liftsPath, json_encode($lifts, JSON_PRETTY_PRINT));
        flock($lock, LOCK_UN);
        fclose($lock);

        return response()->json([
            "status"   => "cancelled",
            "lift_id"  => $id,
            "queue"    => $lifts[$index]['queue']
        ]);
    }







    /** ─────────────────────────────────────────────
     *  PUBLIC STATUS  → /lifts/all-lifts
     *  ───────────────────────────────────────────── */
    public function getAllLifts()
    {
        $liftsPath = storage_path('app/lifts.json');
        $lifts = json_decode(file_get_contents($liftsPath), true);

        return response()->json([
            "lifts" => $lifts
        ]);
    }




    public function resetLifts()
    {
        $lockPath        = storage_path('app/lifts.lock');
        $liftsPath  = storage_path('app/lifts.json');
        $lockFp = fopen($lockPath, 'c+');
        flock($lockFp, LOCK_EX);
        file_put_contents($liftsPath, json_encode([
            ["id" => 1, "position" => -4, "direction" => "idle", "queue" => []],
            ["id" => 2, "position" => -4, "direction" => "idle", "queue" => []],
            ["id" => 3, "position" => -4, "direction" => "idle", "queue" => []],
            ["id" => 4, "position" => -4, "direction" => "idle", "queue" => []]
        ], JSON_PRETTY_PRINT));
        flock($lockFp, LOCK_UN);
        fclose($lockFp);

        return response()->json(
            [
                "message" => "Lift data has been reset."
            ]
        );
    }
}
