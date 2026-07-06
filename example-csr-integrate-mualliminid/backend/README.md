# Backend — Example CSR Integrate MualliminID

Laravel 13 sebagai **Stateless REST API** untuk menerima, memverifikasi, dan melayani data pengguna yang terautentikasi via SSO MualliminID.

> Backend ini **tidak memiliki halaman HTML**, **tidak menggunakan session PHP** untuk autentikasi, dan **tidak memerlukan cookie session**. Setiap request diautentikasi murni melalui **JWT Bearer Token** pada header `Authorization`.

---

## Teknologi

| Komponen | Detail |
| :--- | :--- |
| Framework | Laravel 13 |
| PHP | >= 8.3 |
| Database | SQLite (untuk contoh ini) |
| Autentikasi | Custom Auth Guard via JWT (`openssl_verify` offline) |
| HTTP Client | Laravel `Http` Facade (untuk komunikasi ke SSO API) |

---

## Struktur Direktori Kunci

```
backend/
├── app/
│   ├── Http/Controllers/Api/
│   │   ├── AuthController.php     ← GET /auth/me, POST /auth/logout
│   │   └── UserController.php     ← GET /users, POST /users/sync
│   ├── Models/
│   │   └── User.php               ← Model Eloquent pengguna
│   └── Providers/
│       └── AppServiceProvider.php ← Registrasi custom auth guard "sso-token"
├── config/
│   ├── auth.php                   ← Konfigurasi guard default → sso-token
│   └── services.php               ← Mapping seluruh konfigurasi SSO dari .env
├── database/
│   └── migrations/                ← Schema tabel users dengan kolom sso_id
├── keys/
│   └── public.pem                 ← Kunci publik SSO (TIDAK ter-commit ke Git)
└── routes/
    └── api.php                    ← Definisi seluruh endpoint API
```

---

## Cara Setup

### 1. Install Dependensi

```bash
composer install
```

### 2. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` dan isi variabel SSO:

```env
APP_URL=http://localhost:3011
SERVER_PORT=3011

SSO_API_URL=http://localhost:3009/api
SSO_CLIENT_ID=app_xxxxxxxxxxxxxxxx
SSO_CLIENT_SECRET=secret_xxxxxxxxxxxxxxxx
SSO_PUBLIC_KEY_PATH=keys/public.pem
```

| Variabel | Keterangan | Digunakan di |
| :--- | :--- | :--- |
| `SSO_API_URL` | Base URL API server MualliminID SSO | Guard (userinfo), UserController (sync) |
| `SSO_CLIENT_ID` | Client ID aplikasi yang terdaftar di SSO | UserController (sync — path parameter) |
| `SSO_CLIENT_SECRET` | Secret untuk mengakses endpoint daftar pengguna SSO | UserController (sync — header `X-Client-Secret`) |
| `SSO_PUBLIC_KEY_PATH` | Path ke `public.pem` relatif dari root project | Guard (verifikasi JWT signature secara offline) |

### 3. Setup Database & Kunci Publik

```bash
php artisan migrate
```

Letakkan file `public.pem` dari server SSO ke `backend/keys/`:

```bash
mkdir keys
# Salin public.pem dari server SSO ke backend/keys/public.pem
```

### 4. Jalankan Server

```bash
php artisan serve --port=3011
```

---

## Arsitektur Autentikasi — Custom Auth Guard `sso-token`

File: [`app/Providers/AppServiceProvider.php`](app/Providers/AppServiceProvider.php)

Guard ini didaftarkan via `Auth::viaRequest('sso-token', ...)` dan dieksekusi oleh Laravel **setiap kali ada request ke endpoint yang dilindungi** middleware `auth`. Tidak ada library JWT eksternal — verifikasi dilakukan langsung dengan fungsi PHP native `openssl_verify`.

### Alur Verifikasi Token

