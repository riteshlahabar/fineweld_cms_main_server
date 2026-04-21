<?php

use Illuminate\Support\Facades\Route;
use Modules\Partnership\Http\Controllers\ContractController;
use Modules\Partnership\Http\Controllers\PartnerController;
use Modules\Partnership\Http\Controllers\PartnerSettlementController;
use Modules\Partnership\Http\Controllers\PartyOpeningBalanceAllocationController;
use Modules\Partnership\Http\Controllers\PartyPaymentAllocationController;
use Modules\Partnership\Http\Controllers\Reports\ContractReportController;
use Modules\Partnership\Http\Controllers\Reports\PartnerProfitReportController;
use Modules\Partnership\Http\Controllers\Reports\PartnerReportController;

Route::middleware(['auth'])->group(function () {

    // Partner Management Routes
    Route::prefix('partner')->name('partnership.')->group(function () {
        Route::get('/create', [PartnerController::class, 'create'])
            ->middleware('can:partner.create')
            ->name('partner.create'); // View
        Route::get('/edit/{id}', [PartnerController::class, 'edit'])
            ->middleware('can:partner.edit')
            ->name('partner.edit'); // Edit
        Route::put('/update', [PartnerController::class, 'store'])->name('partner.update'); // Update
        Route::get('/list', [PartnerController::class, 'list'])
            ->middleware('can:partner.view')
            ->name('partner.list'); // List
        Route::get('/datatable-list', [PartnerController::class, 'datatableList'])->name('partner.datatable.list'); // Datatable List
        Route::post('/store', [PartnerController::class, 'store'])->name('partner.store'); // Save operation
        Route::post('/delete/', [PartnerController::class, 'delete'])->middleware('can:partner.delete')->name('partner.delete'); // delete operation
        /**
         * Ajax selection box search
         * */
        Route::get('/ajax/get-list', [PartnerController::class, 'getAjaxSearchBarList']);

        // Partner Contract Routes
        Route::prefix('contract')->group(function () {
            Route::get('/create', [ContractController::class, 'create'])
                ->middleware('can:partner.contract.create')
                ->name('contract.create'); // Create
            Route::get('/edit/{id}', [ContractController::class, 'edit'])
                ->middleware('can:partner.contract.edit')
                ->name('contract.edit'); // Edit
            Route::put('/update', [ContractController::class, 'store'])->name('contract.update'); // Update
            Route::get('/list', [ContractController::class, 'list'])
                ->middleware('can:partner.contract.view')
                ->name('contract.list'); // List
            Route::get('/details/{id}', [ContractController::class, 'details'])
                ->middleware('can:partner.contract.view')
                ->name('contract.details');
            Route::get('/datatable-list', [ContractController::class, 'datatableList'])->name('contract.datatable.list'); // Datatable List
            Route::post('/store', [ContractController::class, 'store'])->name('contract.store'); // Save operation
            Route::post('/delete/', [ContractController::class, 'delete'])->middleware('can:partner.contract.delete')->name('contract.delete'); // delete operation

            // Show Share Holders Modal
            Route::get('/ajax/modal/share-holders/{itemId}', [ContractController::class, 'showShareHoldersModal']);

        });

        // Get Item with Partners
        Route::get('/ajax/item/get-items', [ContractController::class, 'getAjaxItemSearchWithPartners']);

        Route::prefix('party-payment')->group(function () {
            // Party Payments & Allocation Routes
            Route::get('/allocation', function () {
                return view('partnership::allocation.party-payment.list');
            })
                ->middleware('can:partner.payment-allocation.view')
                ->name('partner.party-payment.list'); // View
            Route::get('/allocation/datatable-list', [PartyPaymentAllocationController::class, 'datatableList'])->name('partner.party-payment.datatable.list'); // Datatable List

            // Show Share Holders Modal
            Route::get('/allocation/ajax/modal/show-modal/{paymentId}', [PartyPaymentAllocationController::class, 'showPartnerPartyPaymentAllocationModal']);
            // Partner Party Payment Allocation Store Route (From Modal)
            Route::post('/allocation/store', [PartyPaymentAllocationController::class, 'partnerPartyPaymentAllocationStore'])->name('partner.party-payment.allocation.store'); // Save operation
            // Delete Partner Party Transaction Route
            Route::get('/allocation/delete/{transactionId}', [PartyPaymentAllocationController::class, 'deletePartnerPartyTransaction'])
                ->middleware('can:partner.payment-allocation.delete'); // Delete operation
        });
        Route::prefix('party-balance')->group(function () {
            // Party Payments & Allocation Routes
            Route::get('/allocation', function () {
                return view('partnership::allocation.party-payment.opening-balance.list');
            })
                ->middleware('can:partner.payment-allocation.view')
                ->name('partner.party-payment.opening-balance.list'); // View
            Route::get('/allocation/datatable-list', [PartyOpeningBalanceAllocationController::class, 'datatableList'])->name('partner.party-payment.opening-balance.datatable.list'); // Datatable List

            // Show Share Holders Modal
            Route::get('/allocation/ajax/modal/show-modal/{paymentId}', [PartyOpeningBalanceAllocationController::class, 'showPartnerPartyBalanceAllocationModal']);
            // Partner Party Payment Allocation Store Route (From Modal)
            Route::post('/allocation/store', [PartyOpeningBalanceAllocationController::class, 'partnerPartyBalanceAllocationStore'])->name('partner.party-payment.allocation.opening-balance.store'); // Save operation
            // Delete Partner Party Transaction Route
            Route::get('/allocation/delete/{transactionId}', [PartyOpeningBalanceAllocationController::class, 'deletePartnerPartyTransaction'])
                ->middleware('can:partner.payment-allocation.delete'); // Delete operation
        });
        // Reports
        Route::prefix('report')->group(function () {
            // Party Payments & Allocation Routes
            Route::get('/contract', function () {
                return view('partnership::reports.contract');
            })
                ->middleware('can:partner.report')
                ->name('report.contract'); // View
            Route::post('/contract/get-contract', [ContractReportController::class, 'getContracts'])->name('report.contract.ajax');

            Route::get('/contract/items', function () {
                return view('partnership::reports.contract-item');
            })
                ->middleware('can:partner.report')
                ->name('report.contract.item'); // View
            Route::post('/contract/get-contract-items', [ContractReportController::class, 'getContractItems'])->name('report.contract-item.ajax');

            Route::get('/partner/items', function () {
                return view('partnership::reports.partner-items');
            })
                ->middleware('can:partner.report')
                ->name('report.partner.items'); // View
            Route::post('/partner/get-partner-items', [PartnerReportController::class, 'getPartnerItems'])->name('report.partner-items.ajax');

            Route::get('/partner-profit', function () {
                return view('partnership::reports.partner-profit');
            })
                ->middleware('can:partner.report')
                ->name('report.profit'); // View
            Route::post('/partner-profit/get-records', [PartnerProfitReportController::class, 'getPartnerProfit'])->name('report.profit.ajax');

            Route::get('/partner-profit-item-wise', function () {
                return view('partnership::reports.partner-profit-item-wise');
            })
                ->middleware('can:partner.report')
                ->name('report.profit.item.wise'); // View
            Route::post('/partner-profit-item-wise/get-records', [PartnerProfitReportController::class, 'getPartnerProfitItemWise'])->name('report.profit.item.wise.ajax');

            Route::get('/partner-settlement', function () {
                return view('partnership::reports.partner-settlement');
            })
                ->middleware('can:partner.report')
                ->name('report.settlement'); // View
            Route::post('/partner-settlement/get-records', [PartnerReportController::class, 'getPartnerSettlement'])->name('report.settlement.ajax');

        });

        Route::prefix('settlement')->group(function () {
            // Partner Settlement Routes
            Route::get('/list', function () {
                return view('partnership::settlement.list');
            })
                ->middleware('can:partner.settlement.view')
                ->name('partner.settlement.list'); // View
            Route::get('/datatable-list', [PartnerSettlementController::class, 'datatableList'])->name('partner.settlement.datatable.list'); // Datatable List
            Route::get('/create', [PartnerSettlementController::class, 'create'])
                ->middleware('can:partner.settlement.create')
                ->name('partner.settlement.create'); // View
            Route::get('/edit/{id}', [PartnerSettlementController::class, 'edit'])
                ->middleware('can:partner.settlement.edit')
                ->name('partner.settlement.edit'); // Edit
            Route::post('/store', [PartnerSettlementController::class, 'store'])->name('partner.settlement.store'); // Save operation
            Route::put('/update', [PartnerSettlementController::class, 'store'])->name('partner.settlement.update'); // Update
            Route::post('/delete/', [PartnerSettlementController::class, 'delete'])->middleware('can:partner.settlement.delete')->name('partner.settlement.delete'); // delete operation
        });

    }); // Partner

});
