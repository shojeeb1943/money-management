<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('finance:process-recurring')
    ->dailyAt('00:30')
    ->timezone('Asia/Dhaka');
