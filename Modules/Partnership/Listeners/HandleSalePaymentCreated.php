<?php

namespace Modules\Partnership\Listeners;

use Modules\Partnership\Services\ItemPaymentDistributionService;

class HandleSalePaymentCreated
{
    private $itemPaymentDistributionService;

    /**
     * Create the event listener.
     */
    public function __construct(
        ItemPaymentDistributionService $itemPaymentDistributionService
    ) {
        $this->itemPaymentDistributionService = $itemPaymentDistributionService;
    }

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        $sale = $event->sale;
        $this->itemPaymentDistributionService->distributeSalePayment($sale->id);
    }
}
