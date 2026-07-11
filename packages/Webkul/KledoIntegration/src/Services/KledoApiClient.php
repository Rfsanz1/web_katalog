<?php

namespace Webkul\KledoIntegration\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class KledoApiClient
{
    /**
     * Kledo API base URL.
     */
    protected string $baseUrl;

    /**
     * Bearer token for Kledo API.
     */
    protected string $accessToken;

    public function __construct()
    {
        $this->baseUrl     = rtrim(config('kledo.api_base_url', 'https://app.kledo.com/api/v1'), '/');
        $this->accessToken = config('kledo.access_token', '');
    }

    /**
     * Return a pre-configured HTTP client pointing at the Kledo API.
     */
    protected function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->accessToken)
            ->acceptJson()
            ->timeout(30);
    }

    // -------------------------------------------------------------------------
    // Invoices
    // -------------------------------------------------------------------------

    /**
     * POST /finance/invoices — create a new invoice (items included in payload).
     *
     * @param  array<string, mixed>  $payload
     */
    public function createInvoice(array $payload): Response
    {
        return $this->client()->post('/finance/invoices', $payload);
    }

    /**
     * GET /finance/invoices — list invoices (lightweight endpoint used for connection test).
     */
    public function listInvoices(int $limit = 1): Response
    {
        return $this->client()->get('/finance/invoices', ['per_page' => $limit]);
    }

    // -------------------------------------------------------------------------
    // Contacts
    // -------------------------------------------------------------------------

    /**
     * GET /finance/contacts — search contacts by name or phone.
     */
    public function searchContacts(string $search, int $limit = 10): Response
    {
        return $this->client()->get('/finance/contacts', [
            'search'   => $search,
            'per_page' => $limit,
        ]);
    }

    /**
     * POST /finance/contacts — create a new contact.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createContact(array $payload): Response
    {
        return $this->client()->post('/finance/contacts', $payload);
    }

    /**
     * PUT /finance/contacts/{id} — update an existing contact.
     *
     * @param  array<string, mixed>  $payload
     */
    public function updateContact(int $id, array $payload): Response
    {
        return $this->client()->put("/finance/contacts/{$id}", $payload);
    }

    // -------------------------------------------------------------------------
    // Products (for finance_account_id lookup)
    // -------------------------------------------------------------------------

    /**
     * GET /finance/products — search products by name.
     */
    public function searchProducts(string $name, int $limit = 10): Response
    {
        return $this->client()->get('/finance/products', [
            'search'   => $name,
            'per_page' => $limit,
        ]);
    }

    // -------------------------------------------------------------------------
    // Chart of Accounts (COA) — used for best-effort payment method mapping
    // -------------------------------------------------------------------------

    /**
     * GET /finance/accounts — list all finance/COA accounts.
     *
     * @param  array<string, mixed>  $params
     */
    public function listFinanceAccounts(array $params = []): Response
    {
        return $this->client()->get('/finance/accounts', $params);
    }

    // -------------------------------------------------------------------------
    // Invoice payments (auto-lunas / settlement)
    // -------------------------------------------------------------------------

    /**
     * POST /finance/invoicepayments — record a payment against an invoice.
     *
     * The Kledo endpoint for settling an invoice is documented at
     * https://gentongmas.api.kledo.com/documentation — adjust the path below
     * if the actual endpoint differs (e.g. /finance/invoices/{id}/payments).
     *
     * @param  array<string, mixed>  $payload  Must include finance_account_id, amount, date
     */
    public function payInvoice(int $invoiceId, array $payload): Response
    {
        return $this->client()->post('/finance/invoicepayments', array_merge(
            ['invoice_id' => $invoiceId],
            $payload
        ));
    }

    // -------------------------------------------------------------------------
    // Utility
    // -------------------------------------------------------------------------

    /**
     * Return true when the stored token is non-empty.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->accessToken);
    }
}
