<?php

use App\Http\Controllers\SupportPortalController;
use Illuminate\Support\Facades\Route;

Route::domain('support.believoo.com')->group(function () {
    Route::get('/', [SupportPortalController::class, 'home'])->name('support.home');
    Route::get('/help', [SupportPortalController::class, 'kbIndex'])->name('support.kb.index');
    Route::get('/help/{article}', [SupportPortalController::class, 'kbShow'])->name('support.kb.show');
    Route::get('/status', [SupportPortalController::class, 'status'])->name('support.status');
    Route::get('/tickets', \App\Livewire\SupportTickets::class)->name('support.tickets');
    Route::get('/contact', [SupportPortalController::class, 'contact'])->name('support.contact');
});
