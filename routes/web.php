<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\TicketViewController;
use App\Http\Controllers\Webhooks\SendGridInboundController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('contact.create');
});

Route::get('/contact', [ContactController::class, 'create'])->name('contact.create');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::prefix('ticket/{token}')
    ->where(['token' => '[A-Za-z0-9]+'])
    ->group(function (): void {
        Route::get('/', [TicketViewController::class, 'show'])->name('ticket.view');
        Route::post('/reply', [TicketViewController::class, 'reply'])->name('ticket.reply');
        Route::post('/typing', [TicketViewController::class, 'typing'])->name('ticket.typing');
    });

Route::post('webhooks/sendgrid/inbound/{secret}', [SendGridInboundController::class, 'handle'])
    ->middleware('throttle:60,1')
    ->where('secret', '[A-Za-z0-9\-_]+')
    ->name('webhooks.sendgrid.inbound');

Route::prefix('chat')->name('chat.')->group(function (): void {
    Route::post('/start', [ChatController::class, 'start'])->name('start');

    Route::prefix('{token}')
        ->where(['token' => '[A-Za-z0-9]+'])
        ->group(function (): void {
            Route::get('/', [ChatController::class, 'show'])->name('show');
            Route::post('/reply', [ChatController::class, 'reply'])->name('reply');
            Route::post('/typing', [ChatController::class, 'typing'])->name('typing');
        });
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/{ticket}', [AdminTicketController::class, 'show'])->name('tickets.show');
        Route::post('/tickets/{ticket}/reply', [AdminTicketController::class, 'reply'])->name('tickets.reply');
        Route::post('/tickets/{ticket}/typing', [AdminTicketController::class, 'typing'])->name('tickets.typing');
        Route::patch('/tickets/{ticket}/status', [AdminTicketController::class, 'updateStatus'])->name('tickets.status');
    });
});

require __DIR__.'/auth.php';
