<?php

namespace Webkul\KledoIntegration\Console\Commands;

use Illuminate\Console\Command;
use Webkul\KledoIntegration\Services\KledoApiClient;

class TestKledoConnection extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'kledo:test-connection';

    /**
     * The console command description.
     */
    protected $description = 'Test whether the configured Kledo access token is valid by calling GET /finance/invoices';

    /**
     * Execute the console command.
     */
    public function handle(KledoApiClient $client): int
    {
        if (! $client->isConfigured()) {
            $this->error('KLEDO_ACCESS_TOKEN is not set in your .env file.');

            return self::FAILURE;
        }

        $this->info('Testing Kledo connection...');

        try {
            $response = $client->listInvoices(limit: 1);
        } catch (\Throwable $e) {
            $this->error('Request failed: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($response->successful()) {
            $this->info('✓ Connection successful! Kledo API is reachable and the token is valid.');

            return self::SUCCESS;
        }

        $this->error(sprintf(
            '✗ Connection failed — HTTP %d: %s',
            $response->status(),
            $response->body()
        ));

        return self::FAILURE;
    }
}
