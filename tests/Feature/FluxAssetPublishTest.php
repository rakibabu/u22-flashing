<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

test('flux assets can be published for static production hosting', function () {
    File::deleteDirectory(public_path('flux'));

    Artisan::call('app:publish-flux-assets');

    expect(public_path('flux/flux.js'))->toBeFile()
        ->and(public_path('flux/flux.min.js'))->toBeFile()
        ->and(File::get(public_path('flux/flux.min.js')))->toContain('fluxInputViewable');
});
