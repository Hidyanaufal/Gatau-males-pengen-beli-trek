# LaundryLink API v1 — Dokumentasi untuk Pengembang Android

## 1. Dasar

| Item | Nilai |
|---|---|
| Base URL (emulator Android) | `http://10.0.2.2:8000/api/v1` |
| Base URL (HP asli, satu Wi-Fi) | `http://<IP-LAPTOP>:8000/api/v1` (server dijalankan dengan `php artisan serve --host=0.0.0.0`) |
| Base URL (production) | `https://<DOMAIN-LAUNDRYLINK>/api/v1` *(diisi saat deployment)* |
| Format | JSON |
| Autentikasi | Token Bearer (Laravel Sanctum) |

Simpan base URL di satu tempat (misalnya `BuildConfig`) supaya mudah berganti antara lokal dan production.

Catatan Android (hanya untuk pengembangan lokal): alamat `http://` memerlukan izin cleartext
(`android:usesCleartextTraffic="true"` atau network security config). Production wajib HTTPS.

### Header wajib di setiap permintaan

```
Accept: application/json
Content-Type: application/json
```

Untuk endpoint privat, tambahkan:

```
Authorization: Bearer <token>
```

## 2. Format respons

**Sukses**
```json
{ "success": true, "message": "Teks pesan", "data": { } }
```

**Daftar (pagination)**
```json
{
  "success": true,
  "message": "Daftar layanan.",
  "data": [ ],
  "meta": { "current_page": 1, "per_page": 20, "total": 4, "last_page": 1 }
}
```
Parameter: `?page=1&per_page=10`.

**Gagal**
```json
{ "success": false, "message": "Teks pesan", "errors": { "field": ["pesan"] } }
```
`errors` hanya ada pada kode 422.

### Kode status HTTP

| Kode | Arti |
|---|---|
| 200 / 201 | Berhasil / berhasil dibuat |
| 401 | Token tidak ada atau tidak valid, atau email/password salah |
| 403 | Akun dinonaktifkan |
| 404 | Data tidak ditemukan (termasuk pesanan milik orang lain) |
| 422 | Validasi gagal atau aturan bisnis dilanggar |
| 429 | Terlalu banyak permintaan (login/daftar maks. 10 per menit; endpoint privat 60 per menit) |

Respons 401 dari middleware autentikasi berbentuk `{ "message": "Unauthenticated." }`
(tanpa field `success`). Anggap kode 401 sebagai "perlu login ulang".
Pesan validasi dari server berbahasa Inggris (bawaan Laravel); tampilkan teks sendiri di aplikasi bila perlu.

### Aturan data
- **Uang** dikirim sebagai **string desimal**, misalnya `"17500.00"` (Rupiah). Jangan memakai `float`; gunakan `BigDecimal`/`String`.
- **Tanggal** memakai ISO 8601, misalnya `"2026-10-16T09:00:00+07:00"`.
- **Total dan harga selalu dihitung server.** Aplikasi hanya mengirim `service_id` dan `quantity`.

## 3. Nilai enumerasi

**status pesanan**: `pending` (Menunggu), `confirmed` (Dikonfirmasi), `processing` (Diproses),
`ready` (Siap diambil/diantar), `completed` (Selesai), `cancelled` (Dibatalkan)

**payment_status**: `unpaid` (Belum dibayar), `pending` (Menunggu pembayaran), `paid` (Lunas),
`failed` (Gagal), `expired` (Kedaluwarsa)

**fulfillment_type**: `drop_off` (datang sendiri), `pickup_delivery` (antar-jemput)

**unit layanan**: `kg` (per kilogram), `item` (per item; jumlah harus bilangan bulat)

---

## 4. Endpoint

### POST `/register`
Body:
```json
{
  "name": "Dewi Lestari",
  "email": "dewi@example.com",
  "password": "rahasia123",
  "password_confirmation": "rahasia123",
  "phone": "081234567890",
  "address": "Jl. Anggrek No. 3, Semarang",
  "device_name": "pixel-7"
}
```
`phone`, `address`, `device_name` opsional. Password minimal 8 karakter.

201:
```json
{
  "success": true,
  "message": "Pendaftaran berhasil.",
  "data": {
    "user": { "id": 12, "name": "Dewi Lestari", "email": "dewi@example.com", "phone": "081234567890", "address": "Jl. Anggrek No. 3, Semarang" },
    "token": "1|abcdef...",
    "token_type": "Bearer"
  }
}
```
422: email sudah terdaftar / data tidak valid.

### POST `/login`
Body: `{ "email": "dewi@example.com", "password": "rahasia123", "device_name": "pixel-7" }`

200: sama seperti register (`"message": "Login berhasil."`).
401: `{ "success": false, "message": "Email atau password salah." }`
403: akun dinonaktifkan.

Simpan `token` dengan aman (misalnya EncryptedSharedPreferences). Token tidak kedaluwarsa otomatis;
hapus dari perangkat saat logout.

