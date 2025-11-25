<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateLifts extends Command
{
    protected $signature = 'lifts:engine';
    protected $description = 'Simulates 4 lifts movement & request assignment with locking';

    private $liftsPath;
    private $requestListPath;
    private $lockPath;

    public function __construct()
    {
        parent::__construct();
        $this->liftsPath       = storage_path('app/lifts.json');
        $this->requestListPath = storage_path('app/requestList.json');
        $this->lockPath        = storage_path('app/lifts.lock');
    }

    public function handle()
    {
        $this->ensureFiles();
        $this->info("Lift engine running...");

        while (true) {

            /** LOCK for safe read/update */
            $lockFp = fopen($this->lockPath, 'c+');
            flock($lockFp, LOCK_EX);
            // $this->info("Engine tick: " . now());

            $lifts    = json_decode(file_get_contents($this->liftsPath), true);
            $requests = json_decode(file_get_contents($this->requestListPath), true);

            /** Normalize queue format */
            foreach ($lifts as &$l) {
                foreach ($l['queue'] as &$q) {
                    if (is_int($q)) {
                        $q = ['reqFloor' => $q, 'reqDirection' => null];
                    }
                }
            }
            unset($l, $q);

            /** Assign requests */
            $remaining = [];
            foreach ($requests as $req) {
                if (!$this->assignRequest($req, $lifts)) {
                    $remaining[] = $req;
                }
            }
            $requests = $remaining;

            /** MOVE each lift */
            foreach ($lifts as &$lift) {

                if (empty($lift['queue'])) {
                    $lift['direction'] = "idle";
                    continue;
                }

                /** Maintain queue order */
                $lift['queue'] = $this->reorderQueue($lift['queue'], $lift['position'], $lift['direction']);
                $target = $lift['queue'][0]['reqFloor'];

                /** Lift direction must always come from request direction */
                if (!empty($lift['queue'][0]['reqDirection'])) {
                    $lift['direction'] = $lift['queue'][0]['reqDirection'];
                }

                /** Move ONE FLOOR toward target */
                if ($lift['position'] < $target) {
                    $lift['position']++;
                } elseif ($lift['position'] > $target) {
                    $lift['position']--;
                }
                var_dump($lift);
                file_put_contents($this->liftsPath, json_encode($lifts, JSON_PRETTY_PRINT));

                // $this->info("Saved lifts.json at " . now());


                /** Arrived at destination */
                if ($lift['position'] == $target) {
                    array_shift($lift['queue']);
                    usleep((config('constants.LIFT_OPENING_TIME') + config('constants.LIFT_CLOSING_TIME')) * 1000000);
                }

                if (empty($lift['queue'])) {
                    $lift['direction'] = "idle";
                }
            }
            unset($lift);

            file_put_contents($this->liftsPath, json_encode($lifts, JSON_PRETTY_PRINT));
            flock($lockFp, LOCK_UN); // release finally
            fclose($lockFp);


            /** Wait before next movement step */
            sleep(config('constants.LIFT_TRAVELLING_TIME'));
        }
    }



    private function ensureFiles()
    {
        if (!file_exists($this->liftsPath)) {
            file_put_contents($this->liftsPath, json_encode([
                ["id" => 1, "position" => -4, "direction" => "idle", "queue" => []],
                ["id" => 2, "position" => -4, "direction" => "idle", "queue" => []],
                ["id" => 3, "position" => -4, "direction" => "idle", "queue" => []],
                ["id" => 4, "position" => -4, "direction" => "idle", "queue" => []]
            ], JSON_PRETTY_PRINT));
        }

        if (!file_exists($this->requestListPath)) {
            file_put_contents($this->requestListPath, json_encode([], JSON_PRETTY_PRINT));
        }

        if (!file_exists($this->lockPath)) {
            touch($this->lockPath);
        }
    }

    private function assignRequest($req, &$lifts)
    {
        $floor = (int)$req['current_floor'];
        $dir   = $req['direction'];

        $best = null;
        $bestDist = PHP_INT_MAX;

        foreach ($lifts as $i => $lift) {

            if ($this->hasFloor($lift['queue'], $floor)) {
                return true;
            }

            if ($lift['direction'] === 'idle') {
                $dist = abs($lift['position'] - $floor);
            } elseif (
                $lift['direction'] === $dir &&
                (($dir === 'up'   && $lift['position'] <= $floor) ||
                    ($dir === 'down' && $lift['position'] >= $floor))
            ) {
                $dist = abs($lift['position'] - $floor);
            } else {
                $dist = abs($lift['position'] - $floor) + 100;
            }

            if ($dist < $bestDist) {
                $bestDist = $dist;
                $best = $i;
            }
        }

        if ($best === null) return false;

        $lifts[$best]['queue'][] = [
            'reqFloor' => $floor,
            'reqDirection' => $dir
        ];

        if ($lifts[$best]['direction'] === "idle") {
            $lifts[$best]['direction'] = $lifts[$best]['position'] < $floor ? "up" : "down";
        }

        return true;
    }

    private function reorderQueue($queue, $pos, $direction)
    {
        if (empty($queue)) return [];

        if ($direction === "idle") {
            $direction = $queue[0]['reqFloor'] >= $pos ? "up" : "down";
        }

        $same = [];
        $opp  = [];

        foreach ($queue as $item) {
            $floor = $item['reqFloor'];
            if ($direction === "up") {
                ($floor >= $pos) ? $same[] = $item : $opp[] = $item;
            } else {
                ($floor <= $pos) ? $same[] = $item : $opp[] = $item;
            }
        }

        if ($direction === "up")  usort($same, fn($a, $b) => $a['reqFloor'] <=> $b['reqFloor']);
        if ($direction === "down") usort($same, fn($a, $b) => $b['reqFloor'] <=> $a['reqFloor']);
        usort($opp, fn($a, $b) => $a['reqFloor'] <=> $b['reqFloor']);

        return array_merge($same, $opp);
    }

    private function hasFloor($queue, $floor)
    {
        foreach ($queue as $item) {
            if ($item['reqFloor'] == $floor) return true;
        }
        return false;
    }
}
