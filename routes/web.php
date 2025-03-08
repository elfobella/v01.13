<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Chat
    Route::get('/chats', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chats/create', [ChatController::class, 'createForm'])->name('chat.create.form');
    Route::get('/chats/{chat}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chats', [ChatController::class, 'create'])->name('chat.create');
    Route::post('/group-chats', [ChatController::class, 'createGroup'])->name('chat.create-group');
    Route::post('/chats/{chat}/messages', [ChatController::class, 'sendMessage'])->name('chat.messages.send');
    Route::post('/chats/{chat}/read', [ChatController::class, 'markAsRead'])->name('chat.messages.read');
    Route::post('/chat-active-status', [ChatController::class, 'setActiveStatus'])->name('chat.active.status');
    
    // Bildirimler
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('/notifications/chat/{chatId}/read', [NotificationController::class, 'markChatNotificationsAsRead'])->name('notifications.chat.read');
});

require __DIR__.'/auth.php';
