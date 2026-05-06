<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\TallyMiddlewareController;
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

Route::prefix('tally')->group(function () {
    Route::post('/test-connection', [TallyMiddlewareController::class, 'testConnection']);
    Route::match(['get', 'post'], '/current-company', [TallyMiddlewareController::class, 'currentCompany']);
    Route::match(['get', 'post'], '/ledgers', [TallyMiddlewareController::class, 'ledgers']);
    Route::match(['get', 'post'], '/groups', [TallyMiddlewareController::class, 'groups']);
    Route::match(['get', 'post'], '/stock-items', [TallyMiddlewareController::class, 'stockItems']);
    Route::match(['get', 'post'], '/units', [TallyMiddlewareController::class, 'units']);
    Route::match(['get', 'post'], '/voucher-types', [TallyMiddlewareController::class, 'voucherTypes']);
    Route::match(['get', 'post'], '/master-options', [TallyMiddlewareController::class, 'masterOptions']);
    Route::get('/fields', [TallyMiddlewareController::class, 'fields']);
    Route::post('/ledger/create', [TallyMiddlewareController::class, 'createLedger']);
    Route::post('/item/create', [TallyMiddlewareController::class, 'createItem']);
    Route::post('/sales/create', [TallyMiddlewareController::class, 'createSalesVoucher']);
    Route::post('/expense/create', [TallyMiddlewareController::class, 'createExpenseVoucher']);
    Route::post('/journal/create', [TallyMiddlewareController::class, 'createJournalVoucher']);
    Route::get('/sync-logs', [TallyMiddlewareController::class, 'logs']);
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

