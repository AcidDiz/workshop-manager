<?php

use App\Http\Controllers\Admin\Workshops\WorkshopCreateController;
use App\Http\Controllers\Admin\Workshops\WorkshopDestroyController;
use App\Http\Controllers\Admin\Workshops\WorkshopEditController;
use App\Http\Controllers\Admin\Workshops\WorkshopIndexController as AdminWorkshopIndexController;
use App\Http\Controllers\Admin\Workshops\WorkshopStoreController;
use App\Http\Controllers\Admin\Workshops\WorkshopUpdateController;
use App\Http\Controllers\App\Workshops\WorkshopIndexController as AppWorkshopIndexController;
use App\Http\Controllers\App\Workshops\WorkshopRegistrationAttachController;
use App\Http\Controllers\App\Workshops\WorkshopRegistrationDetachController;
use App\Models\Workshop;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::middleware(['can:viewAny,'.Workshop::class])
        ->prefix('app')
        ->as('app.')
        ->group(function () {
            Route::get('workshops', AppWorkshopIndexController::class)->name('workshops.index');

            Route::post('workshops/{workshop}/registrations', WorkshopRegistrationAttachController::class)
                ->middleware(['can:attachRegistration,workshop'])
                ->name('workshops.registrations.attach');

            Route::delete('workshops/{workshop}/registrations', WorkshopRegistrationDetachController::class)
                ->middleware(['can:detachRegistration,workshop'])
                ->name('workshops.registrations.detach');
        });

    Route::prefix('admin')
        ->as('admin.')
        ->group(function () {
            Route::middleware(['can:create,'.Workshop::class])->group(function () {
                Route::get('workshops', AdminWorkshopIndexController::class)->name('workshops.index');
                Route::get('workshops/create', WorkshopCreateController::class)->name('workshops.create');
                Route::post('workshops', WorkshopStoreController::class)->name('workshops.store');
            });

            Route::get('workshops/{workshop}/edit', WorkshopEditController::class)
                ->middleware(['can:update,workshop'])
                ->name('workshops.edit');

            Route::put('workshops/{workshop}', WorkshopUpdateController::class)
                ->middleware(['can:update,workshop'])
                ->name('workshops.update');

            Route::delete('workshops/{workshop}', WorkshopDestroyController::class)
                ->middleware(['can:delete,workshop'])
                ->name('workshops.destroy');
        });
});

require __DIR__.'/settings.php';
