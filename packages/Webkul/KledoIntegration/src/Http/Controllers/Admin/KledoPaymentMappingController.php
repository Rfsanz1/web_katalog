<?php

namespace Webkul\KledoIntegration\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\KledoIntegration\Models\KledoPaymentMapping;
use Webkul\Payment\Facades\Payment;

class KledoPaymentMappingController extends Controller
{
    /**
     * Show the payment-mapping management page.
     * Lists all Bagisto payment methods alongside their Kledo finance_account_id mapping.
     */
    public function index()
    {
        $mappings = KledoPaymentMapping::orderBy('payment_method_code')->get()
            ->keyBy('payment_method_code');

        // Retrieve all registered Bagisto payment method codes & labels.
        $paymentMethods = $this->getBagistoPaymentMethods();

        return view('kledo::admin.payment-mappings.index', compact('mappings', 'paymentMethods'));
    }

    /**
     * Persist (upsert) payment method → finance_account_id mappings.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'mappings'                           => ['required', 'array'],
            'mappings.*.payment_method_code'     => ['required', 'string', 'max:191'],
            'mappings.*.finance_account_id'      => ['nullable', 'integer', 'min:1'],
        ]);

        foreach ($data['mappings'] as $row) {
            $code             = $row['payment_method_code'];
            $financeAccountId = $row['finance_account_id'] ?? null;

            if ($financeAccountId) {
                KledoPaymentMapping::updateOrCreate(
                    ['payment_method_code' => $code],
                    ['finance_account_id'  => $financeAccountId]
                );
            } else {
                // Remove mapping if the field was cleared.
                KledoPaymentMapping::where('payment_method_code', $code)->delete();
            }
        }

        session()->flash('success', __('kledo::app.admin.payment-mappings.saved'));

        return redirect()->route('admin.kledo.payment-mappings.index');
    }

    /**
     * Delete a single payment mapping.
     */
    public function destroy(int $id)
    {
        KledoPaymentMapping::findOrFail($id)->delete();

        session()->flash('success', __('kledo::app.admin.payment-mappings.deleted'));

        return redirect()->route('admin.kledo.payment-mappings.index');
    }

    /**
     * Return an array of ['code' => '...', 'title' => '...'] for all registered
     * Bagisto payment methods. Falls back to an empty array when the Payment
     * facade is not available (e.g. in test environments).
     *
     * @return array<int, array{code: string, title: string}>
     */
    protected function getBagistoPaymentMethods(): array
    {
        try {
            $methods = Payment::getPaymentMethods();

            return collect($methods)->map(fn ($method, $code) => [
                'code'  => is_string($code) ? $code : ($method['key'] ?? ''),
                'title' => $method['title'] ?? $method['name'] ?? $code,
            ])->values()->all();
        } catch (\Throwable) {
            return [];
        }
    }
}
