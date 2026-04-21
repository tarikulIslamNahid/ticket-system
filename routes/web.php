<?php

declare(strict_types=1);

use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\TicketViewController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('contact.create');
});

Route::get('/contact', [ContactController::class, 'create'])->name('contact.create');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::get('/ticket/{token}', [TicketViewController::class, 'show'])
    ->where('token', '[A-Za-z0-9]+')
    ->name('ticket.view');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth'])->name('dashboard');

require __DIR__.'/auth.php';
