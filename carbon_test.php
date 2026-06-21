<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Carbon\Carbon;

$now = Carbon::parse('2026-06-21 22:56:12');
$dueDate1 = Carbon::parse('2026-06-20 00:00:00');
$dueDate2 = Carbon::parse('2026-06-13 00:00:00');

echo "Comparing starts of days with absolute = true:\n";
echo "Due Date 1 (2026-06-20) vs Now: " . $dueDate1->startOfDay()->diffInDays($now->startOfDay(), true) . " days\n";
echo "Due Date 2 (2026-06-13) vs Now: " . $dueDate2->startOfDay()->diffInDays($now->startOfDay(), true) . " days\n";

$daysOverdue1 = $dueDate1->startOfDay()->diffInDays($now->startOfDay(), true);
$latePaymentFee = 3000;
$lateFee1 = $latePaymentFee * $daysOverdue1;
echo "\nExample Late Fee 1: Rp " . number_format($lateFee1, 0, ',', '.') . "\n";
