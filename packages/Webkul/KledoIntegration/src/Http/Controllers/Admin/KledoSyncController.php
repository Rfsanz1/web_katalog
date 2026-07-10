<?php

namespace Webkul\KledoIntegration\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\KledoIntegration\Jobs\SyncOrderToKledo;
use Webkul\KledoIntegration\Models\KledoSyncLog;
use Webkul\KledoIntegration\Services\KledoApiClient;
use Webkul\Sales\Repositories\OrderRepository;

class KledoSyncController extends Controller
{
    public function __construct(
        protected OrderRepository $orderRepository,
        protected KledoApiClient $client
    ) {}

    /**
     * Show the Kledo sync status page — lists failed / recent sync logs.
     */
    public function index()
    {
        $logs = KledoSyncLog::with('order')
            ->orderByDesc('created_at')
            ->paginate(25);

        $failedCount  = KledoSyncLog::where('status', 'failed')->count();
        $syncedCount  = KledoSyncLog::where('status', 'synced')->count();
        $isConfigured = $this->client->isConfigured();

        return view('kledo::admin.index', compact('logs', 'failedCount', 'syncedCount', 'isConfigured'));
    }

    /**
     * Manually re-queue a sync job for the given order.
     */
    public function retry(int $orderId)
    {
        $order = $this->orderRepository->find($orderId);

        if (! $order) {
            session()->flash('error', __('kledo::app.admin.sync.order-not-found'));

            return redirect()->route('admin.kledo.sync.index');
        }

        // Reset status so it doesn't show as permanently failed.
        $order->update(['kledo_sync_status' => 'pending']);

        SyncOrderToKledo::dispatch($orderId);

        session()->flash('success', __('kledo::app.admin.sync.retry-queued', ['id' => $order->increment_id]));

        return redirect()->route('admin.kledo.sync.index');
    }

    /**
     * Test the Kledo API connection and return a JSON response.
     */
    public function testConnection()
    {
        if (! $this->client->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => __('kledo::app.admin.sync.token-missing'),
            ]);
        }

        try {
            $response = $this->client->listInvoices(limit: 1);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => __('kledo::app.admin.sync.connection-ok'),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => __('kledo::app.admin.sync.connection-failed', [
                    'status' => $response->status(),
                ]),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
