<?php

namespace Webkul\KledoIntegration\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Event::listen(
            'checkout.order.save.after',
            'Webkul\KledoIntegration\Listeners\CreateKledoInvoice@handle'
        );
    }
}
