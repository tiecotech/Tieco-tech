<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\LaunchController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PricingController;
use App\Http\Controllers\Admin\RequestController;
use App\Http\Middleware\AdminArea;
use App\Http\Middleware\RequirePermission as Can;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', AdminArea::class])->prefix('admin')->name('admin.')->group(function () {
    Route::post('locale', [AccountController::class, 'locale'])->name('locale');
    Route::post('notifications/read', [AccountController::class, 'readNotifications'])->name('notifications.read');

    Route::middleware(Can::class.':requests.view')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('requests', [RequestController::class, 'index'])->name('requests.index');
        Route::get('requests/export', [RequestController::class, 'export'])->middleware(Can::class.':requests.export')->name('requests.export');
        Route::get('requests/{projectRequest}', [RequestController::class, 'show'])->whereNumber('projectRequest')->name('requests.show');
        Route::get('requests/{projectRequest}/attachments/{attachment}', [RequestController::class, 'download'])->name('requests.download');
    });

    Route::middleware(Can::class.':requests.manage')->group(function () {
        // Deals sold outside the website, archived manually.
        Route::get('requests/create', [RequestController::class, 'create'])->name('requests.create');
        Route::post('requests', [RequestController::class, 'store'])->name('requests.store');
        Route::post('requests/{projectRequest}/sale', [RequestController::class, 'sale'])->name('requests.sale');
        Route::post('requests/{projectRequest}/payments', [RequestController::class, 'paymentStore'])->name('requests.payments.store');
        Route::delete('requests/{projectRequest}/payments/{payment}', [RequestController::class, 'paymentDestroy'])->name('requests.payments.destroy');
        Route::post('requests/{projectRequest}/status', [RequestController::class, 'status'])->name('requests.status');
        Route::post('requests/{projectRequest}/assign', [RequestController::class, 'assign'])->name('requests.assign');
        Route::post('requests/{projectRequest}/notes', [RequestController::class, 'note'])->name('requests.note');
    });

    Route::middleware(Can::class.':pricing.manage')->prefix('pricing')->name('pricing.')->group(function () {
        Route::get('/', [PricingController::class, 'index'])->name('index');
        Route::put('types/{type}', [PricingController::class, 'updateType'])->name('types.update');
        Route::put('options/{option}', [PricingController::class, 'updateOption'])->name('options.update');
        Route::post('discounts', [PricingController::class, 'storeDiscount'])->name('discounts.store');
        Route::put('discounts/{discount}', [PricingController::class, 'updateDiscount'])->name('discounts.update');
        Route::post('finalize', [PricingController::class, 'finalize'])->name('finalize');
    });
    Route::middleware(Can::class.':messages.view')->prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::get('{message}', [MessageController::class, 'show'])->name('show');
    });
    Route::middleware(Can::class.':messages.manage')->prefix('messages')->name('messages.')->group(function () {
        Route::post('{message}/status', [MessageController::class, 'status'])->name('status');
        Route::delete('{message}', [MessageController::class, 'destroy'])->name('destroy');
    });

    Route::middleware(Can::class.':content.manage')->prefix('content')->name('content.')->group(function () {
        Route::get('services', [ContentController::class, 'services'])->name('services');
        Route::get('services/new', [ContentController::class, 'serviceForm'])->name('services.new');
        Route::post('services', [ContentController::class, 'serviceSave'])->name('services.store');
        Route::get('services/{service}/edit', [ContentController::class, 'serviceForm'])->name('services.edit');
        Route::put('services/{service}', [ContentController::class, 'serviceSave'])->name('services.update');
        Route::delete('services/{service}', [ContentController::class, 'serviceDelete'])->name('services.destroy');

        Route::get('pages', [ContentController::class, 'pages'])->name('pages');
        Route::put('pages/{page}', [ContentController::class, 'pageSave'])->name('pages.update');

        Route::get('team', [ContentController::class, 'team'])->name('team');
        Route::post('team', [ContentController::class, 'teamSave'])->name('team.store');
        Route::put('team/{member}', [ContentController::class, 'teamSave'])->name('team.update');
        Route::delete('team/{member}', [ContentController::class, 'teamDelete'])->name('team.destroy');

        Route::get('settings', [ContentController::class, 'settings'])->name('settings');
        Route::put('settings', [ContentController::class, 'settingsSave'])->name('settings.update');
        Route::put('settings/alerts', [ContentController::class, 'alertsSave'])->name('settings.alerts');
        Route::post('settings/alerts/test', [ContentController::class, 'alertsTest'])->name('settings.alerts.test');
    });

    Route::middleware(Can::class.':users.manage')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('{user}', [UserController::class, 'update'])->name('update');
    });

    // Admin only (no other role is granted site.launch).
    Route::middleware(Can::class.':site.launch')->prefix('launch')->name('launch.')->group(function () {
        Route::get('/', [LaunchController::class, 'show'])->name('show');
        Route::put('/', [LaunchController::class, 'update'])->name('update');
        Route::post('preview-link', [LaunchController::class, 'rotate'])->name('rotate');
    });

    Route::get('activity', ActivityController::class)->middleware(Can::class.':activity.view')->name('activity');
});
