<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// AccuracyReport is a full command class; it is auto-discovered by Laravel via
// app/Console/Commands/ — no manual registration needed here.
// The entry below exists purely as documentation / an explicit reminder.
//
// Usage:
//   php artisan accuracy:report
//   php artisan accuracy:report --source=ai-claude
//   php artisan accuracy:report --verbose
