# Dokumentasi GAP API

REST API backend untuk aplikasi produksi tekstil (dyeing, printing, inspecting, greige, packing/pengiriman, dan marketing). Dibangun dengan **Laravel 7** (PHP ^7.2.5) dan dikonsumsi oleh aplikasi klien (mobile/web).

## 1. Teknologi

| Komponen | Keterangan |
|---|---|
| Framework | Laravel 7 |
| Database | MySQL (default, `DB_CONNECTION=mysql`); driver `sqlsrv` dan `sqlite` juga tersedia di `config/database.php` |
| Autentikasi | Token kustom (JWT-like) yang disimpan di tabel `user` (`verification_token`, `token_expired`) |
| Paket | `firebase/php-jwt`, `tymon/jwt-auth`, `laravel/sanctum`, `fruitcake/laravel-cors`, `guzzlehttp/guzzle` |
| Notifikasi | Menyimpan `fcm_token` per user (Firebase Cloud Messaging) |

## 2. Instalasi

```bash
composer install
cp .env.example .env        # lalu isi DB_*, JWT_SECRET
php artisan key:generate
php artisan serve           # http://localhost:8000
```

Variabel `.env` penting:

- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `JWT_SECRET` — secret untuk menandatangani token login

Skema tabel produksi (user, WO, MO, SC, kartu proses, inspecting, dll.) berasal dari database eksisting; migrasi di repo ini hanya mencakup tabel bawaan Laravel dan penambahan kolom `no_urut` pada `inspecting_items`.

## 3. Struktur Proyek

```
api-app/
├── app/
│   ├── Http/Controllers/   # logika endpoint (lihat bagian 5)
│   ├── Http/Middleware/    # TokenAuthMiddleware, Authenticate, dll.
│   ├── Helpers/Converter.php
│   └── *.php               # model Eloquent (Wo, Mo, Sc, Inspecting, KartuProsesDyeing, ...)
├── routes/api.php          # seluruh route API (prefix /api/v1)
├── config/                 # konfigurasi (database, auth, jwt, cors, ...)
└── database/migrations/
```

### Model utama

| Kelompok | Model (tabel) |
|---|---|
| Pengguna | `User`, `UserModel` (`user`) |
| Order | `Sc`, `ScGreige`, `ScAgen`, `ScKomisi`, `Mo`, `MoColor`, `MoMemo`, `Wo`, `WoColor`, `WoMemo` |
| Proses | `KartuProsesDyeing(+Item)`, `KartuProsesPrinting(+Item)`, `ProcessDyeing`, `ProcessPrinting`, `KartuProcessDyeingProcess`, `KartuProcessPrintingProcess`, `ActionLogKartuDyeing` |
| Inspecting | `Inspecting` (`trn_inspecting`), `InspectingItem`, `InspectingMklbj`, `InspectingMklbjItem`, `DefectInspectingItem`, `MstKodeDefect` |
| Greige | `Greige`, `GreigeGroup`, `StockGreige`, `StockGreigeOpname`, `StockGreigeDaily`, `TrnStockGreigeDaily` |
| Pengiriman | `KirimBuyer`, `KirimBuyerHeader`, `KirimBuyerItem`, `KirimBuyerBal`, `GudangJadi`, `Handling` |
| Master | `MstCustomer`, `MstVendor`, `MstBankAccount`, `MstK3l`, `MstPapperTube` |

Istilah: **SC** = Sales Contract, **MO** = Marketing Order, **WO** = Work Order, **MKL/BJ** = jenis order makloon/barang jadi (varian `Mklbj`).

## 4. Autentikasi

Base URL: `/api/v1`. Semua endpoint kecuali `auth/login` berada dalam group middleware `api`.

1. `POST /api/v1/auth/login` dengan body JSON:
   ```json
   { "username": "user", "password": "rahasia", "force_login": false }
   ```
   - Password diverifikasi dengan `password_verify` terhadap `password_hash`.
   - Jika user masih punya token aktif (belum kedaluwarsa) di perangkat lain, login ditolak kecuali `force_login: true`.
   - Token berlaku **7 hari**, disimpan di `user.verification_token`.
   - Respons sukses: `{ "success": true, "message": "Login Success!", "data": {user}, "token": "..." }`