```
Request → header Authorization: Bearer {jwt_token}

┌─ TAHAP 1: Format Check ───────────────────────────────────────┐
│ $token = $request->bearerToken()                               │
│ Jika kosong → return null → 401 Unauthenticated               │
│                                                                │
│ $parts = explode('.', $token)  → [header, payload, signature] │
│ Jika count($parts) !== 3 → return null                        │
└────────────────────────────────────────────────────────────────┘
              ↓
┌─ TAHAP 2: Verifikasi Signature Kriptografis ──────────────────┐
│ Baca public.pem dari: config('services.sso.public_key_path')  │
│ Jika file tidak ada → return null                             │
│                                                                │
│ $data      = $parts[0] . '.' . $parts[1]  (header.payload)   │
│ $signature = base64url_decode($parts[2])                      │
│                                                                │
│ openssl_verify($data, $signature, $publicKey, SHA256)         │
│   = 1  → VALID (token asli dari SSO Server)                   │
│   ≠ 1  → return null (token palsu / dimanipulasi)             │
└────────────────────────────────────────────────────────────────┘
              ↓
┌─ TAHAP 3: Decode Payload & Validasi Klaim ────────────────────┐
│ $payload = json_decode(base64url_decode($parts[1]))            │
│                                                                │
│ $ssoId = $payload['userId'] ?? $payload['sub']                │
│ $exp   = $payload['exp']    (Unix timestamp)                   │
│                                                                │
│ Jika $ssoId kosong    → return null                           │
│ Jika time() >= $exp   → return null (token kedaluwarsa)       │
└────────────────────────────────────────────────────────────────┘
              ↓
┌─ TAHAP 4: Lookup User di Database Lokal ──────────────────────┐
│ User::where('sso_id', $ssoId)->first()                        │
│                                                                │
│ DITEMUKAN → return $user ✓  (selesai, tanpa request ke SSO)   │
│ TIDAK ADA → lanjut Tahap 5                                     │
└────────────────────────────────────────────────────────────────┘
              ↓
┌─ TAHAP 5: Auto-Provisioning (User Baru Pertama Login) ────────┐
│ Hit SSO API → GET /auth/sso/userinfo                          │
│ (lihat detail di bagian bawah)                                 │
│                                                                │
│ Hasilnya: create atau update user di DB lokal → return $user ✓│
└────────────────────────────────────────────────────────────────┘
```

### Mengapa Verifikasi Dilakukan Offline?

Tanpa `public.pem`, setiap request harus meminta verifikasi token ke SSO Server — artinya satu request pengguna menghasilkan satu request jaringan ekstra. Dengan verifikasi offline:

- Selesai dalam **hitungan mikrodetik** langsung di memori server
- Aplikasi **tetap berfungsi** meski SSO Server sedang down
- Satu-satunya saat bergantung ke SSO adalah Tahap 5 (auto-provisioning user baru)

### Mengapa `role` Diambil dari Payload JWT?

Payload JWT telah **ditandatangani kriptografis** oleh SSO Server — artinya klaim `role` di dalamnya tidak bisa dimanipulasi oleh siapapun tanpa membuat signature tidak valid. Ini menjamin integritas data peran tanpa perlu request tambahan ke SSO.

---

## Komunikasi Backend ke SSO Server

Backend memanggil SSO Server di dua titik. Keduanya dipanggil oleh backend secara aktif menggunakan Laravel `Http` Facade.

---

### 1. `GET /auth/sso/userinfo` — Saat User Pertama Kali Login

**Dipanggil dari:** Guard `sso-token` di `AppServiceProvider.php`, ketika token valid tapi `sso_id`-nya belum ada di database lokal.

**Kapan ini terjadi?**
Setiap kali user login via SSO, guard memverifikasi JWT-nya. Jika `sso_id` dari JWT belum ada di tabel `users` lokal (artinya user ini belum pernah login ke aplikasi ini sebelumnya), backend perlu mengambil data profil lengkapnya dari SSO untuk membuat atau memperbarui record lokal.

**Request yang Dikirim Backend ke SSO:**

```http
GET {SSO_API_URL}/auth/sso/userinfo
Authorization: Bearer {access_token_milik_user}
```

Backend menggunakan token yang **sama persis** yang dikirim oleh user ke backend. Token itu dipakai ulang untuk bertanya ke SSO: "siapa pemilik token ini dan apa data profilnya?". SSO memverifikasi token tersebut, lalu mengembalikan profil pemiliknya.

**Response yang Diterima dari SSO:**

```json
{
  "status": "success",
  "message": "User info berhasil didapatkan",
  "data": {
    "sub": "uuid-user-di-sso",
    "userId": "uuid-user-di-sso",
    "email": "ahmad.fauzi@muallimin.sch.id",
    "email_verified": true,
    "name": "Ahmad Fauzi",
    "first_name": "Ahmad",
    "last_name": "Fauzi",
    "picture": "https://sso.muallimin.sch.id/uploads/profiles/foto.jpg",
    "whatsapp_number": "081234567890",
    "nbm": "20240001",
    "role": "GURU",
    "isDeveloper": false,
    "status": "ACTIVE"
  }
}
```

**Yang Dilakukan Backend dengan Response Ini:**

Backend memeriksa apakah `email` dari response sudah ada di database lokal atau belum:

- **Email sudah ada** → record lama diupdate: `sso_id` diikat, `name`, `nbm`, `whatsapp_number` diperbarui
- **Email belum ada** → record baru dibuat dengan semua data dari response