### POST `/logout` *(privat)*
200: `{ "success": true, "message": "Logout berhasil.", "data": null }`

### GET `/me` *(privat)*
200: `data` berisi `id, name, email, phone, address`.

### GET `/services`
Layanan aktif saja, urut nama. Tidak perlu token.
```json
{
  "success": true,
  "message": "Daftar layanan.",
  "data": [
    {
      "id": 1,
      "name": "Cuci Kiloan Reguler",
      "description": "Cuci dan lipat, selesai 2 hari.",
      "price": "7000.00",
      "unit": "kg",
      "unit_label": "Per kilogram",
      "estimated_hours": 48
    }
  ],
  "meta": { "current_page": 1, "per_page": 20, "total": 4, "last_page": 1 }
}
```

### GET `/services/{id}`
200: satu objek layanan seperti di atas. 404 bila tidak ada atau tidak aktif.

### POST `/orders` *(privat)*
Body:
```json
{
  "fulfillment_type": "pickup_delivery",
  "address": "Jl. Anggrek No. 3, Semarang",
  "scheduled_at": "2026-10-16T09:00:00+07:00",
  "notes": "Tolong dipisah baju putih",
  "items": [
    { "service_id": 1, "quantity": 2.5 },
    { "service_id": 4, "quantity": 1 }
  ]
}
```
Aturan:
- `fulfillment_type` wajib. Jika `pickup_delivery`, `address` dan `scheduled_at` (harus di masa depan) wajib.
- `items`: 1–20 baris, `service_id` tidak boleh dobel, layanan harus aktif.
- `quantity`: 0.1–999. Untuk layanan satuan `item` harus bilangan bulat.
- Field lain (mis. `total`, `price`, `status`) **diabaikan** server.

201:
```json
{
  "success": true,
  "message": "Pesanan berhasil dibuat.",
  "data": {
    "id": 21,
    "order_number": "LL-261015-K3X9Q",
    "status": "pending",
    "status_label": "Menunggu",
    "payment_status": "unpaid",
    "payment_status_label": "Belum dibayar",
    "fulfillment_type": "pickup_delivery",
    "total": "42500.00",
    "address": "Jl. Anggrek No. 3, Semarang",
    "scheduled_at": "2026-10-16T09:00:00+07:00",
    "notes": "Tolong dipisah baju putih",
    "ordered_at": "2026-10-15T14:20:11+07:00",
    "can_cancel": true,
    "items": [
      { "service_id": 1, "service_name": "Cuci Kiloan Reguler", "unit_price": "7000.00", "quantity": "2.50", "subtotal": "17500.00" },
      { "service_id": 4, "service_name": "Cuci Selimut", "unit_price": "25000.00", "quantity": "1.00", "subtotal": "25000.00" }
    ]
  }
}
```
422 contoh:
```json
{
  "success": false,
  "message": "Data yang dikirim tidak valid.",
  "errors": { "items.0.service_id": ["The selected items.0.service_id is invalid."] }
}
```

### GET `/orders` *(privat)*
Riwayat pesanan milik sendiri, terbaru dulu. Filter opsional: `?status=pending`.
Bentuk `data` berupa array objek pesanan (sama seperti di atas) beserta `meta`.

### GET `/orders/{id}` *(privat)*
Detail satu pesanan. 404 jika id tidak ada **atau milik pelanggan lain**.

### POST `/orders/{id}/cancel` *(privat)*
Tanpa body. Hanya bisa dari status `pending` atau `confirmed`, dan selama belum `paid`.

200: objek pesanan dengan `status: "cancelled"`.
422: `{ "success": false, "message": "Pesanan tidak dapat dibatalkan pada status ini." }`

Gunakan `can_cancel` pada objek pesanan untuk menampilkan atau menyembunyikan tombol Batalkan.

### GET `/orders/{id}/payment` *(privat)*
```json
{
  "success": true,
  "message": "Status pembayaran.",
  "data": {
    "order_number": "LL-261015-K3X9Q",
    "total": "42500.00",
    "payment_status": "unpaid",
    "payment_status_label": "Belum dibayar",
    "payments": []
  }
}
```
Integrasi pembayaran online (payment gateway) ditambahkan di Fase 6. Status pembayaran
**hanya** berubah lewat verifikasi server, bukan lewat aplikasi Android.

---

## 5. Contoh pengujian dengan curl (CMD Windows)

```cmd
curl -s -X POST http://localhost:8000/api/v1/login -H "Accept: application/json" -H "Content-Type: application/json" -d "{\"email\":\"budi@demo.test\",\"password\":\"password\"}"

curl -s http://localhost:8000/api/v1/me -H "Accept: application/json" -H "Authorization: Bearer TOKEN_DI_SINI"
```

## 6. Akun uji (data demo lokal)

`budi@demo.test`, `siti@demo.test`, `andi@demo.test` — password `password`. Hanya untuk lokal, jangan ada di production.
