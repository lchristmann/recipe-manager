<?php

use App\Console\Commands\PruneOrphanedRecipeImageFilesCommand;
use App\Console\Commands\PruneOrphanedTagsCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(PruneOrphanedTagsCommand::class)
    ->weeklyOn(1, '4:00'); // Monday 04:00

Schedule::command(PruneOrphanedRecipeImageFilesCommand::class)
    ->weeklyOn(1, '4:05'); // Monday 04:05