```php
$ssoUser = $response->json()['data'] ?? [];
$email   = $ssoUser['email'] ?? null;

$userData = [
    'sso_id'          => $ssoId,                         // dari JWT payload — sudah terverifikasi
    'name'            => $ssoUser['name'] ?? explode('@', $email)[0],
    'nbm'             => $ssoUser['nbm'] ?? null,
    'whatsapp_number' => $ssoUser['whatsapp_number'] ?? null,
    'role'            => $payload['role'] ?? null,        // dari JWT payload — sudah terverifikasi
];

$user = User::where('email', $email)->first();

if ($user) {
    $user->update($userData);
} else {
    $userData['email'] = $email;
    $user = User::create($userData);
}
```

> `sso_id` dan `role` sengaja diambil dari **JWT payload** (bukan dari field `role`/`userId` di response userinfo), karena JWT sudah ditandatangani kriptografis oleh SSO — nilainya tidak bisa dimanipulasi. Field lain diambil dari userinfo karena tidak tersedia di payload JWT.

---

### 2. `GET /client-apps/by-client-id/{CLIENT_ID}/users` — Saat Sync Massal

**Dipanggil dari:** `UserController::sync()` di `UserController.php`, ketika admin menekan tombol "Sync Pengguna" di Dashboard.

**Kapan ini terjadi?**
Endpoint ini bukan dipanggil otomatis — hanya berjalan saat admin secara eksplisit memicu sync. Tujuannya mengambil seluruh daftar pengguna yang terdaftar di aplikasi ini pada SSO, lalu menyesuaikan data di database lokal agar selaras.

**Request yang Dikirim Backend ke SSO:**

```http
GET {SSO_API_URL}/client-apps/by-client-id/{SSO_CLIENT_ID}/users?page=1&limit=100
X-Client-Secret: {SSO_CLIENT_SECRET}
```

Tidak menggunakan Bearer Token karena ini adalah komunikasi antar-server — tidak ada user yang sedang login. Sebagai gantinya, `X-Client-Secret` digunakan sebagai bukti identitas aplikasi. SSO Server memverifikasi secret ini dengan membandingkannya ke hash yang tersimpan di databasenya menggunakan bcrypt.

Karena SSO membatasi 100 data per response, backend memanggil endpoint ini berulang kali dengan menaikkan `page` sampai semua halaman habis.

**Response yang Diterima dari SSO (per halaman):**

```json
{
  "status": "success",
  "message": "Daftar user aplikasi berhasil didapatkan",
  "data": [
    {
      "userId": "uuid-user-1",
      "email": "ahmad.fauzi@muallimin.sch.id",
      "name": "Ahmad Fauzi",
      "first_name": "Ahmad",
      "last_name": "Fauzi",
      "whatsapp_number": "081234567890",
      "nbm": "20240001",
      "role": "GURU",
      "status": "ACTIVE"
    }
  ],
  "meta": {
    "total": 285,
    "page": 1,
    "limit": 100,
    "totalPages": 3
  }
}
```

**Yang Dilakukan Backend dengan Response Ini:**

Untuk setiap user dalam `data`, backend melakukan `updateOrCreate` — artinya jika user sudah ada di lokal (dicari berdasarkan `sso_id`) maka datanya diupdate, jika belum ada maka dibuat baru. Semua `sso_id` yang berhasil diproses dikumpulkan, lalu di akhir loop, user lokal yang ber-`sso_id` tapi tidak ada dalam daftar SSO akan dihapus — kecuali yang memiliki `role = ADMIN`.

```php
do {
    $response = Http::withHeaders(['X-Client-Secret' => config('services.sso.client_secret')])
        ->get(config('services.sso.api_url') . '/client-apps/by-client-id/' . config('services.sso.client_id') . '/users', [
            'page'  => $page,
            'limit' => $limit,
        ]);

    $json       = $response->json();
    $ssoUsers   = $json['data'] ?? [];
    $totalPages = $json['meta']['totalPages'] ?? 1;

    foreach ($ssoUsers as $ssoUser) {
        if (empty($ssoUser['userId'])) continue;

        User::updateOrCreate(
            ['sso_id' => $ssoUser['userId']],
            [
                'name'             => $ssoUser['name'] ?? explode('@', $ssoUser['email'])[0],
                'email'            => $ssoUser['email'],
                'nbm'              => $ssoUser['nbm'] ?? null,
                'whatsapp_number'  => $ssoUser['whatsapp_number'] ?? null,
                'role'             => $ssoUser['role'] ?? null,
            ]
        );
        $syncedSsoIds[] = $ssoUser['userId'];
    }

    $page++;
} while ($page <= $totalPages);

// Hapus user lokal yang sudah tidak terdaftar di SSO (kecuali ADMIN)
User::whereNotNull('sso_id')
    ->whereNotIn('sso_id', $syncedSsoIds)
    ->where('role', '!=', 'ADMIN')
    ->delete();
```

---

## API Endpoints (Backend Laravel)

Semua endpoint di prefix `/api`, dilindungi middleware `auth` (Bearer Token wajib):