2. Kirim token pada header berikutnya: `Authorization: Bearer <token>`.

Catatan: login mengembalikan HTTP 200 meski gagal; cek field `success`. Validasi gagal pada endpoint lain mengembalikan 422, data tidak ditemukan 404.

### Endpoint Auth (`/auth`)

| Method | Path | Keterangan |
|---|---|---|
| POST | `/auth/login` | Login (lihat di atas) |
| POST | `/auth/logout` | Logout |
| POST | `/auth/update-fcm-token` | Simpan `fcm_token` perangkat (body JSON) |
| POST | `/auth/role-user` | Ambil role user |
| POST | `/auth/change-password` | Body: `old_password`, `new_password`, `new_password_confirmation` (min 6) |
| GET | `/auth/profile` | Profil user |

## 5. Endpoint

Semua path di bawah relatif terhadap `/api/v1`. Parameter GET dikirim sebagai query string.

### 5.1 Dashboard (`/dashboard`)

| Method | Path | Keterangan |
|---|---|---|
| GET | `/dashboard/index` | Data ringkasan dashboard |
| GET | `/dashboard/profile` | Profil user |
| GET | `/dashboard/grafik` | Data grafik |
| GET | `/dashboard/get-kartu-dyeing` | Cari kartu proses dyeing (`id`, `no`) |
| GET | `/dashboard/get-kartu-printing` | Cari kartu proses printing (`no`) |

### 5.2 Master Data

| Method | Path | Keterangan |
|---|---|---|
| GET | `/master-defect/get-master-defect` | Daftar kode defect |
| GET | `/customer/get-customer` | Daftar customer |

### 5.3 Work Order (`/wo`)

| Method | Path | Keterangan |
|---|---|---|
| GET | `/wo/get-wo` | Mengambil daftar 300 WO aktif terbaru (`status = 5`, proses dyeing `process = 1`) beserta relasi SC, MO, Greige, Customer, dan WO Color |
| GET | `/wo/search-wo?no=` | Cari WO berdasarkan nomor WO (`no`) untuk order dyeing & printing (`process` in 1, 2; `jenis_order` in 1, 2, 3; `status = 5`, limit 100). Mengembalikan detail SC Greige dan varian `mo_colors` (`wo_color_id`, `mo_color_id`, `color`) |
| PUT | `/wo/ganti-wo/{id}` | Mengganti WO, MO, SC, dan varian warna pada kartu proses dyeing atau printing. `{id}` adalah ID kartu proses. Body JSON: `wo_id`*, `mo_id`*, `sc_id`*, `sc_greige_id`*, `wo_color_id`*, `kartu_proses_id`*, `jenis_process` (`'dyeing'` atau `'printing'`, default: `'dyeing'`) |

### 5.4 Kartu Proses

| Method | Path | Parameter |
|---|---|---|
| GET | `/kartu-proses-dyeing/get-kartu-proses-dyeing/{id}` | — |
| GET | `/kartu-proses-dyeing/rekap-dyeing` | filter rekap* |
| GET | `/kartu-proses-printing/get-kartu-proses-printing/{id}` | — |
| GET | `/kartu-proses-printing/rekap-printing` | filter rekap* |

\*Filter rekap: `start_date`, `end_date`, `no_kartu`, `no_do`, `motif`, `limit`/`per_page`, `page`, `month`/`bulan`/`month_wo`/`bulan_wo`, `year`/`tahun`/`year_wo`/`tahun_wo`.
Alias `rekap-dyeing` dan `rekap-printing` juga tersedia di bawah `/defect-item`.

### 5.5 Inspecting (`/inspecting`)

Pembuatan data:

