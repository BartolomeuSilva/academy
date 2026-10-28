<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('horizon:snapshot')->everyFiveMinutes();
Schedule::command('queue:prune-failed', ['--hours' => 24 * 7])->daily();
