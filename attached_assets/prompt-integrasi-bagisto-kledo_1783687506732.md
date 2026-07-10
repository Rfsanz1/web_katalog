# Prompt untuk Replit: Integrasi Bagisto → Kledo (Auto Invoice)

Copy-paste seluruh isi di bawah ini ke Replit Agent/AI di project Bagisto kamu.

---

## PROMPT

Saya punya project e-commerce berbasis **Bagisto (Laravel)**. Saya ingin membuat integrasi otomatis: **setiap ada order baru yang berhasil dibuat di Bagisto, sistem otomatis membuat invoice di Kledo (software akuntansi) lewat REST API-nya.**

Tolong buatkan custom Laravel package/module dengan spesifikasi berikut:

### 1. Struktur
Buat package baru di `packages/Webkul/KledoIntegration` mengikuti konvensi package Bagisto yang sudah ada (lihat contoh package `Webkul/Payment` atau `Webkul/Notification` untuk pola Service Provider & Listener).

### 2. Konfigurasi
Akun Kledo saya hanya menyediakan **access token statis** (bukan OAuth2 client_id/secret dengan flow authorize/callback). Jadi cukup sederhana:

Buat file config `config/kledo.php` yang menyimpan:
- `access_token`
- `api_base_url` (default: `https://app.kledo.com/api/v1`)

Semua nilai sensitif diambil dari environment variable (`.env`), contoh:
```
KLEDO_ACCESS_TOKEN=
KLEDO_API_BASE_URL=https://app.kledo.com/api/v1
```

Buat service `KledoApiClient` (pakai Laravel `Http` facade) yang menyisipkan token ini di header `Authorization: Bearer {token}` pada setiap request ke Kledo. Tidak perlu flow authorize/callback maupun refresh token otomatis — cukup baca token dari config.

Catatan: kalau nanti token ini expired dan Kledo tidak menyediakan refresh otomatis, cukup generate token baru dari dashboard Kledo dan update `.env` manual.

### 3. Event Listener untuk Order
Bagisto punya event `checkout.order.save.after` yang dipanggil dari `Webkul\Sales\Repositories\OrderRepository` setiap order berhasil disimpan (lihat pola yang sama di `Webkul\Payment\Listeners\GenerateInvoice`).

Buat:
- `EventServiceProvider` yang mendaftarkan listener ini ke event tersebut.
- `Listeners\CreateKledoInvoice` yang menerima objek `$order` (instance `Webkul\Sales\Models\Order`), lalu:
  1. Ambil data: nomor order, tanggal, data customer (nama, email), daftar item (nama produk, SKU, qty, harga satuan), subtotal, diskon, pajak, total.
  2. Mapping data tersebut ke format payload yang dibutuhkan endpoint invoice Kledo.
  3. Kirim POST request ke Kledo API menggunakan `KledoApiClient`.
  4. Simpan `kledo_invoice_id` hasil response ke tabel order (tambahkan kolom baru via migration, misal `kledo_invoice_id` dan `kledo_sync_status`).

### 4. Error Handling & Retry
- Gunakan Laravel Queue (`Job` + `ShouldQueue`) untuk proses pengiriman ke Kledo, jangan dilakukan synchronous saat checkout (supaya tidak memperlambat customer).
- Kalau request gagal, gunakan retry otomatis dengan backoff (misal 3x percobaan, jeda 1 menit, 5 menit, 15 menit).
- Simpan log kegagalan ke `storage/logs` dan/atau tabel `kledo_sync_logs` (kolom: order_id, status, response_body, created_at) supaya bisa di-debug/retry manual dari admin panel.

### 5. Admin UI (opsional tapi disarankan)
Tambahkan halaman kecil di admin panel Bagisto:
- List order yang gagal sync ke Kledo + tombol "Retry".
- Indikator status token (misal tombol "Test Connection").

### 6. Testing
Buat command Artisan `php artisan kledo:test-connection` untuk mengecek apakah token masih valid dan bisa hit API Kledo (misal endpoint `GET /finance/invoices` untuk cek list, tanpa create).

### Catatan penting
- Jangan hardcode token di kode, ambil dari `.env`.
- Ikuti coding style Laravel/Bagisto yang sudah ada di project ini (PSR-12, gunakan Repository pattern kalau ada).
- Karena saya belum punya dokumentasi endpoint invoice Kledo yang pasti, buat dulu struktur kode dengan placeholder yang jelas (`// TODO: sesuaikan field payload dengan dokumentasi resmi Kledo`) di bagian mapping payload, supaya saya tinggal isi setelah saya cek endpoint aslinya lewat Developer Tools browser (Network tab) saat submit invoice manual di Kledo.

---

### Setelah generate, saya akan isi sendiri:
1. Access token dari akun Kledo saya (`KLEDO_ACCESS_TOKEN` di `.env`).
2. Field payload exact sesuai endpoint invoice Kledo (saya cek manual lewat Network tab).
