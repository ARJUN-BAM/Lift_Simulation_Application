<?php

use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    var_dump('HEllo');
})->daily();
