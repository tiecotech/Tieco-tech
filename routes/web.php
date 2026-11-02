<?php

use Illuminate\Support\Facades\Route;

// The starter kit's dashboard is replaced by the Tieco admin area.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', '/admin')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/tieco.php';
require __DIR__.'/admin.php';
