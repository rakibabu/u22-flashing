<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:manage-account'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.profile')->name('profile.edit');
});

Route::middleware(['auth', 'verified', 'can:manage-account'])->group(function () {
    Route::redirect('settings/appearance', 'settings/profile')->name('appearance.edit');

    Route::livewire('settings/security', 'pages::settings.security')
        ->middleware([
            'password.confirm',
        ])
        ->name('security.edit');
});
