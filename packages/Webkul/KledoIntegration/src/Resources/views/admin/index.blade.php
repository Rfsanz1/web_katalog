<x-admin::layouts>
    <x-slot:title>
        @lang('kledo::app.admin.sync.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="py-3 text-xl font-bold text-gray-800 dark:text-white">
            @lang('kledo::app.admin.sync.title')
        </p>

        <div class="flex items-center gap-x-2.5">
            <a
                href="{{ route('admin.kledo.payment-mappings.index') }}"
                class="secondary-button"
            >
                @lang('kledo::app.admin.menu.payment-mappings')
            </a>

            <button
                id="btn-test-connection"
                type="button"
                class="secondary-button"
            >
                @lang('kledo::app.admin.sync.test-connection')
            </button>
        </div>
    </div>

    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
        @lang('kledo::app.admin.sync.description')
    </p>

    {{-- Connection test result banner --}}
    <div id="connection-result" class="mb-4 hidden rounded p-3 text-sm"></div>

    {{-- Token missing warning --}}
    @if (! $isConfigured)
        <div class="mb-4 rounded bg-yellow-100 p-3 text-sm text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
            ⚠️ @lang('kledo::app.admin.sync.token-missing')
        </div>
    @endif

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

    {{-- Stats row --}}
    <div class="mb-6 grid grid-cols-3 gap-4">
        @foreach ([
            ['key' => 'pending', 'label' => 'kledo::app.admin.sync.stats.pending', 'color' => 'text-yellow-600'],
            ['key' => 'success', 'label' => 'kledo::app.admin.sync.stats.success', 'color' => 'text-green-600'],
            ['key' => 'failed',  'label' => 'kledo::app.admin.sync.stats.failed',  'color' => 'text-red-600'],
        ] as $stat)
            <a
                href="{{ route('admin.kledo.sync.index', ['status' => $stat['key']]) }}"
                class="block rounded-lg border bg-white p-4 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800 {{ $currentStatus === $stat['key'] ? 'ring-2 ring-blue-500' : '' }}"
            >
                <p class="text-xs text-gray-500 dark:text-gray-400">@lang($stat['label'])</p>
                <p class="text-2xl font-bold {{ $stat['color'] }}">{{ $stats[$stat['key']] }}</p>
            </a>
        @endforeach
    </div>

    {{-- Status filter tabs --}}
    <div class="mb-4 flex gap-2">
        <a
            href="{{ route('admin.kledo.sync.index') }}"
            class="rounded px-3 py-1 text-sm {{ ! $currentStatus ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}"
        >
            @lang('kledo::app.admin.sync.filter.all')
        </a>

        @foreach ([
            'pending' => 'kledo::app.admin.sync.filter.pending',
            'success' => 'kledo::app.admin.sync.filter.success',
            'failed'  => 'kledo::app.admin.sync.filter.failed',
        ] as $value => $label)
            <a
                href="{{ route('admin.kledo.sync.index', ['status' => $value]) }}"
                class="rounded px-3 py-1 text-sm {{ $currentStatus === $value ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}"
            >
                @lang($label)
            </a>
        @endforeach
    </div>

    {{-- Orders table --}}
    <div class="overflow-hidden rounded-xl border dark:border-gray-700">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">
                        @lang('kledo::app.admin.sync.table.increment-id')
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">
                        @lang('kledo::app.admin.sync.table.customer')
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">
                        @lang('kledo::app.admin.sync.table.total')
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">
                        @lang('kledo::app.admin.sync.table.kledo-id')
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">
                        @lang('kledo::app.admin.sync.table.status')
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">
                        @lang('kledo::app.admin.sync.table.created-at')
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">
                        @lang('kledo::app.admin.sync.table.actions')
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @forelse ($orders as $order)
                    <tr>
                        <td class="px-4 py-3">
                            <a
                                href="{{ route('admin.sales.orders.view', $order->id) }}"
                                class="text-blue-600 hover:underline dark:text-blue-400"
                            >
                                {{ $order->increment_id }}
                            </a>
                        </td>

                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200">
                            {{ $order->customer_first_name }} {{ $order->customer_last_name }}
                            <div class="text-xs text-gray-400">{{ $order->customer_email }}</div>
                        </td>

                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200">
                            {{ $order->base_currency_code }} {{ number_format($order->grand_total, 2) }}
                        </td>

                        <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">
                            {{ $order->kledo_invoice_id ?? '—' }}
                        </td>

                        <td class="px-4 py-3">
                            @php $s = $order->kledo_sync_status; @endphp
                            @if ($s === 'success')
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900 dark:text-green-300">success</span>
                            @elseif ($s === 'failed')
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900 dark:text-red-300">failed</span>
                            @elseif ($s === 'pending')
                                <span class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300">pending</span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                            {{ $order->created_at->format('d M Y H:i') }}
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <a
                                    href="{{ route('admin.kledo.sync.show', $order->id) }}"
                                    class="text-xs text-blue-600 hover:underline dark:text-blue-400"
                                >
                                    @lang('kledo::app.admin.sync.table.view-logs')
                                </a>

                                @if ($order->kledo_sync_status === 'failed')
                                    <form method="POST" action="{{ route('admin.kledo.sync.retry', $order->id) }}">
                                        @csrf
                                        <button type="submit" class="secondary-button text-xs">
                                            @lang('kledo::app.admin.sync.table.retry')
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">
                            @lang('kledo::app.admin.sync.no-orders')
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($orders->hasPages())
        <div class="mt-4">
            {{ $orders->links() }}
        </div>
    @endif

    {{-- Inline JS for Test Connection --}}
    <script>
        document.getElementById('btn-test-connection').addEventListener('click', async function () {
            const btn    = this;
            const result = document.getElementById('connection-result');

            btn.disabled    = true;
            btn.textContent = 'Testing…';
            result.className = 'mb-4 rounded p-3 text-sm bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300';
            result.classList.remove('hidden');
            result.textContent = 'Menghubungi Kledo API…';

            try {
                const res  = await fetch('{{ route('admin.kledo.sync.test-connection') }}', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();

                if (data.success) {
                    result.className = 'mb-4 rounded p-3 text-sm bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200';
                } else {
                    result.className = 'mb-4 rounded p-3 text-sm bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200';
                }
                result.textContent = data.message;
            } catch (e) {
                result.className = 'mb-4 rounded p-3 text-sm bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200';
                result.textContent = 'Request error: ' + e.message;
            } finally {
                btn.disabled    = false;
                btn.textContent = '{{ __('kledo::app.admin.sync.test-connection') }}';
            }
        });
    </script>
</x-admin::layouts>
