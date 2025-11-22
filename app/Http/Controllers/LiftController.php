<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LiftRequestDispatcher;
use Illuminate\Routing\Controller;

class LiftController extends Controller
{
    public function requestLift(Request $req)
    {
        $validated = $req->validate([
            'current_floor' => 'required|integer|between:-4,12',
            'direction'     => 'required|in:up,down'
        ]);

        $floor = (int)$validated['current_floor'];
        $direction = $validated['direction'];

        if (!in_array($direction, ['up', 'down'])) {
            return response()->json(['error' => 'invalid direction'], 400);
        }

        if ($floor < config('constants.MIN_FLOOR') || $floor > config('constants.MAX_FLOOR')) {
            return response()->json(['error' => 'floor out of range'], 400);
        }

        if ($floor == 12 && $direction == 'up') {
            return response()->json(['error' => 'Last Floor- Cant go upward'], 400);
        }
        if ($floor == -4 && $direction == 'down') {
            return response()->json(['error' => 'First Floor-cant go downward'], 400);
        }
        // === Read current lift states ===
        $liftsPath = storage_path('app/lifts.json');
        $lifts = json_decode(file_get_contents($liftsPath), true);

        // === Calculate time for each lift ===
        $bestLiftIndex = null;
        $bestTime = PHP_INT_MAX;

        foreach ($lifts as $i => $lift) {
            $timeNeeded = $this->calculateArrivalTime($lift, $floor);

            if ($timeNeeded < $bestTime) {
                $bestTime = $timeNeeded;
                $bestLiftIndex = $i;
            }
        }

        // === Assign request floor to the best lift ===
        $chosenLift = $bestLiftIndex + 1; // convert index -> id
        $this->assignLiftByTime($lifts, $floor, $bestLiftIndex);

        // Save updated lifts.json
        file_put_contents($liftsPath, json_encode($lifts, JSON_PRETTY_PRINT));

        return response()->json([
            'lift_id' => $chosenLift,
            'arrival_time' => $bestTime . 'sec'
        ]);
    }


    public function insideLift(Request $req, $id)
    {
        $destinations = $req->input('destinations', []);

        if (!is_array($destinations)) {
            return response()->json(['error' => 'destinations must be an array'], 400);
        }

        $lock = fopen(storage_path('app/lifts.lock'), 'c+');
        flock($lock, LOCK_EX);

        $liftsPath = storage_path('app/lifts.json');
        $lifts = json_decode(file_get_contents($liftsPath), true);

        $index = $id - 1;

        if (!isset($lifts[$index])) {
            flock($lock, LOCK_UN);
            fclose($lock);
            return response()->json(['error' => 'invalid lift id'], 404);
        }

        // Add each destination, avoid duplicates
        foreach ($destinations as $floor) {
            if (!in_array($floor, $lifts[$index]['queue'])) {
                $lifts[$index]['queue'][] = $floor;
            }
        }

        // Save back
        file_put_contents($liftsPath, json_encode($lifts, JSON_PRETTY_PRINT));

        flock($lock, LOCK_UN);
        fclose($lock);

        return response()->json([
            'lift_id' => $id,
            'queue' => $lifts[$index]['queue']
        ]);
    }



    private function calculateArrivalTime($lift, $requestFloor)
    {
        $floorTime = config('constants.LIFT_TRAVELLING_TIME');    // seconds per floor
        $doorTime  = config('constants.LIFT_OPENING_TIME') + config('constants.LIFT_CLOSING_TIME');  // door open/close time

        $pos = $lift['position'];
        $dir = $lift['direction'];
        $queue = $lift['queue'];

        // If idle and no queue → simple case
        if ($dir === "idle" && empty($queue)) {
            return abs($pos - $requestFloor) * $floorTime;
        }

        // Simulate travel through queue
        $simPos = $pos;
        $time = 0;

        foreach ($queue as $target) {
            $time += abs($simPos - $target) * $floorTime;
            $time += $doorTime;
            $simPos = $target;
        }

        // After finishing queue → go to request floor
        $time += abs($simPos - $requestFloor) * $floorTime;

        return $time;
    }
    private function assignLiftByTime(&$lifts, $requestFloor, $index)
    {
        // Add request floor to only that lift
        if (!in_array($requestFloor, $lifts[$index]['queue'])) {
            $lifts[$index]['queue'][] = $requestFloor;
        }

        // Set new direction if idle
        if ($lifts[$index]['direction'] === "idle") {
            $lifts[$index]['direction'] =
                $lifts[$index]['position'] < $requestFloor ? "up" : "down";
        }
    }


    public function cancelLift(Request $req, $id)
    {
        $destinations = $req->input('destinations', []);

        if (!is_array($destinations)) {
            return response()->json(['error' => 'destinations must be an array'], 400);
        }

        // Floor validation (-4 to 16)
        foreach ($destinations as $floor) {
            if (!is_int($floor) && !ctype_digit($floor)) {
                return response()->json(['error' => 'Invalid floor value'], 400);
            }
            $floor = (int)$floor;
            if ($floor < -4 || $floor > 16) {
                return response()->json(['error' => "Floor $floor out of range"], 400);
            }
        }

        // Acquire lock (important!)
        $lock = fopen(storage_path('app/lifts.lock'), 'c+');
        flock($lock, LOCK_EX);

        $liftsPath = storage_path('app/lifts.json');
        $lifts = json_decode(file_get_contents($liftsPath), true);

        $index = $id - 1;

        if (!isset($lifts[$index])) {
            flock($lock, LOCK_UN);
            fclose($lock);
            return response()->json(['error' => 'invalid lift id'], 404);
        }

        // Remove matched floors from queue
        $newQueue = array_filter($lifts[$index]['queue'], function ($floor) use ($destinations) {
            return !in_array($floor, $destinations);
        });

        // Re-index array
        $lifts[$index]['queue'] = array_values($newQueue);

        // If queue empty → set lift idle
        if (empty($lifts[$index]['queue'])) {
            $lifts[$index]['direction'] = "idle";
        }

        // Save updated lifts.json
        file_put_contents($liftsPath, json_encode($lifts, JSON_PRETTY_PRINT));

        flock($lock, LOCK_UN);
        fclose($lock);

        return response()->json([
            'status' => 'cancelled',
            'lift_id' => $id,
            'removed' => $destinations,
            'queue' => $lifts[$index]['queue']
        ]);
    }

    public function getAllLifts()
    {
        $liftsPath = storage_path('app/lifts.json');

        if (!file_exists($liftsPath)) {
            return response()->json(['error' => 'lifts data not found'], 500);
        }

        $lifts = json_decode(file_get_contents($liftsPath), true);

        return response()->json([
            'lifts' => $lifts
        ]);
    }
}