| Method | Path | Keterangan |
|---|---|---|
| POST | `/inspecting/store-inspecting` | Buat inspecting dyeing dari kartu. Body: `id_kartu`*, `no_lot`*, `unit`*, `jenis_inspek`*, `inspect_result`* (array), `no_memo`, `note`, `inspection_table` |
| POST | `/inspecting/store-mkl-bj-inspecting` | Buat inspecting MKL/BJ. Body: `wo_id`*, `color`* (`wo_color_id`), `no_lot`*, `unit`*, `jenis_inspek`*, `jenis_makloon`*, `inspect_result`* (array), `no_memo`, `note`, `inspection_table` |
| POST | `/inspecting/store-printing-inspecting` | Buat inspecting printing dari kartu. Body: `id_kartu`*, `no_lot`*, `unit`*, `jenis_inspek`*, `inspect_result`* (array), `no_memo`, `inspection_table` |
| POST | `/inspecting/add-inspect-result` | Tambah item inspecting. Body: `inspecting_id`*, `qty`*, `grade`*, `join_piece`, `lot_no`, `stock_id`, `qty_bit`, `gsm_item`, `defect` (array: `mst_kode_defect_id`, ...) |
| POST | `/inspecting/add-inspect-mklbj-result` | Tambah item inspecting MKL/BJ. Body: `inspecting_id`*, `qty`*, `grade`*, `join_piece`, `lot_no`, `gsm_item`, `qty_bit`, `defect` (array: `mst_kode_defect_id`, `meterage`, `point`) |

(* = wajib.) `no_urut` item dihitung otomatis; item grade 5 (sample) tidak mendapat nomor urut.

Baca / ubah / hapus:

| Method | Path | Keterangan |
|---|---|---|
| GET | `/inspecting/search-inspecting?jenis_process=&search=` | Cari data inspecting (`jenis_process`: `dyeing`, `printing`, atau `mkl-bj`; `search`: ID, nomor WO, atau lot) |
| GET | `/inspecting/get-inspecting/{id}` | Detail inspecting kartu (dyeing / printing) beserta items dan defect |
| GET | `/inspecting/get-inspecting-mkl-bj/{id}` | Detail inspecting MKL/BJ beserta items dan defect |
| PUT | `/inspecting/get-inspecting/update/{id}` | Ubah data item inspecting (`InspectingItem`). Body: `qty`*, `grade`*, `join_piece`, `defect` (array), `stock_id`, `qty_bit`, `lot_no`, `gsm_item` |
| PUT | `/inspecting/kalkukasi/{id}` | Hitung ulang grade item berdasarkan nilai poin defect. Body JSON array: `[{"nilai_poin": 20, "id": [{"inspecting_item_id": 1}]}]` |
| PUT | `/inspecting/kalkukasi/mkl-bj/{id}` | Kalkulasi ulang data inspecting MKL/BJ |
| PUT | `/inspecting/update-inspecting/{id}` | Ubah header inspecting dyeing/printing (`wo_id`*, `no_lot`*, `unit`*, `warna`*, `jenis_inspek`, `no_memo`, `mo_id`, `sc_id`, `sc_greige_id`, `note`, `inspection_table`, `kartu_proses_dyeing_id`, `kartu_proses_printing_id`) |
| PUT | `/inspecting/update-inspecting-mklbj/{id}` | Ubah header inspecting MKL/BJ (`wo_id`*, `mo_color_id`*, `no_lot`*, `unit`*, `jenis_makloon`*, `jenis_inspek`, `no_memo`, `note`, `inspection_table`, `inspect_result`) |
| PUT | `/inspecting/update-inspecting-mklbj/item/{id}` | Ubah item inspecting MKL/BJ (`InspectingMklbjItem`). Body: `qty`*, `grade`, `join_piece`, `lot_no`, `gsm_item`, `qty_bit`, `defect` (array: `id`, `mst_kode_defect_id`, `meterage`, `point`) |
| DELETE | `/inspecting/delete-inspecting-item/{id}` | Hapus satu item inspecting (`InspectingItem`) beserta data defect terkait |
| DELETE | `/inspecting/delete-inspecting-item-mklbj/{id}` | Hapus satu item inspecting MKL/BJ (`InspectingMklbjItem`) |
| DELETE | `/inspecting/delete-all-defects/{id}` | Hapus semua defect pada satu inspecting header |
| DELETE | `/inspecting/delete-all-defects-mklbj/{id}` | Hapus semua defect pada satu inspecting MKL/BJ header |

