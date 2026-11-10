<?php

use App\Http\Controllers\BuilderController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RootRedirectController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\ServicesController;
use App\Http\Middleware\ComingSoon;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

// "/" keeps the name `home` that the starter kit's pages link to; it redirects to /en or /ar.
// Until launch, ComingSoon answers every public page instead (staff and preview-link holders pass).
Route::get('/', RootRedirectController::class)->middleware(ComingSoon::class)->name('home');
Route::get('/robots.txt', [SeoController::class, 'robots']);
Route::get('/sitemap.xml', [SeoController::class, 'sitemap']);
Route::get('/preview/exit', [PreviewController::class, 'exit'])->name('preview.exit');
Route::get('/preview/{token}', [PreviewController::class, 'enter'])->middleware('throttle:10,1')->name('preview.enter');

Route::prefix('{locale}')->where(['locale' => 'en|ar'])->middleware([ComingSoon::class, SetLocale::class])->group(function () {
    Route::get('/', HomeController::class)->name('site.home');

    Route::get('/services', [ServicesController::class, 'index'])->name('site.services');
    Route::get('/services/{slug}', [ServicesController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('site.services.show');
    Route::get('/projects', [ProjectController::class, 'index'])->name('site.projects');
    Route::get('/projects/{slug}', [ProjectController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('site.projects.show');
    Route::get('/about', [PageController::class, 'about'])->name('site.about');
    Route::get('/legal/{slug}', [PageController::class, 'legal'])->name('site.legal');
    Route::get('/contact', [ContactController::class, 'show'])->name('site.contact');
    Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1')->name('site.contact.store');

    // Project Builder
    Route::get('/start', [BuilderController::class, 'show'])->name('site.start');
    Route::post('/start/estimate', [BuilderController::class, 'estimate'])->middleware('throttle:90,1')->name('site.start.estimate');
    Route::post('/start', [BuilderController::class, 'submit'])->middleware('throttle:8,1')->name('site.start.submit');
    Route::get('/start/received', [BuilderController::class, 'received'])->name('site.start.received');
});
