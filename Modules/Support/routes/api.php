<?php

use Illuminate\Support\Facades\Route;
use Modules\Support\Http\Controllers\ChatController;
use Modules\Support\Http\Controllers\TicketController;
use Modules\Support\Http\Controllers\FaqController;
use Modules\Support\Http\Controllers\CustomerDisputeController;


// ─── Public FAQ Routes ────────────────────────────────────────

Route::prefix('support')->middleware('throttle:api')->group(function () {
    Route::get('faqs',              [FaqController::class, 'publicFaqs']);
    Route::get('faq-categories',    [FaqController::class, 'publicCategories']);
});

// ─── Customer Routes (Authenticated) ──────────────────────────

Route::middleware(['auth:sanctum'])->prefix('support')->group(function () {

    // Tickets
    Route::get('tickets',                   [TicketController::class, 'index']);
    Route::post('tickets',                  [TicketController::class, 'store']);
    Route::get('tickets/{ticket}',           [TicketController::class, 'show']);
    Route::post('tickets/{ticket}/messages', [TicketController::class, 'addMessage']);

    // Disputes (customer-facing)
    Route::get('disputes',                   [CustomerDisputeController::class, 'index']);
    Route::post('disputes',                  [CustomerDisputeController::class, 'store']);
    Route::get('disputes/{dispute}',         [CustomerDisputeController::class, 'show']);

    // Chat
    Route::get('chat/room',                  [ChatController::class, 'myRoom']);
    Route::post('chat/room',                 [ChatController::class, 'createRoom'])->middleware('throttle:10,1');
    Route::get('chat/room/{room}/messages',   [ChatController::class, 'messages']);
    Route::post('chat/room/{room}/messages',  [ChatController::class, 'sendMessage'])->middleware('throttle:30,1');
});

// ─── Admin Routes ─────────────────────────────────────────────

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/support')->group(function () {

    // Ticket management
    Route::get('tickets',                        [TicketController::class, 'index']);
    Route::get('tickets/{ticket}',               [TicketController::class, 'show']);
    Route::post('tickets/{ticket}/messages',     [TicketController::class, 'addMessage']);
    Route::patch('tickets/{ticket}/status',      [TicketController::class, 'updateStatus']);

    // FAQ management
    Route::get('faqs',                           [FaqController::class, 'index']);
    Route::post('faqs',                          [FaqController::class, 'store']);
    Route::get('faqs/{faq}',                     [FaqController::class, 'show']);
    Route::put('faqs/{faq}',                     [FaqController::class, 'update']);
    Route::delete('faqs/{faq}',                  [FaqController::class, 'destroy']);

    // FAQ Categories management
    Route::get('faq-categories',                 [FaqController::class, 'categories']);
    Route::post('faq-categories',                [FaqController::class, 'storeCategory']);
    Route::put('faq-categories/{faqCategory}',   [FaqController::class, 'updateCategory']);
    Route::delete('faq-categories/{faqCategory}', [FaqController::class, 'destroyCategory']);

    // Chat management
    Route::get('chat/rooms',                     [ChatController::class, 'adminRooms']);
    Route::get('chat/rooms/{room}/messages',     [ChatController::class, 'messages']);
    Route::post('chat/rooms/{room}/messages',    [ChatController::class, 'sendMessage']);
    Route::post('chat/rooms/{room}/close',       [ChatController::class, 'closeRoom']);
});
