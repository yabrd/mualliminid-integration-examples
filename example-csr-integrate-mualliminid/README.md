# Example CSR Integrate MualliminID

Contoh implementasi integrasi **Single Sign-On (SSO) MualliminID** menggunakan arsitektur **Client-Side Rendering (CSR)**.

Arsitektur CSR berarti:
- **Backend** berjalan sebagai **Stateless REST API** murni — tidak melayani HTML, tidak menggunakan session PHP untuk autentikasi.
- **Frontend** berjalan sebagai **Single Page Application (SPA)** terpisah — bertanggung jawab penuh atas alur autentikasi PKCE, penyimpanan token, refresh token otomatis, dan sinkronisasi logout antar tab.
- Setiap request dari frontend ke backend membawa **Bearer Token (JWT)** yang diverifikasi secara offline oleh backend menggunakan kunci publik RSA (`public.pem`).

---

## Struktur Direktori

```
example-csr-integrate-mualliminid/
├── backend/      ← Laravel 13 (Stateless REST API)
└── frontend/     ← Vue 3 + Vite (Single Page Application)
```

---

## Teknologi

| Bagian | Stack |
| :--- | :--- |
| Backend | Laravel 13, PHP >= 8.3, SQLite |
| Frontend | Vue 3, Vite, Pinia, Axios, Tailwind CSS v4, Vue Router 4 |
| Autentikasi | OAuth 2.0 Authorization Code + PKCE, JWT RS256 |

---

## Alur Autentikasi SSO (PKCE Flow)

### 1. Login — Inisiasi PKCE

User klik tombol login di frontend. Frontend menghasilkan PKCE dan redirect browser ke SSO Server:

```
Frontend:
  generateCodeVerifier()   → 112 karakter hex acak
  generateCodeChallenge()  → SHA-256(verifier) → Base64URL

sessionStorage:
  sso_code_verifier = verifier   ← disimpan untuk dipakai saat callback
  sso_oauth_state   = random     ← disimpan untuk validasi anti-CSRF

Redirect browser ke:
GET {SSO_API_URL}/auth/sso/authorize
  ?client_id=...
  &redirect_uri=http://localhost:5011/callback
  &response_type=code
  &scope=openid profile email
  &state={random}
  &code_challenge={challenge}
  &code_challenge_method=S256
```

### 2. Callback — Tukar Code → Token

Setelah user login di SSO, browser diarahkan ke `/callback?code=...&state=...`. Frontend memvalidasi state, lalu menukar authorization code ke access token:

```
Validasi state:
  savedState = sessionStorage.getItem('sso_oauth_state')
  sessionStorage.removeItem('sso_oauth_state')
  Jika state URL ≠ savedState → error (kemungkinan CSRF attack)

Tukar code → token:

  POST {SSO_API_URL}/auth/sso/token
  {
    grant_type:    "authorization_code",
    code:          "{dari URL}",
    client_id:     "{CLIENT_ID}",
    redirect_uri:  "http://localhost:5011/callback",
    code_verifier: "{dari sessionStorage}"
  }

SSO Server memverifikasi SHA-256(code_verifier) == code_challenge.
Jika cocok, token diterbitkan.

Response dari SSO:
  access_token  → disimpan ke localStorage
  id_token      → disimpan ke localStorage (dipakai saat logout)
  refresh_token → disimpan SSO sebagai httpOnly cookie (tidak di JS)
```

### 3. Hydrate — Verifikasi ke Backend

Setelah token tersimpan, frontend request ke backend untuk memverifikasi token dan mendapatkan data profil:

```
GET /api/auth/me
Authorization: Bearer {access_token}

Backend:
  Verifikasi JWT signature secara offline menggunakan public.pem (openssl_verify)
  Decode payload → ambil sso_id dan exp
  Jika exp sudah lewat → tolak
  Cari user di DB lokal berdasarkan sso_id

  Jika DITEMUKAN → return data user
  Jika TIDAK ADA → hit SSO /auth/sso/userinfo → ambil profil → create/update user lokal → return

Response:
  { id, sso_id, name, email, nbm, whatsapp_number, role }

Frontend: simpan ke authStore.user → masuk Dashboard
```

### 4. Dashboard — Akses Data

Setelah login, frontend mengakses data dari backend. Token dikirim otomatis oleh Axios interceptor:

