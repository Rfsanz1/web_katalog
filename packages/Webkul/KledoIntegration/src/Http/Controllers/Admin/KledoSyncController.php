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
     * Show the Kledo sync status page.
     * Lists orders that have been processed (kledo_sync_status is not null),
     * with optional filter by status.
     */
    public function index(Request $request)
    {
        $status = $request->query('status');

        $query = \Webkul\Sales\Models\Order::query()
            ->whereNotNull('kledo_sync_status')
            ->orderByDesc('created_at');

        if ($status && in_array($status, ['pending', 'success', 'failed'])) {
            $query->where('kledo_sync_status', $status);
        }

        $orders = $query->paginate(25)->withQueryString();

        $stats = [
            'pending' => \Webkul\Sales\Models\Order::where('kledo_sync_status', 'pending')->count(),
            'success' => \Webkul\Sales\Models\Order::where('kledo_sync_status', 'success')->count(),
            'failed'  => \Webkul\Sales\Models\Order::where('kledo_sync_status', 'failed')->count(),
        ];

        $isConfigured   = $this->client->isConfigured();
        $currentStatus  = $status;

        return view('kledo::admin.index', compact('orders', 'stats', 'isConfigured', 'currentStatus'));
    }

    /**
     * Show the sync log detail for a specific order.
     */
    public function show(int $orderId)
    {
        $order = $this->orderRepository->find($orderId);

        if (! $order) {
            session()->flash('error', __('kledo::app.admin.sync.order-not-found'));

            return redirect()->route('admin.kledo.sync.index');
        }

        $logs = KledoSyncLog::where('order_id', $orderId)
            ->orderByDesc('created_at')
            ->get();

        return view('kledo::admin.logs.show', compact('order', 'logs'));
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

        // Reset status so it does not show as permanently failed.
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