*Catatan: Route `PUT /inspecting/inspecting-item/update/{id}` yang mengarah ke `DashboardController@updateInspectingItem` tidak aktif/belum diimplementasikan pada controller; gunakan `/inspecting/get-inspecting/update/{id}` untuk mengubah data item inspecting.*

Pembuatan inspecting juga mencatat log aktivitas kartu (`ActionLogKartuDyeing`).

### 5.6 Defect Item (`/defect-item`)

| Method | Path | Parameter |
|---|---|---|
| GET | `/defect-item/get-defect-item` | `tahun` — rekap jumlah per no urut |
| GET | `/defect-item/get-defect-tgl-kirim` | `start_date`, `end_date` |
| GET | `/defect-item/get-defect-meter-tgl-kirim` | `start_date`, `end_date` |
| GET | `/defect-item/get-defect-meter-tgl-kirim-printing` | `start_date`, `end_date` |

### 5.7 Greige & Stok Harian

`/greige`

| Method | Path | Parameter |
|---|---|---|
| GET | `/greige/rekap-stock-greige` | `start_date`, `end_date`, `kondisi_greige`, `lot_lusi`, `lot_pakan`, `motif`, `asal_greige` |
| GET | `/greige/rekap-stock-greige-opname` | sama |
| POST | `/greige/save-daily-stock` | `date` |
| GET | `/greige/get-daily-stock` | `date`, `start_date`, `end_date`, `greige_id`, `greige_group_id`, `motif` |

`/stock-greige-daily`

| Method | Path | Keterangan |
|---|---|---|
| GET | `/stock-greige-daily/index` | `date`, `start_date`, `end_date`, `greige_id`, `greige_group_id`, `asal_greige`, `motif`, `per_page`, `only_incoming`, `auto_snapshot`, `all` |
| GET | `/stock-greige-daily/matrix` | Matriks stok per tanggal: `startDate`, `endDate` + filter greige |
| POST | `/stock-greige-daily/save-snapshot` | Simpan snapshot stok (`date`) |
| GET | `/stock-greige-daily/export-excel` | Unduh CSV (UTF-8 BOM, dibuka di Excel) dengan filter yang sama |
| GET | `/stock-greige-daily/detail/{id}` | Detail snapshot |
| GET | `/stock-greige-daily/incoming-summary` | Ringkasan greige masuk |
| DELETE | `/stock-greige-daily/delete/{id}` | Hapus snapshot |

### 5.8 Packing / Pengiriman (`/verpacking`)

Filter umum: `no_kirim`, `buyer`, `design`, `start_date`, `end_date`, `no_lot`, `kombinasi`, `jenis_order`, `no_wo`, `motif`, `stamping`, `piece_length`.

| Method | Path | Parameter |
|---|---|---|
| GET | `/verpacking/daftar-pengiriman-produksi` | filter umum |
| GET | `/verpacking/daftar-pengiriman-produksi-mklbj` | filter umum |
| GET | `/verpacking/analisa-pengiriman-produksi` | filter umum |
| GET | `/verpacking/analisa-pengiriman-produksi-mklbj` | filter umum |
| GET | `/verpacking/rekap-pengiriman-produksi` | `start_date`, `end_date` |
| GET | `/verpacking/rekap-pengiriman-harian` | `start_date`, `end_date` |
| GET | `/verpacking/rekap-pengiriman-produksi-bulan` | `month`, `year` |

### 5.9 Marketing (`/marketing`)

