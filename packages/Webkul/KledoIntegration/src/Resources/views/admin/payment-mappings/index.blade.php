<x-admin::layouts>
    <x-slot:title>
        @lang('kledo::app.admin.payment-mappings.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="py-3 text-xl font-bold text-gray-800 dark:text-white">
            @lang('kledo::app.admin.payment-mappings.title')
        </p>

        <a
            href="{{ route('admin.kledo.sync.index') }}"
            class="secondary-button"
        >
            @lang('kledo::app.admin.payment-mappings.back')
        </a>
    </div>

    <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
        @lang('kledo::app.admin.payment-mappings.description')
    </p>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="mb-4 rounded bg-green-100 p-3 text-sm text-green-800 dark:bg-green-900 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded bg-red-100 p-3 text-sm text-red-800 dark:bg-red-900 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    @if (count($paymentMethods) === 0)
        <div class="rounded-xl border p-8 text-center text-gray-400 dark:border-gray-700 dark:text-gray-500">
            @lang('kledo::app.admin.payment-mappings.no-methods')
        </div>
    @else
        <form method="POST" action="{{ route('admin.kledo.payment-mappings.store') }}">
            @csrf

            <div class="overflow-hidden rounded-xl border dark:border-gray-700">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">
                                @lang('kledo::app.admin.payment-mappings.code')
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300 w-60">
                                @lang('kledo::app.admin.payment-mappings.account-id')
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        @foreach ($paymentMethods as $index => $method)
                            @php
                                $code    = $method['code'];
                                $title   = $method['title'];
                                $mapping = $mappings->get($code);
                            @endphp

                            <input type="hidden" name="mappings[{{ $index }}][payment_method_code]" value="{{ $code }}">

                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-800 dark:text-gray-200">{{ $title }}</p>
                                    <p class="text-xs text-gray-400 font-mono">{{ $code }}</p>
                                </td>

                                <td class="px-4 py-3">
                                    <input
                                        type="number"
                                        name="mappings[{{ $index }}][finance_account_id]"
                                        value="{{ $mapping?->finance_account_id }}"
                                        placeholder="e.g. 42"
                                        min="1"
                                        class="w-full rounded border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-800 placeholder-gray-400 focus:border-blue-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:placeholder-gray-500"
                                    >
                                    <p class="mt-1 text-xs text-gray-400">
                                        @lang('kledo::app.admin.payment-mappings.account-hint')
                                    </p>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit" class="primary-button">
                    @lang('kledo::app.admin.payment-mappings.save')
                </button>
            </div>
        </form>
    @endif
</x-admin::layouts>
