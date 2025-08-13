<?php

declare(strict_types=1);

use App\Http\Controllers\PlaygroundController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/playground')->middleware('auth');

Route::middleware(['auth', 'verified'])->group(function (): void {
    // Playground routes with names for Wayfinder
    Route::get('/playground', [PlaygroundController::class, 'index'])
        ->name('playground.index');

    Route::get('/playground/tools/{tool:slug}', [PlaygroundController::class, 'show'])
        ->name('playground.show');

    Route::put('/playground/tools/{tool}', [PlaygroundController::class, 'update'])
        ->name('playground.update');

    Route::post('/playground/tools/{tool}/execute', [PlaygroundController::class, 'execute'])
        ->name('playground.execute');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
