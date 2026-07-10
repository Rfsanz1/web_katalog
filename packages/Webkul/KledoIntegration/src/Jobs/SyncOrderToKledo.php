<?php

namespace Webkul\KledoIntegration\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Webkul\KledoIntegration\Models\KledoSyncLog;
use Webkul\KledoIntegration\Services\KledoApiClient;
use Webkul\Sales\Repositories\OrderRepository;

class SyncOrderToKledo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of attempts before the job is considered failed.
     */
    public int $tries = 3;

    /**
     * Number of seconds to wait before retrying.
     * Attempt 1 → 60 s, attempt 2 → 300 s, attempt 3 → 900 s.
     *
     * @return array<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly int $orderId) {}

    /**
     * Execute the job.
     */
    public function handle(OrderRepository $orderRepository, KledoApiClient $client): void
    {
        $order = $orderRepository->find($this->orderId);

        if (! $order) {
            Log::warning('[Kledo] Order not found', ['order_id' => $this->orderId]);

            return;
        }

        // Idempotency guard — skip if a previous attempt already synced this order.
        if ($order->kledo_invoice_id && $order->kledo_sync_status === 'synced') {
            Log::info('[Kledo] Skipping — order already synced', [
                'order_id'         => $this->orderId,
                'kledo_invoice_id' => $order->kledo_invoice_id,
            ]);

            return;
        }

        $payload = $this->buildPayload($order);

        $response = $client->createInvoice($payload);

        if ($response->successful()) {
            $kledoInvoiceId = data_get($response->json(), 'data.id');

            $order->update([
                'kledo_invoice_id'   => $kledoInvoiceId,
                'kledo_sync_status'  => 'synced',
            ]);

            KledoSyncLog::create([
                'order_id'      => $this->orderId,
                'status'        => 'synced',
                'response_body' => $response->body(),
            ]);

            Log::info('[Kledo] Invoice created', [
                'order_id'         => $this->orderId,
                'kledo_invoice_id' => $kledoInvoiceId,
            ]);
        } else {
            $this->handleFailure($order, $response->body(), $response->status());
        }
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[Kledo] Job permanently failed', [
            'order_id' => $this->orderId,
            'error'    => $exception->getMessage(),
        ]);

        // Mark the order as failed so the admin UI can surface it.
        try {
            \Webkul\Sales\Models\Order::where('id', $this->orderId)->update([
                'kledo_sync_status' => 'failed',
            ]);

            KledoSyncLog::create([
                'order_id'      => $this->orderId,
                'status'        => 'failed',
                'response_body' => $exception->getMessage(),
            ]);
        } catch (\Throwable) {
            // Never throw from failed() — just log.
        }
    }

    /**
     * Build the Kledo invoice payload from a Bagisto order.
     *
     * @param  \Webkul\Sales\Models\Order  $order
     * @return array<string, mixed>
     */
    protected function buildPayload($order): array
    {
        // ---------------------------------------------------------------
        // TODO: sesuaikan seluruh field payload di bawah ini dengan
        //       dokumentasi resmi endpoint POST /finance/invoices Kledo.
        //       Cek via Developer Tools → Network tab saat submit invoice
        //       manual di dashboard Kledo, lalu cocokkan key-nya.
        // ---------------------------------------------------------------

        $items = [];

        foreach ($order->items as $item) {
            $items[] = [
                // TODO: sesuaikan key nama produk dengan field Kledo
                'name'        => $item->name,
                // TODO: sesuaikan key SKU
                'code'        => $item->sku,
                // TODO: sesuaikan key qty
                'qty'         => (int) $item->qty_ordered,
                // TODO: sesuaikan key harga satuan (pastikan integer/float sesuai Kledo)
                'price'       => (float) $item->base_price,
                // TODO: tambahkan field pajak per item jika Kledo memerlukannya
                // 'tax_rate' => ...,
            ];
        }

        return [
            // TODO: sesuaikan key nomor transaksi/referensi
            'trans_no'     => $order->increment_id,

            // TODO: sesuaikan format tanggal (Kledo mungkin minta 'YYYY-MM-DD')
            'trans_date'   => $order->created_at->format('Y-m-d'),

            // TODO: sesuaikan key nama kontak/customer
            'contact_name' => $order->customer_full_name ?? ($order->billing_address->name ?? ''),

            // TODO: sesuaikan key email kontak
            'contact_email' => $order->customer_email,

            // TODO: sesuaikan key daftar item
            'items'        => $items,

            // TODO: sesuaikan key diskon (Kledo mungkin hitung per item)
            'discount'     => (float) $order->base_discount_amount,

            // TODO: sesuaikan key pajak total
            'tax'          => (float) $order->base_tax_amount,

            // TODO: sesuaikan key total (mungkin dihitung otomatis oleh Kledo dari items)
            'total'        => (float) $order->base_grand_total,

            // TODO: tambahkan field memo/catatan jika diperlukan
            // 'memo' => 'Order dari Bagisto #' . $order->increment_id,
        ];
    }

    /**
     * Log a non-successful API response and conditionally release the job
     * back to the queue (handled automatically by Laravel's retry logic).
     */
    protected function handleFailure($order, string $responseBody, int $httpStatus): void
    {
        $attemptsRemaining = $this->tries - $this->attempts();

        Log::warning('[Kledo] API returned non-success', [
            'order_id'          => $this->orderId,
            'http_status'       => $httpStatus,
            'response'          => substr($responseBody, 0, 500),
            'attempt'           => $this->attempts(),
            'attempts_remaining'=> $attemptsRemaining,
        ]);

        // Only log to the sync-log table on the final attempt so the UI doesn't
        // show transient failures that will be retried automatically.
        if ($attemptsRemaining <= 0) {
            KledoSyncLog::create([
                'order_id'      => $this->orderId,
                'status'        => 'failed',
                'response_body' => $responseBody,
            ]);

            $order->update(['kledo_sync_status' => 'failed']);
        }

        // Re-throw to trigger Laravel's built-in retry / backoff mechanism.
        throw new \RuntimeException(
            "[Kledo] Invoice creation failed (HTTP {$httpStatus}) for order #{$this->orderId}"
        );
    }
}
