<?php

use Illuminate\Support\Facades\Route;

// The starter kit's dashboard is replaced by the Tieco admin area.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/admin')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/tieco.php';
require __DIR__.'/admin.php';

// Post-deploy hook for FTP deploys (see DEPLOY.md). 404 unless DEPLOY_TOKEN is configured and matches.
Route::post('_deploy/finish', [\App\Http\Controllers\DeployController::class, 'finish'])
    ->middleware('throttle:5,1')->name('deploy.finish');
