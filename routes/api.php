<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth')->get('/user', function (Request $request) {
    return $request->user();
});

// Bildirim oluşturmak için API route'u
Route::post('/notifications', [NotificationController::class, 'store']);

// Okunmamış bildirim sayısını döndür - web middleware grubunu kullan
Route::middleware(['web', 'auth'])->get('/notifications/unread-count', function (Request $request) {
    return response()->json([
        'count' => $request->user()->notifications()->where('is_read', false)->count()
    ]);
}); 