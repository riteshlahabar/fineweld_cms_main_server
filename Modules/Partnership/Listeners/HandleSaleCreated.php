<?php

namespace Modules\Partnership\Listeners;

use Modules\Partnership\Services\ItemProfitDistributionService;

class HandleSaleCreated
{
    private $itemProfitDistributionService;

    /**
     * Create the event listener.
     */
    public function __construct(
        ItemProfitDistributionService $itemProfitDistributionService
    ) {
        $this->itemProfitDistributionService = $itemProfitDistributionService;
    }

    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        $sale = $event->sale;
        $this->itemProfitDistributionService->recordSaleProfit($sale->id);
    }
}
