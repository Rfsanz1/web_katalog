<?php

namespace Webkul\KledoIntegration\Listeners;

use Webkul\KledoIntegration\Jobs\SyncOrderToKledo;
use Webkul\Sales\Models\Order;

class CreateKledoInvoice
{
    /**
     * Handle the checkout.order.save.after event.
     *
     * Dispatches an async job so the checkout flow is never slowed down.
     */
    public function handle(Order $order): void
    {
        SyncOrderToKledo::dispatch($order->id);
    }
}
