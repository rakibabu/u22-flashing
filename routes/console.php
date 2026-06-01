<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:prune-failed --hours=168')->dailyAt('03:00');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:publish-flux-assets', function (): int {
    $source = base_path('vendor/livewire/flux/dist/flux-lite.min.js');
    $targetDirectory = public_path('flux');

    if (! File::exists($source)) {
        $this->error('Flux runtime not found. Run composer install first.');

        return 1;
    }

    File::ensureDirectoryExists($targetDirectory);
    File::copy($source, $targetDirectory.'/flux.js');
    File::copy($source, $targetDirectory.'/flux.min.js');

    $this->info('Flux assets published to public/flux.');

    return 0;
})->purpose('Publish Flux UI JavaScript assets for static production hosting');
