<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
| Add ONE cron entry on the server (see README):
|   * * * * * cd /path-to-clinic && php artisan schedule:run >> /dev/null 2>&1
| On Windows use Task Scheduler to run "php artisan schedule:run" every minute.
*/

// Daily backup of the database + patient attachments, then remove old backups.
Schedule::command('backup:clean')->dailyAt('01:30')->withoutOverlapping();
Schedule::command('backup:run')->dailyAt('02:00')->withoutOverlapping();
Schedule::command('backup:monitor')->dailyAt('09:00');

// Housekeeping
Schedule::command('activitylog:clean')->weekly();
Schedule::command('auth:clear-resets')->daily();
