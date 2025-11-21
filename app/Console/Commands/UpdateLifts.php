<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateLifts extends Command
{
    protected $signature = 'lifts:engine';
    protected $description = 'Simulates 4 lifts moving every 3s with FIFO requests, dedupe, door timing and safe file locking';

    private $liftsPath;
    private $requestListPath;
    private $lockPath;

    public function __construct()
    {
        parent::__construct();

        $this->liftsPath = storage_path('app/lifts.json');
        $this->requestListPath = storage_path('app/requestList.json');
        $this->lockPath = storage_path('app/lifts.lock');
    }

    public function handle()
    {
        $this->ensureFiles();

        $this->info("Lift engine running. CTRL+C to stop.");

        while (true) {

            $lockFp = fopen($this->lockPath, 'c+');
            flock($lockFp, LOCK_EX);

            $lifts = json_decode(file_get_contents($this->liftsPath), true);
            $requests = json_decode(file_get_contents($this->requestListPath), true);

            // Assign FIFO requests
            $remaining = [];
            foreach ($requests as $req) {
                $assigned = $this->assignRequest($req, $lifts);
                if (!$assigned) $remaining[] = $req;
            }
            $requests = $remaining;

            // Move each lift
            foreach ($lifts as &$lift) {

                if (empty($lift['queue'])) {
                    $lift['direction'] = 'idle';
                    continue;
                }

                $lift['queue'] = $this->reorderQueue(
                    $lift['queue'],
                    $lift['position'],
                    $lift['direction']
                );

                $target = $lift['queue'][0];

                if ($lift['direction'] === 'idle') {
                    $lift['direction'] = $lift['position'] < $target ? 'up' : 'down';
                }

                if ($lift['position'] < $target) {
                    $lift['position'] += 1;
                    $lift['direction'] = 'up';
                } elseif ($lift['position'] > $target) {
                    $lift['position'] -= 1;
                    $lift['direction'] = 'down';
                }

                if ($lift['position'] == $target) {
                    array_shift($lift['queue']);
                    usleep(3000000); // 1.5s door simulation
                }

                if (empty($lift['queue'])) {
                    $lift['direction'] = 'idle';
                }
            }
            unset($lift);

            file_put_contents($this->liftsPath, json_encode($lifts, JSON_PRETTY_PRINT));
            file_put_contents($this->requestListPath, json_encode($requests, JSON_PRETTY_PRINT));

            flock($lockFp, LOCK_UN);
            fclose($lockFp);

            sleep(5);
        }
    }

    private function ensureFiles()
    {
        if (!file_exists($this->liftsPath)) {
            file_put_contents($this->liftsPath, json_encode([
                ["id" => 1, "position" => -3, "direction" => "idle", "queue" => []],
                ["id" => 2, "position" => -3, "direction" => "idle", "queue" => []],
                ["id" => 3, "position" => -3, "direction" => "idle", "queue" => []],
                ["id" => 4, "position" => -3, "direction" => "idle", "queue" => []]
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
                if ($dist < $bestDist) {
                    $bestDist = $dist;
                    $best = $i;
                }
                continue;
            }

            if ($lift['direction'] === $dir) {
                if (
                    $dir === 'up'   && $lift['position'] <= $floor ||
                    $dir === 'down' && $lift['position'] >= $floor
                ) {

                    $dist = abs($lift['position'] - $floor);
                    if ($dist < $bestDist) {
                        $bestDist = $dist;
                        $best = $i;
                    }
                }
            }
        }

        if ($best === null) return false;

        if (!$this->hasFloor($lifts[$best]['queue'], $floor)) {
            $lifts[$best]['queue'][] = $floor;

            if ($lifts[$best]['direction'] === 'idle') {
                $lifts[$best]['direction'] = $lifts[$best]['position'] < $floor ? 'up' : 'down';
            }
        }

        return true;
    }

    private function reorderQueue($queue, $pos, $direction)
    {
        if (empty($queue)) return [];

        if ($direction === 'idle') {
            $direction = $queue[0] >= $pos ? 'up' : 'down';
        }

        $same = [];
        $opp = [];

        foreach ($queue as $floor) {
            if ($direction === 'up') {
                ($floor >= $pos) ? $same[] = $floor : $opp[] = $floor;
            } else {
                ($floor <= $pos) ? $same[] = $floor : $opp[] = $floor;
            }
        }

        if ($direction === 'up') sort($same);
        if ($direction === 'down') rsort($same);

        sort($opp);

        return array_merge($same, $opp);
    }

    private function hasFloor($queue, $floor)
    {
        return in_array($floor, $queue);
    }
}
