<x-admin::layouts>
    <x-slot:title>
        @lang('kledo::app.admin.sync.title')
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="py-3 text-xl font-bold text-gray-800 dark:text-white">
            @lang('kledo::app.admin.sync.title')
        </p>

        {{-- Test Connection button --}}
        <div class="flex items-center gap-x-2.5">
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

    {{-- Stats row --}}
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div class="rounded-lg border bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                @lang('kledo::app.admin.sync.stats.synced')
            </p>
            <p class="text-2xl font-bold text-green-600">{{ $syncedCount }}</p>
        </div>

        <div class="rounded-lg border bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <p class="text-xs text-gray-500 dark:text-gray-400">
                @lang('kledo::app.admin.sync.stats.failed')
            </p>
            <p class="text-2xl font-bold text-red-600">{{ $failedCount }}</p>
        </div>
    </div>

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

    @if (! $isConfigured)
        <div class="mb-4 rounded bg-yellow-100 p-3 text-sm text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
            ⚠️ @lang('kledo::app.admin.sync.token-missing')
        </div>
    @endif

    {{-- Sync log table --}}
    <div class="overflow-hidden rounded-xl border dark:border-gray-700">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">
                        @lang('kledo::app.admin.sync.table.order-id')
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">
                        @lang('kledo::app.admin.sync.table.increment-id')
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">
                        @lang('kledo::app.admin.sync.table.status')
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">
                        @lang('kledo::app.admin.sync.table.response')
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
                @forelse ($logs as $log)
                    <tr>
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200">
                            #{{ $log->order_id }}
                        </td>
                        <td class="px-4 py-3 text-gray-800 dark:text-gray-200">
                            @if ($log->order)
                                <a
                                    href="{{ route('admin.sales.orders.view', $log->order_id) }}"
                                    class="text-blue-600 hover:underline dark:text-blue-400"
                                >
                                    {{ $log->order->increment_id }}
                                </a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($log->status === 'synced')
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900 dark:text-green-300">
                                    synced
                                </span>
                            @else
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900 dark:text-red-300">
                                    failed
                                </span>
                            @endif
                        </td>
                        <td class="max-w-xs truncate px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">
                            {{ $log->response_body ? \Illuminate\Support\Str::limit($log->response_body, 120) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400">
                            {{ $log->created_at->format('d M Y H:i') }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($log->status === 'failed')
                                <form method="POST" action="{{ route('admin.kledo.sync.retry', $log->order_id) }}">
                                    @csrf
                                    <button
                                        type="submit"
                                        class="secondary-button text-xs"
                                    >
                                        @lang('kledo::app.admin.sync.table.retry')
                                    </button>
                                </form>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td
                            colspan="6"
                            class="px-4 py-8 text-center text-gray-400 dark:text-gray-500"
                        >
                            @lang('kledo::app.admin.sync.no-logs')
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($logs->hasPages())
        <div class="mt-4">
            {{ $logs->links() }}
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
