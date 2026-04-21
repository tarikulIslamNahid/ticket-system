<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\TicketController as AdminTicketController;
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

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/{ticket}', [AdminTicketController::class, 'show'])->name('tickets.show');
        Route::post('/tickets/{ticket}/reply', [AdminTicketController::class, 'reply'])->name('tickets.reply');
        Route::post('/tickets/{ticket}/typing', [AdminTicketController::class, 'typing'])->name('tickets.typing');
    });
});

require __DIR__.'/auth.php';