```
GET /api/users?page=1&per_page=10
Authorization: Bearer {access_token}
← { stats: { total, synced, no_role }, users: { data: [...], current_page, ... } }

POST /api/users/sync
Authorization: Bearer {access_token}
← { message: "Sinkronisasi berhasil! N pengguna telah diselaraskan." }
  Backend menarik daftar pengguna dari SSO dan menyesuaikan DB lokal.
```

### 5. Refresh Token Otomatis

Jika backend mengembalikan `401`, Axios interceptor mencoba refresh otomatis tanpa user perlu login ulang:

```
POST {SSO_API_URL}/auth/sso/token
Cookie: {CLIENT_ID}_refresh_token=...  ← httpOnly cookie dikirim otomatis
{ grant_type: "refresh_token", client_id: "..." }

← Terima access_token baru → simpan ke localStorage → ulangi request yang gagal

Jika refresh gagal → hapus token → redirect ke halaman Login
```

### 6. Logout

```
Frontend:
  1. POST {SSO_API_URL}/auth/sso/end_session
     Authorization: Bearer {access_token}
     { id_token_hint: "{id_token}", client_id: "..." }
     ← SSO invalidasi session dan refresh token cookie di server

  2. Hapus access_token dan id_token dari localStorage
  3. POST /api/auth/logout (sinyal ke backend, diabaikan)
  4. BroadcastChannel → semua tab lain ikut logout otomatis
```

---

## Prasyarat

- **PHP** >= 8.3
- **Composer** >= 2
- **Node.js** >= 20
- **npm** >= 10
- Akses ke server **MualliminID SSO** — API URL, Client ID, Client Secret
- File **`public.pem`** — kunci publik RSA dari SSO Server untuk verifikasi JWT offline

---

## Cara Menjalankan

### Langkah 1 — Setup Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Isi variabel SSO di `.env`:

```env
APP_URL=http://localhost:3011
SSO_API_URL=http://localhost:3009/api
SSO_CLIENT_ID=app_xxxxxxxxxxxxxxxx
SSO_CLIENT_SECRET=secret_xxxxxxxxxxxxxxxx
SSO_PUBLIC_KEY_PATH=keys/public.pem
```

Salin `public.pem` dari server SSO ke folder `backend/keys/`:

```bash
mkdir backend/keys
# salin public.pem ke backend/keys/public.pem
```

Jalankan server:

```bash
php artisan serve --port=3011
```

### Langkah 2 — Setup Frontend

```bash
cd frontend
npm install
cp .env.example .env
```

Isi variabel di `.env`:

```env
VITE_API_BASE_URL=/api
VITE_PORT=5011
VITE_API_SSO_URL=http://localhost:3009/api
VITE_SSO_CLIENT_ID=app_xxxxxxxxxxxxxxxx
```

Jalankan dev server:

```bash
npm run dev
```

Akses di browser: **http://localhost:5011**

---

## Perbedaan CSR vs SSR

| Aspek | CSR (repo ini) | SSR |
| :--- | :--- | :--- |
| Token disimpan | `localStorage` di browser | Cookie `httpOnly` di browser (set oleh server) |
| Refresh token | `httpOnly` cookie — browser kirim langsung ke SSO | `httpOnly` cookie — server meneruskan Cookie browser ke SSO (*server-side proxy*) |
| Verifikasi token | Backend verifikasi JWT offline per request (`openssl_verify`) | Guard Laravel verifikasi JWT offline per request (`openssl_verify`) |
| Session lokal | Tidak ada (stateless) | Tidak ada (stateless) |
| Rendering | Client-side (Vue 3 SPA) | Server-side (Blade + Livewire) |
| Keamanan token | Rentan XSS jika ada celah injeksi JS | Lebih aman — token tidak bisa diakses JavaScript |
| Kompleksitas backend | Lebih sederhana (verifikasi JWT + sajikan data) | Lebih kompleks (guard custom, cookie proxying, Livewire) |
| Kompleksitas frontend | Tinggi (PKCE manual, refresh interceptor, broadcast logout) | Minimal (Blade + Livewire reaktif) |

---

## Dokumentasi Detail

- [Backend README](./backend/README.md) — Guard `sso-token`, JWT verification offline, endpoint API, komunikasi ke SSO Server
- [Frontend README](./frontend/README.md) — Alur PKCE, STORAGE_KEYS, interceptor refresh, semua API call ke SSO & backend
