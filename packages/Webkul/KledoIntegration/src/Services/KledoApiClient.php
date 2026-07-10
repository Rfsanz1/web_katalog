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

    /**
     * POST /finance/invoices — create a new invoice.
     *
     * @param  array<string, mixed>  $payload
     */
    public function createInvoice(array $payload): Response
    {
        return $this->client()->post('/finance/invoices', $payload);
    }

    /**
     * GET /finance/invoices — list invoices (used for connection test).
     */
    public function listInvoices(int $limit = 1): Response
    {
        return $this->client()->get('/finance/invoices', ['per_page' => $limit]);
    }

    /**
     * Return true when the stored token is non-empty.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->accessToken);
    }
}
