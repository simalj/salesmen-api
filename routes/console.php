<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\DatabaseHealthCheck;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Register custom commands
Artisan::command('db:health-check {--detailed} {--slow-queries} {--connections}', DatabaseHealthCheck::class)
    ->purpose('Check database health and performance metrics');
