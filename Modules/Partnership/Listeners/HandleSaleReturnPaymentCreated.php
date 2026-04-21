<?php

namespace Modules\Partnership\Listeners;

use Modules\Partnership\Services\ItemPaymentDistributionService;

class HandleSaleReturnPaymentCreated
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
        $return = $event->return;
        $this->itemPaymentDistributionService->distributeSaleReturnPayment($return->id);
    }
}