```php
// routes/api.php
Route::middleware(['auth'])->group(function () {
    Route::get('/auth/me',      [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/users',        [UserController::class, 'index']);
    Route::post('/users/sync',  [UserController::class, 'sync']);
});
```

---

### `GET /api/auth/me`

**Tujuan:** Mendapatkan data profil pengguna yang sedang terautentikasi.

Guard memproses token → mengisi `Auth::user()` → controller langsung return hasilnya, tanpa query tambahan.

**Request:**
```http
GET /api/auth/me
Authorization: Bearer {access_token}
```

**Response Sukses (200):**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    "sso_id": "uuid-user-di-sso",
    "name": "Ahmad Fauzi",
    "email": "ahmad.fauzi@muallimin.sch.id",
    "nbm": "20240001",
    "whatsapp_number": "081234567890",
    "role": "GURU"
  }
}
```

**Response Gagal (401):**
```json
{ "message": "Unauthenticated." }
```

---

### `POST /api/auth/logout`

**Tujuan:** Sinyal ke backend bahwa sesi pengguna berakhir.

Backend CSR tidak menyimpan session atau refresh token — sehingga tidak ada state yang perlu dihapus. Invalidasi token yang sesungguhnya terjadi di SSO Server dan dilakukan langsung oleh frontend (bukan melalui backend).

**Request:**
```http
POST /api/auth/logout
Authorization: Bearer {access_token}
```

**Response Sukses (200):**
```json
{
  "status": "success",
  "message": "Logged out successfully"
}
```

---

### `GET /api/users`

**Tujuan:** Daftar pengguna di database lokal dengan statistik dan paginasi.

**Request:**
```http
GET /api/users?per_page=20
Authorization: Bearer {access_token}
```

**Query Parameters:**

| Parameter | Default | Nilai Valid |
| :--- | :--- | :--- |
| `per_page` | `10` | `10`, `20`, `60`, `80`, `100` |

**Response Sukses (200):**
```json
{
  "status": "success",
  "stats": {
    "total": 150,
    "synced": 148,
    "no_role": 3
  },
  "users": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Ahmad Fauzi",
        "email": "ahmad.fauzi@muallimin.sch.id",
        "role": "GURU",
        "nbm": "20240001",
        "whatsapp_number": "081234567890"
      }
    ],
    "last_page": 8,
    "per_page": 20,
    "total": 150
  }
}
```

**Keterangan `stats`:**

| Field | Keterangan |
| :--- | :--- |
| `total` | Total pengguna di DB lokal |
| `synced` | Pengguna yang memiliki `sso_id` (sudah ter-link ke akun SSO) |
| `no_role` | Pengguna dengan kolom `role = null` |

---

### `POST /api/users/sync`

**Tujuan:** Sinkronisasi massal seluruh pengguna dari SSO Server ke database lokal.

**Request:**
```http
POST /api/users/sync
Authorization: Bearer {access_token}
```

**Response Sukses (200):**
```json
{
  "status": "success",
  "message": "Sinkronisasi berhasil! 285 pengguna telah diselaraskan."
}
```

**Response Gagal — SSO tidak merespons (400):**
```json
{ "message": "Gagal sinkronisasi: Respon API SSO tidak sukses." }
```

**Response Gagal — Exception tak terduga (500):**
```json
{ "message": "Gagal melakukan sinkronisasi: {detail error}" }
```

---

## Model User

File: [`app/Models/User.php`](app/Models/User.php)

| Kolom | Tipe | Nullable | Unique | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| `id` | bigint | Tidak | Ya | Primary key lokal |
| `sso_id` | uuid | Ya | Ya | ID unik dari SSO Server (`userId` dari JWT/userinfo) — penghubung antara akun SSO dan akun lokal |
| `name` | string | Tidak | Tidak | Nama lengkap |
| `email` | string | Tidak | Ya | Email — kunci fallback saat user belum punya `sso_id` |
| `nbm` | string(20) | Ya | Tidak | Nomor Buku Murid |
| `whatsapp_number` | string(20) | Ya | Tidak | Nomor WhatsApp |
| `role` | string(50) | Ya | Tidak | Peran: `GURU`, `SISWA`, `ADMIN`, dll — diambil dari JWT payload |
| `password` | string | Ya | Tidak | Tidak digunakan untuk SSO login. Ada karena `Authenticatable` memerlukan kolom ini |

---

## Git Safety

File-file berikut **tidak ter-commit** karena sudah masuk `.gitignore`:

```
.env      ← Berisi SSO_CLIENT_SECRET, SSO_CLIENT_ID, dan key app
/keys/    ← Berisi public.pem (kunci kriptografi RSA)
*.log     ← Log runtime
```

> Jangan pernah commit `public.pem` atau `.env` ke repositori publik.
