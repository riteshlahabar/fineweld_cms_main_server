<?php

namespace Modules\Partnership\Listeners;

use Modules\Partnership\Services\ItemProfitDistributionService;

class HandleSaleReturnCreated
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
        $return = $event->return;
        $this->itemProfitDistributionService->recordSaleReturnProfit($return->id);
    }
}
