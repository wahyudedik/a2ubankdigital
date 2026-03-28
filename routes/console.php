<?php

use Illuminate\Support\Facades\Schedule;

// Process scheduled transfers every day at 00:05
Schedule::command('transfers:process-scheduled')->dailyAt('00:05');

// Process standing instructions every day at 00:10
Schedule::command('transfers:process-standing')->dailyAt('00:10');

// Check overdue installments every day at 01:00
Schedule::command('loans:check-overdue')->dailyAt('01:00');
