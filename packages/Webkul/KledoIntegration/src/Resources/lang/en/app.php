<?php

return [
    'admin' => [
        'menu' => [
            'title' => 'Kledo Sync',
        ],

        'sync' => [
            'title'            => 'Kledo Invoice Sync',
            'description'      => 'Setiap order baru dikirim otomatis ke Kledo sebagai invoice.',
            'test-connection'  => 'Test Koneksi',
            'connection-ok'    => 'Koneksi berhasil — token valid dan API Kledo dapat dijangkau.',
            'connection-failed'=> 'Koneksi gagal — HTTP :status. Periksa token di .env.',
            'token-missing'    => 'KLEDO_ACCESS_TOKEN belum diset di .env.',
            'retry-queued'     => 'Order #:id dijadwalkan ulang untuk sync ke Kledo.',
            'order-not-found'  => 'Order tidak ditemukan.',
            'no-logs'          => 'Belum ada log sync.',

            'table' => [
                'order-id'       => 'Order',
                'increment-id'   => 'No. Order',
                'status'         => 'Status',
                'response'       => 'Response API',
                'created-at'     => 'Waktu',
                'actions'        => 'Aksi',
                'retry'          => 'Retry',
            ],

            'stats' => [
                'synced' => 'Berhasil',
                'failed' => 'Gagal',
            ],
        ],
    ],
];
