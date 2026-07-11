<?php

return [
    'admin' => [
        'menu' => [
            'title'            => 'Kledo Sync',
            'payment-mappings' => 'Payment Mappings',
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
            'no-orders'        => 'Belum ada order yang diproses oleh Kledo sync.',
            'no-logs'          => 'Belum ada log sync untuk order ini.',

            'filter' => [
                'all'     => 'Semua',
                'pending' => 'Pending',
                'success' => 'Berhasil',
                'failed'  => 'Gagal',
            ],

            'table' => [
                'order-id'       => 'Order',
                'increment-id'   => 'No. Order',
                'customer'       => 'Customer',
                'total'          => 'Total',
                'kledo-id'       => 'Kledo Invoice ID',
                'status'         => 'Status',
                'response'       => 'Response API',
                'step'           => 'Step',
                'created-at'     => 'Waktu',
                'actions'        => 'Aksi',
                'retry'          => 'Retry',
                'view-logs'      => 'Detail Log',
            ],

            'stats' => [
                'pending' => 'Pending',
                'success' => 'Berhasil',
                'failed'  => 'Gagal',
            ],

            'detail' => [
                'title'     => 'Log Sync — Order :id',
                'back'      => 'Kembali ke Daftar',
                'order-info'=> 'Informasi Order',
                'kledo-id'  => 'Kledo Invoice ID',
                'sync-status' => 'Status Sync',
            ],
        ],

        'payment-mappings' => [
            'title'       => 'Payment Method Mappings',
            'description' => 'Petakan metode pembayaran Bagisto ke ID akun COA di Kledo. '
                           .'Digunakan untuk pelunasan otomatis invoice setelah order baru dibuat.',
            'code'        => 'Metode Pembayaran (Bagisto)',
            'account-id'  => 'Finance Account ID (Kledo)',
            'account-hint'=> 'Isi ID akun COA dari Kledo (integer). Kosongkan untuk menghapus mapping.',
            'save'        => 'Simpan Semua Mapping',
            'saved'       => 'Mapping berhasil disimpan.',
            'deleted'     => 'Mapping berhasil dihapus.',
            'no-methods'  => 'Tidak ada metode pembayaran yang terdaftar.',
            'back'        => 'Kembali ke Kledo Sync',
        ],
    ],
];
