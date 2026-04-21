<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Engineer\EngineerLiveController;
use App\Http\Controllers\Api\Engineer\EngineerTicketController;
use App\Http\Controllers\Api\Engineer\EngineerNotificationController;
use App\Http\Controllers\Api\Engineer\TicketVisitController;
use App\Http\Controllers\Api\Engineer\ItemController;

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

//Customer app route

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/auth/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/auth/save-customer-token', [AuthController::class, 'saveCustomerToken']);

Route::prefix('tickets')->group(function () {
    // Masters for dropdowns
    Route::get('/masters/{type}', [TicketController::class, 'masters']);
    Route::post('/my-tickets', [TicketController::class, 'myCustomerTickets']);
    // Tickets
    Route::get('/', [TicketController::class, 'index']);
    Route::post('/', [TicketController::class, 'store']);
    Route::get('/{id}', [TicketController::class, 'show']);
    Route::put('/{id}', [TicketController::class, 'update']);
});

Route::prefix('products')->group(function () {
    Route::get('/my-products/{party_id}', [ProductController::class, 'myProducts']);
});


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Engineer app route
|
*/

Route::prefix('engineer')->group(function () {
    Route::post('/presence/start', [EngineerLiveController::class, 'start']);
    Route::post('/live-location/update', [EngineerLiveController::class, 'updateLocation']);
    Route::post('/presence/stop', [EngineerLiveController::class, 'stop']);
    Route::post('/my-tickets', [EngineerTicketController::class, 'getMyTickets']);
    Route::post('/save-engineer-token', [EngineerNotificationController::class, 'saveFcmToken']);
    Route::post('ticket-visit', [TicketVisitController::class, 'store']);
    Route::get('ticket-visits/{ticketId}', [TicketVisitController::class, 'index']);
    Route::post('generate-otp', [TicketVisitController::class, 'generateOtp']);
    Route::post('verify-otp', [TicketVisitController::class, 'verifyOtp']);
    Route::post('/set-current-ticket', [EngineerTicketController::class, 'setCurrentTicket']);
    Route::get('/items/search', [ItemController::class, 'search']);
    Route::get('/tickets/today-closed', [EngineerTicketController::class, 'todayClosedTickets']);
    Route::post(
    '/tickets/firebase-close',
    [TicketVisitController::class, 'closeTicketAfterFirebase']
);
});




