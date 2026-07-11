<x-admin::layouts>
    <x-slot:title>
        @lang('kledo::app.admin.sync.detail.title', ['id' => $order->increment_id])
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="py-3 text-xl font-bold text-gray-800 dark:text-white">
            @lang('kledo::app.admin.sync.detail.title', ['id' => $order->increment_id])
        </p>

        <div class="flex items-center gap-x-2.5">
            <a
                href="{{ route('admin.kledo.sync.index') }}"
                class="secondary-button"
            >
                @lang('kledo::app.admin.sync.detail.back')
            </a>

            @if ($order->kledo_sync_status === 'failed')
                <form method="POST" action="{{ route('admin.kledo.sync.retry', $order->id) }}">
                    @csrf
                    <button type="submit" class="primary-button">
                        @lang('kledo::app.admin.sync.table.retry')
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Order summary card --}}
    <div class="mb-6 rounded-xl border bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
        <p class="mb-3 text-sm font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide">
            @lang('kledo::app.admin.sync.detail.order-info')
        </p>

        <dl class="grid grid-cols-2 gap-x-6 gap-y-3 sm:grid-cols-4 text-sm">
            <div>
                <dt class="text-gray-400">@lang('kledo::app.admin.sync.table.increment-id')</dt>
                <dd class="font-medium text-gray-800 dark:text-gray-200">
                    <a
                        href="{{ route('admin.sales.orders.view', $order->id) }}"
                        class="text-blue-600 hover:underline dark:text-blue-400"
                    >
                        {{ $order->increment_id }}
                    </a>
                </dd>
            </div>

            <div>
                <dt class="text-gray-400">@lang('kledo::app.admin.sync.table.customer')</dt>
                <dd class="font-medium text-gray-800 dark:text-gray-200">
                    {{ $order->customer_first_name }} {{ $order->customer_last_name }}
                </dd>
            </div>

            <div>
                <dt class="text-gray-400">@lang('kledo::app.admin.sync.detail.kledo-id')</dt>
                <dd class="font-mono text-xs text-gray-600 dark:text-gray-300">
                    {{ $order->kledo_invoice_id ?? '—' }}
                </dd>
            </div>

            <div>
                <dt class="text-gray-400">@lang('kledo::app.admin.sync.detail.sync-status')</dt>
                <dd>
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
                </dd>
            </div>
        </dl>
    </div>

    {{-- Sync log table --}}
    <div class="overflow-hidden rounded-xl border dark:border-gray-700">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300 w-32">
                        @lang('kledo::app.admin.sync.table.step')
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300 w-28">
                        @lang('kledo::app.admin.sync.table.status')
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">
                        @lang('kledo::app.admin.sync.table.response')
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300 w-40">
                        @lang('kledo::app.admin.sync.table.created-at')
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @forelse ($logs as $log)
                    <tr>
                        <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-300">
                            {{ $log->step ?? '—' }}
                        </td>

                        <td class="px-4 py-3">
                            @if ($log->status === 'success')
                                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900 dark:text-green-300">success</span>
                            @elseif ($log->status === 'failed')
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900 dark:text-red-300">failed</span>
                            @elseif ($log->status === 'warning')
                                <span class="rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300">warning</span>
                            @else
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $log->status }}</span>
                            @endif
                        </td>

                        <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400 break-all">
                            {{ $log->response_body ?? '—' }}
                        </td>

                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                            {{ $log->created_at->format('d M Y H:i:s') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td
                            colspan="4"
                            class="px-4 py-8 text-center text-gray-400 dark:text-gray-500"
                        >
                            @lang('kledo::app.admin.sync.no-logs')
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin::layouts>
