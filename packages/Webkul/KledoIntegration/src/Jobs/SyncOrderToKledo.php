<?php

namespace Webkul\KledoIntegration\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Webkul\KledoIntegration\Models\KledoPaymentMapping;
use Webkul\KledoIntegration\Models\KledoSyncLog;
use Webkul\KledoIntegration\Services\KledoApiClient;
use Webkul\Sales\Repositories\OrderRepository;

class SyncOrderToKledo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of attempts before the job is considered failed.
     * Only connection/timeout errors trigger a retry; validation errors do not.
     */
    public int $tries = 3;

    /**
     * Seconds to wait before each retry attempt.
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

    // -------------------------------------------------------------------------
    // Entry point
    // -------------------------------------------------------------------------

    /**
     * Execute the job.
     * Follows the four-step process defined in the integration spec.
     */
    public function handle(OrderRepository $orderRepository, KledoApiClient $client): void
    {
        $order = $orderRepository->find($this->orderId);

        if (! $order) {
            Log::warning('[Kledo] Order not found', ['order_id' => $this->orderId]);

            return;
        }

        // Eager-load all needed relationships upfront.
        $order->load(['items.product', 'shippingAddress', 'billingAddress', 'payment', 'customer']);

        // Idempotency guard: skip if a previous attempt already synced successfully.
        if ($order->kledo_invoice_id && $order->kledo_sync_status === 'success') {
            Log::info('[Kledo] Skipping — order already synced', [
                'order_id'         => $this->orderId,
                'kledo_invoice_id' => $order->kledo_invoice_id,
            ]);

            return;
        }

        // Mark as pending so the admin UI shows it is being processed.
        $order->update(['kledo_sync_status' => 'pending']);

        // -----------------------------------------------------------------
        // STEP 1 — Resolve Kledo contact for the customer
        // -----------------------------------------------------------------
        try {
            $contactId = $this->resolveContact($order, $client);

            $this->logSync($this->orderId, 'contact', 'success', "contact_id={$contactId}");
        } catch (\Throwable $e) {
            $this->logSync($this->orderId, 'contact', 'failed', $e->getMessage());
            $order->update(['kledo_sync_status' => 'failed']);

            // Re-throw so Laravel's retry mechanism can pick it up for connection errors.
            throw $e;
        }

        // -----------------------------------------------------------------
        // STEP 3 — Build items array (resolve finance_account_id per product)
        // Must be done before Step 2 because items are part of the invoice body.
        // -----------------------------------------------------------------
        $items = $this->buildItems($order, $client);

        // -----------------------------------------------------------------
        // STEP 2 — Create the invoice in Kledo (items included in body)
        // -----------------------------------------------------------------
        try {
            $kledoInvoiceId = $this->createInvoice($order, $contactId, $items, $client);

            $order->update([
                'kledo_invoice_id'  => $kledoInvoiceId,
                'kledo_sync_status' => 'success',
            ]);

            $this->logSync($this->orderId, 'invoice', 'success', "kledo_invoice_id={$kledoInvoiceId}");
        } catch (\Throwable $e) {
            $this->logSync($this->orderId, 'invoice', 'failed', $e->getMessage());
            $order->update(['kledo_sync_status' => 'failed']);

            throw $e;
        }

        // -----------------------------------------------------------------
        // STEP 4 — Auto-pay the invoice (skip for Cash On Delivery)
        // -----------------------------------------------------------------
        $paymentCode = $order->payment?->method ?? '';

        if (strtolower($paymentCode) !== 'cashondelivery') {
            try {
                $this->autoPayInvoice($kledoInvoiceId, $paymentCode, $order, $client);

                $this->logSync($this->orderId, 'payment', 'success', "Invoice {$kledoInvoiceId} paid via {$paymentCode}");
            } catch (\Throwable $e) {
                // Step 4 failure must NOT change sync_status — invoice is still created.
                $this->logSync(
                    $this->orderId,
                    'payment',
                    'warning',
                    'Auto-payment failed (manual settlement required): '.$e->getMessage()
                );

                Log::warning('[Kledo] Auto-payment failed — invoice was created but needs manual settlement', [
                    'order_id'         => $this->orderId,
                    'kledo_invoice_id' => $kledoInvoiceId,
                    'payment_method'   => $paymentCode,
                    'error'            => $e->getMessage(),
                ]);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Step 1 helper: resolve or create the Kledo contact
    // -------------------------------------------------------------------------

    /**
     * Return the Kledo contact_id for the order's customer.
     * Checks the cached kledo_contact_id on the customer row first, then
     * searches Kledo by name/phone, then creates a new contact if not found.
     */
    protected function resolveContact($order, KledoApiClient $client): ?int
    {
        $customer = $order->customer;

        // Fast path: already cached on the customer record.
        if ($customer && $customer->kledo_contact_id) {
            return (int) $customer->kledo_contact_id;
        }

        $name     = trim($order->customer_first_name.' '.$order->customer_last_name);
        $shipping = $order->shippingAddress;
        $phone    = $shipping?->phone ?? ($order->billingAddress?->phone ?? '');
        $address  = $this->formatAddress($shipping);

        // Search Kledo for an existing contact.
        $searchResponse = $client->searchContacts($name);

        if ($searchResponse->successful()) {
            $contacts  = data_get($searchResponse->json(), 'data.data', []);
            $contactId = null;

            foreach ($contacts as $contact) {
                $nameMatch  = strtolower($contact['name'] ?? '') === strtolower($name);
                $phoneClean = preg_replace('/\D/', '', $phone);
                $kledoPhone = preg_replace('/\D/', '', $contact['phone'] ?? '');
                $phoneMatch = $phoneClean !== '' && $phoneClean === $kledoPhone;

                if ($nameMatch || $phoneMatch) {
                    $contactId = (int) $contact['id'];

                    // Update address in Kledo if it has changed.
                    if ($address !== '' && ($contact['address'] ?? '') !== $address) {
                        $client->updateContact($contactId, ['address' => $address]);
                    }

                    break;
                }
            }

            if ($contactId) {
                if ($customer) {
                    $customer->update(['kledo_contact_id' => $contactId]);
                }

                return $contactId;
            }
        }

        // Contact not found — create a new one.
        $createResponse = $client->createContact([
            'name'        => $name,
            'phone'       => $phone,
            'address'     => $address,
            'type_id'     => 4,
            'is_customer' => 1,
        ]);

        if (! $createResponse->successful()) {
            throw new \RuntimeException(
                '[Kledo] Failed to create contact: '.$createResponse->body()
            );
        }

        $contactId = data_get($createResponse->json(), 'data.id');

        if ($customer && $contactId) {
            $customer->update(['kledo_contact_id' => $contactId]);
        }

        return $contactId ? (int) $contactId : null;
    }

    // -------------------------------------------------------------------------
    // Step 3 helper: build items array (resolves finance_account_id per item)
    // -------------------------------------------------------------------------

    /**
     * Build the items[] array for the Kledo invoice payload.
     * Items whose finance_account_id cannot be resolved are skipped and logged.
     *
     * @param  \Webkul\Sales\Models\Order  $order
     * @return array<int, array<string, mixed>>
     */
    protected function buildItems($order, KledoApiClient $client): array
    {
        $items = [];

        foreach ($order->items as $item) {
            // Skip child items (bundle/configurable children); only send parents.
            if ($item->parent_id) {
                continue;
            }

            try {
                $financeAccountId = $this->resolveProductFinanceAccount($item, $client);

                if ($financeAccountId === null) {
                    $this->logSync(
                        $this->orderId,
                        'item-'.$item->id,
                        'warning',
                        "Product '{$item->name}' (item #{$item->id}) has no finance_account_id in Kledo — item skipped."
                    );

                    Log::warning('[Kledo] Item skipped — no finance_account_id', [
                        'order_id' => $this->orderId,
                        'item_id'  => $item->id,
                        'product'  => $item->name,
                    ]);

                    continue;
                }

                $items[] = [
                    'finance_account_id' => $financeAccountId,
                    'desc'               => $item->name,
                    'qty'                => (float) $item->qty_ordered,
                    'price'              => (float) $item->price,
                    'amount'             => round((float) $item->qty_ordered * (float) $item->price, 2),
                    'discount_percent'   => 0,
                ];
            } catch (\Throwable $e) {
                // Per spec: partial item failures must not abort the whole process.
                $this->logSync(
                    $this->orderId,
                    'item-'.$item->id,
                    'warning',
                    "Item '{$item->name}' skipped due to error: ".$e->getMessage()
                );

                Log::warning('[Kledo] Item resolution error', [
                    'order_id' => $this->orderId,
                    'item_id'  => $item->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return $items;
    }

    /**
     * Resolve the Kledo finance_account_id for a single order item.
     * Checks the cached value on the product row first, then searches Kledo.
     *
     * @param  \Webkul\Sales\Models\OrderItem  $item
     */
    protected function resolveProductFinanceAccount($item, KledoApiClient $client): ?int
    {
        $product = $item->product;

        if ($product && $product->kledo_finance_account_id) {
            return (int) $product->kledo_finance_account_id;
        }

        $response = $client->searchProducts($item->name);

        if ($response->successful()) {
            $kledoProducts = data_get($response->json(), 'data.data', []);

            foreach ($kledoProducts as $kledoProduct) {
                if (strtolower($kledoProduct['name'] ?? '') === strtolower($item->name)) {
                    $financeAccountId = $kledoProduct['finance_account_id'] ?? null;

                    if ($financeAccountId && $product) {
                        $product->update(['kledo_finance_account_id' => $financeAccountId]);
                    }

                    return $financeAccountId ? (int) $financeAccountId : null;
                }
            }
        }

        // Not found in Kledo — caller will skip and log this item.
        return null;
    }

    // -------------------------------------------------------------------------
    // Step 2 helper: create the invoice
    // -------------------------------------------------------------------------

    /**
     * POST /finance/invoices and return the Kledo invoice ID.
     *
     * @param  \Webkul\Sales\Models\Order  $order
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function createInvoice($order, ?int $contactId, array $items, KledoApiClient $client): int
    {
        $transDate = $order->created_at->format('Y-m-d');
        $dueDate   = $order->created_at->copy()->addDays(config('kledo.due_days', 30))->format('Y-m-d');

        $shipping = $order->shippingAddress;
        $memo     = implode(' | ', array_filter([
            $order->channel_name,
            $this->formatAddress($shipping),
        ]));

        $payload = [
            'trans_date'  => $transDate,
            'due_date'    => $dueDate,
            'memo'        => $memo,
            'include_tax' => 0,
            'items'       => $items,
        ];

        // Only attach contact_id when we have a valid one.
        if ($contactId) {
            $payload['contact_id'] = $contactId;
        }

        $response = $client->createInvoice($payload);

        if (! $response->successful()) {
            throw new \RuntimeException(
                '[Kledo] Invoice creation failed (HTTP '.$response->status().'): '.$response->body()
            );
        }

        $invoiceId = data_get($response->json(), 'data.id');

        if (! $invoiceId) {
            throw new \RuntimeException(
                '[Kledo] Invoice created but response contained no ID: '.$response->body()
            );
        }

        return (int) $invoiceId;
    }

    // -------------------------------------------------------------------------
    // Step 4 helper: auto-pay the invoice
    // -------------------------------------------------------------------------

    /**
     * POST a payment against the Kledo invoice.
     * Resolves the finance_account_id from the mapping table or best-effort COA search.
     *
     * @param  \Webkul\Sales\Models\Order  $order
     *
     * @throws \RuntimeException when finance_account_id cannot be resolved.
     */
    protected function autoPayInvoice(int $kledoInvoiceId, string $paymentCode, $order, KledoApiClient $client): void
    {
        // 1. Check explicit payment mapping table.
        $mapping          = KledoPaymentMapping::where('payment_method_code', $paymentCode)->first();
        $financeAccountId = $mapping?->finance_account_id;

        // 2. Best-effort: search COA by name similarity.
        if (! $financeAccountId) {
            $response = $client->listFinanceAccounts();

            if ($response->successful()) {
                $accounts = data_get(
                    $response->json(),
                    'data.data',
                    data_get($response->json(), 'data', [])
                );

                $codeLower = strtolower(str_replace(['_', '-'], ' ', $paymentCode));

                foreach ($accounts as $account) {
                    $accountName = strtolower($account['name'] ?? '');

                    if (str_contains($accountName, $codeLower) || str_contains($codeLower, $accountName)) {
                        $financeAccountId = (int) $account['id'];
                        break;
                    }
                }
            }
        }

        if (! $financeAccountId) {
            throw new \RuntimeException(
                "No finance_account_id found for payment method '{$paymentCode}'. "
                .'Add a mapping in the Kledo > Payment Mappings admin page.'
            );
        }

        $response = $client->payInvoice($kledoInvoiceId, [
            'finance_account_id' => $financeAccountId,
            'amount'             => (float) $order->grand_total,
            'date'               => $order->created_at->format('Y-m-d'),
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException(
                '[Kledo] Payment POST failed (HTTP '.$response->status().'): '.$response->body()
            );
        }
    }

    // -------------------------------------------------------------------------
    // Permanent failure handler
    // -------------------------------------------------------------------------

    /**
     * Called by Laravel after all retry attempts are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[Kledo] Job permanently failed', [
            'order_id' => $this->orderId,
            'error'    => $exception->getMessage(),
        ]);

        try {
            \Webkul\Sales\Models\Order::where('id', $this->orderId)->update([
                'kledo_sync_status' => 'failed',
            ]);

            KledoSyncLog::create([
                'order_id'      => $this->orderId,
                'step'          => 'job',
                'status'        => 'failed',
                'response_body' => 'Permanent failure after all retries: '.$exception->getMessage(),
            ]);
        } catch (\Throwable) {
            // Never throw from failed() — just log.
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Persist a sync log entry.
     */
    protected function logSync(int $orderId, string $step, string $status, string $responseBody): void
    {
        KledoSyncLog::create([
            'order_id'      => $orderId,
            'step'          => $step,
            'status'        => $status,
            'response_body' => $responseBody,
        ]);
    }

    /**
     * Format a Bagisto address model into a readable single-line string.
     *
     * @param  \Webkul\Sales\Models\OrderAddress|null  $address
     */
    protected function formatAddress($address): string
    {
        if (! $address) {
            return '';
        }

        return implode(', ', array_filter([
            $address->address1 ?? '',
            $address->address2 ?? '',
            $address->city ?? '',
            $address->state ?? '',
            $address->postcode ?? '',
            $address->country ?? '',
        ]));
    }
}