| Method | Path | Parameter & Keterangan |
|---|---|---|
| GET | `/marketing/get-marketing` | Mengambil daftar staf marketing aktif (`status = 10` pada `trn_sc`). Response: `[{"id": 1, "name": "Nama Marketing"}]` diurutkan berdasarkan nama. |
| GET | `/marketing/outstanding` | Laporan outstanding order dyeing (`process = 1`).<br>**Query Parameters:**<br>- `start_date`* (date, `YYYY-MM-DD`): Tanggal awal SC<br>- `end_date`* (date, `YYYY-MM-DD`): Tanggal akhir SC<br>- `customer_id` (numeric) — *Wajib jika `marketing_id` kosong*<br>- `marketing_id` (numeric) — *Wajib jika `customer_id` kosong*<br>- `po_no` (string, optional): Filter nomor PO<br>- `no_wo` (string, optional): Filter nomor WO<br>- `wo_greige` (string, optional): Filter nama kain greige<br>- `is_sisaan` (boolean / `1`, optional): Jika aktif, hanya menampilkan MO status 3 (in-progress/sisaan). Jika tidak diisi atau filter nomor WO/PO aktif, menampilkan status 3 dan 5 (closed).<br>**Data Output:** Memuat data SC beserta MO, WO, detail warna, akumulasi hasil inspect per grade (A, A+, A*, B, C, PK, Sample) dari inspecting kartu dan MKL/BJ, total pengiriman buyer, retur buyer (menambah sisa), mutasi ex-finish, dan sisa outstanding order. |
| GET | `/marketing/outstanding-printing` | Laporan outstanding order printing (`process = 2`). Parameter query dan struktur output sama persis dengan endpoint `outstanding`, khusus untuk proses printing. |
| GET | `/marketing/follow-sc` | Follow-up Sales Contract (status 5) yang belum memiliki MO aktif (status 3) atau belum memiliki MO Color / WO Color.<br>**Query Parameters:**<br>- `customer_id`* (numeric)<br>- `start_date`* (date, `YYYY-MM-DD`)<br>- `end_date`* (date, `YYYY-MM-DD`)<br>- `marketing_id` (numeric, optional) |
| GET | `/marketing/kirim-buyer` | Data rincian pengiriman ke buyer dan retur buyer per MO.<br>**Query Parameters:** `customer_id`, `start_date`, `end_date`, `po_no`, `no_wo`, `wo_greige`, `sc_no` |
| GET | `/marketing/sales-contract` | Daftar Sales Contract beserta ringkasan agregat (total meter, total yard, total amount).<br>**Query Parameters:**<br>- `tipe_kontrak` (`1` = Ekspor, `2` = Lokal)<br>- `start_date`, `end_date` (date, `YYYY-MM-DD`)<br>- `customer_id` (numeric)<br>- `marketing_id` (numeric)<br>- `status` (numeric: status SC)<br>- `sc_no`, `po_no`, `search` (string) |
| POST | `/marketing/close-mo` | Close manual status Marketing Order (status MO diubah menjadi 5 / Closed).<br>**Header:** `Authorization: Bearer <token>`<br>**Body (JSON/Form):** `mo_id`* (numeric), `note` (string, optional - default: `'Close manual, Qty Sudah terpenuhi'`)<br>**Otorisasi:** Hanya staf marketing penanggung jawab SC terkait atau user dengan role `Developer`, `Super Admin`, `Direktur Marketing`, `Manager Marketing`. |
| POST | `/marketing/reopen-mo` | Buka kembali status Marketing Order yang sebelumnya di-close (status MO diubah kembali menjadi 3 / Approved).<br>**Header:** `Authorization: Bearer <token>`<br>**Body (JSON/Form):** `mo_id`* (numeric)<br>**Otorisasi:** Sama dengan `close-mo`. |

## 6. Format Respons

Umumnya:

```json
{ "success": true, "message": "...", "data": { } }
```

Error validasi (422):

```json
{ "success": false, "message": "Validasi data gagal", "errors": { "field": ["..."] } }
```

## 7. Catatan untuk Pengembang

- Route memakai sintaks string controller (`'AuthController@login'`), sesuai Laravel 7.
- Group `middleware => 'api'` hanya menerapkan middleware grup `api` (throttle/bindings); pengecekan token dilakukan manual di sebagian controller (`User::where('verification_token', $token)`). `TokenAuthMiddleware` ada tetapi tidak dipasang pada route di `routes/api.php` dan memakai kolom `fcm_token`, bukan `verification_token`. Pertimbangkan menyatukan pengecekan token dalam satu middleware yang dipasang di semua route terproteksi.
- Controller berukuran besar (Dashboard, Inspecting, Marketing >1300 baris); logika bisnis layak dipindah ke service class.
- Token dibuat dengan `base64` + `hash_hmac` manual, bukan JWT standar (signature dihitung dari string literal `header.payload`, bukan dari header/payload sebenarnya). Pertimbangkan memakai `firebase/php-jwt` yang sudah terpasang.
