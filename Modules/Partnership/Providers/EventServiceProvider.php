<?php

namespace Modules\Partnership\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \Modules\Partnership\Events\SaleCreated::class => [
            \Modules\Partnership\Listeners\HandleSaleCreated::class,
        ],
        \Modules\Partnership\Events\SalePaymentCreated::class => [
            \Modules\Partnership\Listeners\HandleSalePaymentCreated::class,
        ],
        \Modules\Partnership\Events\SaleReturnCreated::class => [
            \Modules\Partnership\Listeners\HandleSaleReturnCreated::class,
        ],
        \Modules\Partnership\Events\SaleReturnPaymentCreated::class => [
            \Modules\Partnership\Listeners\HandleSaleReturnPaymentCreated::class,
        ],
    ];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
