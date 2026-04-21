<?php

namespace Modules\Partnership\Events;

use App\Models\Sale\SaleReturn;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaleReturnCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $return;

    /**
     * Create a new event instance.
     */
    public function __construct(SaleReturn $return)
    {
        $this->return = $return;
    }

    /**
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
