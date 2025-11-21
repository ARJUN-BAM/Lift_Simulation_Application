<?php

namespace App\Services;

class LiftRequestDispatcher
{
    public static function push($floor, $direction)
    {
        $lockPath = storage_path('app/lifts.lock');
        $reqPath  = storage_path('app/requestList.json');
        $liftsPath = storage_path('app/lifts.json');

        $lock = fopen($lockPath, 'c+');
        flock($lock, LOCK_EX);

        $requests = json_decode(file_get_contents($reqPath), true);
        $lifts = json_decode(file_get_contents($liftsPath), true);

        foreach ($requests as $r) {
            if ((int)$r['current_floor'] === $floor && $r['direction'] === $direction) {
                flock($lock, LOCK_UN);
                fclose($lock);
                return 'ignored';
            }
        }

        foreach ($lifts as $lift) {
            if (in_array($floor, $lift['queue'])) {
                flock($lock, LOCK_UN);
                fclose($lock);
                return 'ignored';
            }
        }

        $requests[] = [
            'current_floor' => $floor,
            'direction' => $direction,
            'ts' => time()
        ];

        file_put_contents($reqPath, json_encode($requests, JSON_PRETTY_PRINT));

        flock($lock, LOCK_UN);
        fclose($lock);

        return 'queued';
    }
}
