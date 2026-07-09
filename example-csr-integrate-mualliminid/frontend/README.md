# Frontend — Example CSR Integrate MualliminID

Vue 3 + Vite sebagai **Single Page Application (SPA)** yang mengimplementasikan alur autentikasi **OAuth 2.0 Authorization Code + PKCE** untuk login via Mu'allimin ID secara stateless.

> Frontend ini **bertanggung jawab penuh** atas alur PKCE, penyimpanan token di `localStorage`, refresh token otomatis, dan sinkronisasi logout antar tab browser. Backend hanya berperan memverifikasi token dan melayani data.

---

## Teknologi

| Komponen         | Detail                                     |
| :--------------- | :----------------------------------------- |
| Framework        | Vue 3 (Composition API + `<script setup>`) |
| Bundler          | Vite                                       |
| State Management | Pinia                                      |
| HTTP Client      | Axios                                      |
| Styling          | Tailwind CSS v4                            |
| Router           | Vue Router 4                               |

---

## Struktur Direktori

```
frontend/
├── src/
│   ├── assets/
│   │   └── main.css               ← Entry point Tailwind CSS
│   ├── components/
│   │   ├── Card.vue               ← Layout card reusable (slot: title, headerActions, footer)
│   │   ├── Pagination.vue         ← Navigasi halaman dengan sliding window
│   │   ├── Profile.vue            ← Kartu profil pengguna aktif
│   │   └── Table.vue              ← Tabel data dinamis berbasis konfigurasi kolom
│   ├── router/
│   │   └── index.js               ← Definisi rute + navigation guard
│   ├── services/
│   │   └── api.js                 ← Axios client terpusat + interceptors + API functions
│   ├── stores/
│   │   └── auth.js                ← Pinia store: user state, hydrate, logout, broadcast
│   ├── utils/
│   │   ├── constants.js           ← Konstanta kunci localStorage/sessionStorage (STORAGE_KEYS)
│   │   ├── pkce.js                ← Generator PKCE (code_verifier + code_challenge)
│   │   └── validateEnv.js         ← Validasi environment variables saat startup
│   ├── views/
│   │   ├── callback-view.vue      ← Halaman callback OAuth (tukar code → token)
│   │   ├── dashboard-view.vue     ← Halaman utama setelah login
│   │   └── login-view.vue         ← Halaman login (inisiasi PKCE flow)
│   ├── App.vue                    ← Shell aplikasi (loading guard + toast system)
│   └── main.js                    ← Entry point — urutan init yang kritis
├── index.html
├── vite.config.js                 ← Konfigurasi Vite (proxy /api → backend + alias @)
└── .env.example                   ← Template environment variables
```

---

## Cara Setup

### 1. Install Dependensi

```bash
npm install
```

### 2. Konfigurasi Environment

```bash
cp .env.example .env
```

Edit `.env`:

```env
VITE_API_BASE_URL=/api
VITE_PORT=5011

VITE_API_SSO_URL=http://localhost:3009/api
VITE_SSO_CLIENT_ID=app_xxxxxxxxxxxxxxxx
```

| Variabel             | Keterangan                                                                                             |
| :------------------- | :----------------------------------------------------------------------------------------------------- |
| `VITE_API_BASE_URL`  | Base URL untuk request ke backend. Nilai `/api` membuat request melewati proxy Vite — hindari CORS.    |
| `VITE_PORT`          | Port dev server frontend                                                                               |
| `VITE_API_SSO_URL`   | Base URL API server MualliminID SSO — digunakan langsung oleh browser untuk token exchange dan refresh |
| `VITE_SSO_CLIENT_ID` | Client ID aplikasi yang terdaftar di SSO                                                               |

### 3. Jalankan Dev Server

```bash
npm run dev
```

Akses di browser: **http://localhost:5011**

---

## Penjelasan Detail Per File

### `utils/constants.js` — Konstanta Storage Keys

Semua kunci `localStorage` dan `sessionStorage` yang digunakan aplikasi didefinisikan terpusat di sini untuk menghindari typo dan magic strings yang tersebar.

```js
export const STORAGE_KEYS = {
  SSO_TOKEN: "sso_token", // Access token (localStorage) — dikirim ke backend setiap request
  SSO_ID_TOKEN: "sso_id_token", // ID token (localStorage) — dipakai untuk end_session ke SSO saat logout
  CODE_VERIFIER: "sso_code_verifier", // PKCE verifier (sessionStorage) — dihapus setelah token exchange
  OAUTH_STATE: "sso_oauth_state", // State anti-CSRF (sessionStorage) — dihapus di awal callback
  THEME: "theme", // Preferensi tema (localStorage) — persisten
  JUST_LOGGED_OUT: "just_logged_out", // Flag sementara penanda baru saja logout (sessionStorage)
};
```

`sessionStorage` dipakai untuk data sementara yang hanya perlu hidup selama satu sesi tab. `localStorage` untuk data yang perlu bertahan meski tab ditutup dan dibuka lagi.

---

### `utils/pkce.js` — Generator PKCE

PKCE (Proof Key for Code Exchange) adalah ekstensi OAuth 2.0 yang wajib dipakai oleh aplikasi publik seperti SPA — karena SPA tidak bisa menyimpan `client_secret` secara aman. Mekanisme ini mencegah serangan **authorization code interception**.

**`generateCodeVerifier()`**

Menghasilkan 56 unsigned 32-bit integer acak via `window.crypto.getRandomValues()`, lalu dikonversi ke string hex 112 karakter. Ini adalah nilai rahasia yang hanya diketahui oleh browser tab yang memulai login.

```js
const array = new Uint32Array(56);
window.crypto.getRandomValues(array);
return Array.from(array, (dec) => ("0" + dec.toString(16)).substr(-2)).join("");
```

**`generateCodeChallenge(verifier)`**

Hash verifier menggunakan SHA-256 via Web Crypto API, lalu encode hasilnya sebagai Base64URL (tanpa padding `=`, tanda `+` jadi `-`, `/` jadi `_`). Nilai inilah yang dikirim ke SSO saat redirect authorize.

```js
const digest = await window.crypto.subtle.digest(
  "SHA-256",
  encoder.encode(verifier),
);
return btoa(String.fromCharCode(...new Uint8Array(digest)))
  .replace(/\+/g, "-")
  .replace(/\//g, "_")
  .replace(/=+$/, "");
```

Saat token exchange, SSO Server menghash ulang `code_verifier` yang dikirim browser dan mencocokkannya dengan `code_challenge` yang disimpan sebelumnya. Jika tidak cocok, token tidak diterbitkan.

---

### `utils/validateEnv.js` — Validasi Environment

Diimport paling pertama di `main.js` (sebelum apapun). Jika ada env variable wajib yang kosong, aplikasi langsung lempar error dengan pesan yang jelas — mencegah bug samar akibat URL atau client_id yang kosong.

```js
const REQUIRED_VARS = [
  "VITE_API_BASE_URL",
  "VITE_API_SSO_URL",
  "VITE_SSO_CLIENT_ID",
];
const missing = REQUIRED_VARS.filter((key) => !import.meta.env[key]);
if (missing.length)
  throw new Error(
    `❌ Missing environment variables:\n${missing.map((k) => `   - ${k}`).join("\n")}`,
  );
```

---

### `main.js` — Entry Point & Urutan Inisialisasi

```js
import "./utils/validateEnv"; // 1. Pastikan semua env tersedia
import { createApp } from "vue";
import { createPinia } from "pinia";
import App from "./App.vue";
import router from "./router";
import { useAuthStore } from "./stores/auth";

const app = createApp(App);
const pinia = createPinia();
app.use(pinia); // 2. Aktifkan Pinia dulu sebelum store dipakai

const authStore = useAuthStore();
authStore.listenChannel(); // 3. Mulai dengarkan event logout dari tab lain

(async () => {
  await authStore.hydrate(); // 4. Cek apakah user sudah login (GET /api/auth/me)
  app.use(router); // 5. Pasang router SETELAH hydrate selesai
  app.mount("#app"); // 6. Render aplikasi
})();
```

**Mengapa router dipasang setelah `hydrate()`?**

`router.beforeEach` mengecek `authStore.isLoggedIn`. Jika router dipasang sebelum `hydrate()` selesai, guard akan salah membaca status — user yang sebenarnya sudah login akan dianggap belum login dan diredirect ke halaman Login.

---

### `App.vue` — Shell Aplikasi

Komponen root yang bertugas tiga hal:

**1. Loading Guard**
`<router-view>` hanya ditampilkan setelah `authStore.isHydrated = true`. Selama `hydrate()` berjalan, tampil spinner "Memuat Aplikasi...". Ini mencegah flash of unauthenticated content.

```html
<router-view
  v-if="authStore.isHydrated"
  @logout="handleLogout"
  @show-toast="triggerToast"
/>
<div v-else>... spinner ...</div>
```

**2. Toast Notification System**
Menerima event `@show-toast` dengan payload `{ message, type }` dari view manapun. Toast muncul di pojok kanan atas dan auto-dismiss setelah 4 detik.

**3. Logout Handler**
Menerima event `@logout` dari Dashboard, lalu mengarahkan browser ke halaman Login via `router.push({ name: 'Login' })`.

---

### `router/index.js` — Rute & Navigation Guard

```js
const routes = [
  {
    path: "/",
    name: "Dashboard",
    component: DashboardView,
    meta: { requiresAuth: true },
  },
  { path: "/login", name: "Login", component: LoginView },
  { path: "/callback", name: "Callback", component: CallbackView },
];
```

**Navigation Guard `beforeEach`:**

```js
router.beforeEach((to, from) => {
  const authStore = useAuthStore();
  if (to.meta.requiresAuth && !authStore.isLoggedIn) return { name: "Login" };
  if (to.name === "Login" && authStore.isLoggedIn) return { name: "Dashboard" };
});
```

- Rute `/` membutuhkan auth. Jika belum login → redirect ke `/login`
- Jika sudah login tapi coba akses `/login` → redirect ke `/` (cegah login ulang)
- `/callback` tidak dilindungi — harus bisa diakses saat belum login

---

### `services/api.js` — Axios Client Terpusat

Semua request HTTP ke backend Laravel melewati satu instance Axios ini. File ini mengekspor dua hal: instance `apiClient` dan objek `AuthAPI`/`UserAPI` sebagai abstraksi endpoint.

**Request Interceptor — Injeksi Token Otomatis**

Setiap request yang keluar akan otomatis ditambahkan header `Authorization` jika token ada di localStorage:

```js
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem(STORAGE_KEYS.SSO_TOKEN);
  if (token) config.headers["Authorization"] = `Bearer ${token}`;
  return config;
});
```

**Response Interceptor — Refresh Token Otomatis**

Jika backend mengembalikan `401` dan token masih ada di localStorage (bukan karena proses logout atau sudah pernah retry), interceptor langsung mencoba refresh token ke SSO Server.

Request yang dikirim ke SSO:

```http
POST {VITE_API_SSO_URL}/auth/sso/token
Content-Type: application/json
Cookie: {CLIENT_ID}_refresh_token=...  ← dikirim otomatis karena withCredentials: true

{
  "grant_type": "refresh_token",
  "client_id":  "app_xxxx"
}
```

Refresh token tidak dikirim di body karena SSO menyimpannya sebagai **httpOnly cookie** — browser mengirimnya otomatis selama `withCredentials: true` disertakan.

Response dari SSO jika berhasil:

```json
{
  "status": "success",
  "data": {
    "access_token": "eyJ...",
    "id_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 900
  }
}
```

Yang dilakukan interceptor dengan response ini:

```js
const newAccessToken = response.data?.data?.access_token;
localStorage.setItem(STORAGE_KEYS.SSO_TOKEN, newAccessToken); // Ganti token lama
processQueue(null, newAccessToken); // Proses ulang request yang antri
originalRequest.headers["Authorization"] = `Bearer ${newAccessToken}`;
return apiClient(originalRequest); // Ulangi request yang gagal
```

Jika refresh gagal (cookie expired / SSO menolak):

```js
processQueue(refreshError, null); // Tolak semua request yang antri
localStorage.removeItem(STORAGE_KEYS.SSO_TOKEN);
localStorage.removeItem(STORAGE_KEYS.SSO_ID_TOKEN);
router.push({ name: "Login" }); // Paksa logout ke halaman Login
```

Request lain yang datang saat proses refresh sedang berjalan tidak langsung gagal — mereka dimasukkan ke `failedQueue` dan akan diproses ulang atau ditolak tergantung hasil refresh.

**Fungsi API yang Tersedia**

```js
export const AuthAPI = {
  me: () => apiCall("/auth/me"),
  logout: () => apiClient.post("/auth/logout").catch(() => {}),
};

export const UserAPI = {
  getAll: (params) => apiCall("/users", { params }),
  sync: () => apiCall("/users/sync", { method: "POST" }),
};
```

---

### `stores/auth.js` — Pinia Auth Store

| State        | Tipe        | Keterangan                                     |
| :----------- | :---------- | :--------------------------------------------- |
| `user`       | Object/null | Data profil user aktif — null jika belum login |
| `isHydrated` | Boolean     | Apakah proses cek session awal sudah selesai   |

| Getter        | Keterangan                                                  |
| :------------ | :---------------------------------------------------------- |
| `isLoggedIn`  | `true` jika `user !== null`                                 |
| `currentUser` | Alias untuk `user`                                          |
| `appRole`     | Role user uppercase — `user?.role?.toUpperCase()` atau null |

**`hydrate()`**

Dipanggil sekali saat startup (di `main.js`) dan sekali lagi di callback setelah token exchange berhasil.

```js
async hydrate() {
  try {
    const res = await AuthAPI.me();          // GET /api/auth/me
    if (!res.data?.id) throw new Error();
    this.setUserData(res.data);
  } catch {
    this.user = null;
  } finally {
    this.isHydrated = true;
  }
}
```

**`logout(broadcast = true)`**

```
1. Set flag isLoggingOut (cegah interceptor api.js mencoba refresh saat logout)
2. Simpan JUST_LOGGED_OUT ke sessionStorage
3. Jika broadcast = true → kirim pesan 'logout' ke semua tab via BroadcastChannel
4. Ambil ssoToken dan idToken dari localStorage
5. POST {SSO_API_URL}/auth/sso/end_session ke SSO
   Body: { id_token_hint: idToken, client_id: CLIENT_ID }
   Header: Authorization: Bearer {ssoToken}
   ← SSO invalidasi session dan refresh token di sisi server
6. Hapus user dari state, hapus token dari localStorage
7. POST /api/auth/logout ke backend (sinyal, opsional)
```

**`listenChannel()`**

BroadcastChannel `"auth-example"` mendengarkan pesan dari tab lain. Jika ada tab yang logout (`event.data === 'logout'`), tab ini ikut logout dengan `this.logout(false)` — parameter `false` mencegah pesan broadcast dikirim ulang (untuk menghindari loop).

---

### `views/login-view.vue` — Halaman Login

Saat tombol "Login MualliminID" diklik, fungsi `redirectToSSO()` dijalankan:

```js
const verifier = generateCodeVerifier(); // 112 karakter hex acak
sessionStorage.setItem(STORAGE_KEYS.CODE_VERIFIER, verifier); // Simpan untuk dipakai di callback

const challenge = await generateCodeChallenge(verifier); // SHA-256 → Base64URL

const oauthState =
  Math.random().toString(36).substring(2) + Date.now().toString(36);
sessionStorage.setItem(STORAGE_KEYS.OAUTH_STATE, oauthState); // Simpan untuk validasi CSRF

const authUrl = new URL(`${VITE_API_SSO_URL}/auth/sso/authorize`);
authUrl.searchParams.set("client_id", VITE_SSO_CLIENT_ID);
authUrl.searchParams.set("redirect_uri", `${window.location.origin}/callback`);
authUrl.searchParams.set("response_type", "code");
authUrl.searchParams.set("scope", "openid profile email");
authUrl.searchParams.set("state", oauthState);
authUrl.searchParams.set("code_challenge", challenge);
authUrl.searchParams.set("code_challenge_method", "S256");

window.location.href = authUrl.toString(); // Browser redirect ke SSO Server
```

SSO Server menerima request ini, menampilkan halaman login SSO, lalu setelah user berhasil login mengarahkan browser ke `redirect_uri/callback?code=...&state=...`.

---

### `views/callback-view.vue` — Halaman Callback OAuth

Diakses browser setelah SSO Server redirect. Logika berjalan di `onMounted` via `exchangeCode()`:

**Step 1 — Validasi State (anti-CSRF)**

```js
const savedState = sessionStorage.getItem(STORAGE_KEYS.OAUTH_STATE);
sessionStorage.removeItem(STORAGE_KEYS.OAUTH_STATE); // Sekali pakai, langsung hapus

if (!state || !savedState || state !== savedState) {
  // → Tampilkan error "Validasi keamanan OAuth gagal."
}
```

**Step 2 — Ambil PKCE Verifier**

```js
const verifier = sessionStorage.getItem(STORAGE_KEYS.CODE_VERIFIER);
if (!verifier) throw new Error("PKCE verifier tidak ditemukan...");
```

**Step 3 — Tukar Authorization Code → Access Token**

Request ini dikirim langsung dari browser ke SSO Server (bukan melalui proxy backend):

```js
const tokenResponse = await axios.post(
  `${VITE_API_SSO_URL}/auth/sso/token`,
  {
    grant_type: "authorization_code",
    code, // Dari URL ?code=...
    client_id: VITE_SSO_CLIENT_ID,
    redirect_uri: window.location.origin + "/callback",
    code_verifier: verifier, // SSO cocokkan SHA-256(verifier) vs code_challenge
  },
  { withCredentials: true }, // Agar cookie refresh_token bisa di-set SSO
);
```

Response dari SSO (`tokenResponse.data.data`):

```json
{
  "access_token": "eyJ...",
  "id_token": "eyJ...",
  "token_type": "Bearer",
  "expires_in": 900,
  "client_id": "app_xxxx"
}
```

> `refresh_token` tidak ada di response JSON — SSO Server menyimpannya sebagai **httpOnly cookie** secara otomatis.

**Step 4 — Simpan Token**

```js
localStorage.setItem(STORAGE_KEYS.SSO_TOKEN, accessToken);
localStorage.setItem(STORAGE_KEYS.SSO_ID_TOKEN, idToken);
sessionStorage.removeItem(STORAGE_KEYS.CODE_VERIFIER); // Hapus verifier, sudah tidak dibutuhkan
```

**Step 5 — Hydrate (GET /api/auth/me)**

```js
await authStore.hydrate();
if (!authStore.isLoggedIn)
  throw new Error("Gagal menyinkronkan data profil dari backend.");
```

Ini memastikan backend bisa memverifikasi token dan user sudah ada (atau sudah dibuat via auto-provisioning) di database lokal.

**Step 6 — Redirect ke Dashboard**

```js
setTimeout(() => router.push({ name: "Dashboard" }), 500);
```

---

### `views/dashboard-view.vue` — Halaman Dashboard

Menampilkan:

- Profil pengguna aktif dari `authStore.currentUser`
- Statistik pengguna (`total`, `synced`, `no_role`) dari `GET /api/users`
- Tabel daftar pengguna dengan paginasi
- Tombol "Sync Pengguna" → `POST /api/users/sync`
- Toggle tema light/dark (disimpan ke `localStorage[STORAGE_KEYS.THEME]`)
- Tombol Logout → emit event `@logout` ke `App.vue`

---

## Komunikasi Frontend ke API

Frontend memanggil dua tujuan berbeda: **SSO Server langsung** (tanpa melalui proxy backend) dan **Backend Laravel** (melalui proxy Vite `/api`).

---

### Ke SSO Server — Langsung dari Browser

Request ini dibuat langsung oleh browser ke SSO Server tanpa melewati backend Laravel.

---

#### `POST /auth/sso/token` — Tukar Authorization Code → Token

**Dipanggil dari:** `callback-view.vue` saat callback OAuth diterima dari SSO.

**Request yang dikirim:**

```http
POST {VITE_API_SSO_URL}/auth/sso/token
Content-Type: application/json

{
  "grant_type":    "authorization_code",
  "code":          "{authorization_code_dari_URL}",
  "client_id":     "{VITE_SSO_CLIENT_ID}",
  "redirect_uri":  "{window.location.origin}/callback",
  "code_verifier": "{verifier_dari_sessionStorage}"
}
```

SSO Server menghash `code_verifier` dengan SHA-256 dan mencocokkannya dengan `code_challenge` yang dikirim saat authorize. Jika cocok, token diterbitkan.

**Response dari SSO:**

```json
{
  "status": "success",
  "message": "Token berhasil diterbitkan",
  "data": {
    "access_token": "eyJ...",
    "id_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 900,
    "client_id": "app_xxxx"
  }
}
```

> `refresh_token` tidak ada di response JSON — SSO Server menyimpannya sebagai **httpOnly cookie** `{CLIENT_ID}_refresh_token` secara otomatis. Request dikirim dengan `withCredentials: true` agar cookie tersebut bisa di-set oleh SSO.

**Yang dilakukan frontend dengan response ini:**

```js
localStorage.setItem(STORAGE_KEYS.SSO_TOKEN, tokenData.access_token);
localStorage.setItem(STORAGE_KEYS.SSO_ID_TOKEN, tokenData.id_token);
sessionStorage.removeItem(STORAGE_KEYS.CODE_VERIFIER);
await authStore.hydrate(); // Lanjut ke GET /api/auth/me
```

---

#### `POST /auth/sso/token` — Refresh Access Token

**Dipanggil dari:** Interceptor response di `services/api.js` saat backend mengembalikan `401`.

**Request yang dikirim:**

```http
POST {VITE_API_SSO_URL}/auth/sso/token
Content-Type: application/json
Cookie: {CLIENT_ID}_refresh_token=...  ← dikirim otomatis karena withCredentials: true

{
  "grant_type": "refresh_token",
  "client_id":  "{VITE_SSO_CLIENT_ID}"
}
```

**Response dari SSO:**

```json
{
  "status": "success",
  "data": {
    "access_token": "eyJ...",
    "id_token": "eyJ...",
    "token_type": "Bearer",
    "expires_in": 900
  }
}
```

**Yang dilakukan frontend dengan response ini:**

```js
const newAccessToken = response.data?.data?.access_token;
localStorage.setItem(STORAGE_KEYS.SSO_TOKEN, newAccessToken);
processQueue(null, newAccessToken); // Proses ulang semua request yang tertahan
return apiClient(originalRequest); // Ulangi request yang awalnya gagal 401
```

Jika refresh gagal: hapus token dari localStorage dan redirect ke halaman Login.

---

#### `POST /auth/sso/end_session` — Logout dari SSO

**Dipanggil dari:** `stores/auth.js` action `logout()`.

**Request yang dikirim:**

```http
POST {VITE_API_SSO_URL}/auth/sso/end_session
Authorization: Bearer {sso_token}
Content-Type: application/json

{
  "id_token_hint": "{sso_id_token_dari_localStorage}",
  "client_id":     "{VITE_SSO_CLIENT_ID}"
}
```

SSO Server memverifikasi `id_token_hint`, mencari session yang terkait (`sid` dari JWT payload), lalu menon-aktifkan session dan refresh token di sisi server.

**Response dari SSO:**

```json
{
  "status": "success",
  "message": "Logout client app berhasil"
}
```

**Yang dilakukan frontend setelah request ini:**

```js
this.user = null;
localStorage.removeItem(STORAGE_KEYS.SSO_TOKEN);
localStorage.removeItem(STORAGE_KEYS.SSO_ID_TOKEN);
await AuthAPI.logout(); // Sinyal ke backend (fire and forget)
```

---

### Ke Backend Laravel — Melalui Proxy Vite `/api`

Request ini dikirim ke `{VITE_API_BASE_URL}/...` yang di-proxy Vite ke `http://localhost:3011`. Token dari localStorage otomatis ditambahkan ke header `Authorization: Bearer` oleh interceptor request di `api.js`.

---

#### `GET /api/auth/me` — Ambil Data User Login

**Dipanggil dari:** `stores/auth.js` action `hydrate()` — saat startup dan setelah token exchange.

**Request yang dikirim:**

```http
GET /api/auth/me
Authorization: Bearer {sso_token}
```

**Response dari Backend:**

```json
{
  "status": "success",
  "data": {
    "id": 1,
    "sso_id": "uuid-dari-sso",
    "name": "Ahmad Fauzi",
    "email": "ahmad.fauzi@muallimin.sch.id",
    "nbm": "20240001",
    "whatsapp_number": "081234567890",
    "role": "GURU"
  }
}
```

**Yang dilakukan frontend dengan response ini:**

```js
if (!res.data?.id) throw new Error("no_user");
this.setUserData(res.data); // Set authStore.user → isLoggedIn = true
```

---

#### `GET /api/users` — Daftar Pengguna

**Dipanggil dari:** `dashboard-view.vue` fungsi `loadUsers()` — saat Dashboard dimuat dan saat ganti halaman.

**Request yang dikirim:**

```http
GET /api/users?page=1&per_page=10
Authorization: Bearer {sso_token}
```

**Response dari Backend:**

```json
{
  "status": "success",
  "stats": {
    "total": 150,
    "synced": 148,
    "no_role": 3
  },
  "users": {
    "data": [
      {
        "id": 1,
        "name": "Ahmad Fauzi",
        "email": "...",
        "role": "GURU",
        "nbm": "...",
        "whatsapp_number": "..."
      }
    ],
    "current_page": 1,
    "last_page": 8,
    "from": 1,
    "to": 10,
    "total": 150
  }
}
```

**Yang dilakukan frontend dengan response ini:**

```js
stats.value = data.stats;
users.value = data.users.data;
pagination.value = {
  firstItem: data.users.from,
  lastItem: data.users.to,
  total: data.users.total,
  currentPage: data.users.current_page,
  lastPage: data.users.last_page,
};
```

---

#### `POST /api/users/sync` — Sync Pengguna dari SSO

**Dipanggil dari:** `dashboard-view.vue` fungsi `syncUsers()` — saat tombol "Sinkronkan dari SSO" ditekan.

**Request yang dikirim:**

```http
POST /api/users/sync
Authorization: Bearer {sso_token}
```

**Response dari Backend:**

```json
{
  "status": "success",
  "message": "Sinkronisasi berhasil! 285 pengguna telah diselaraskan."
}
```

**Yang dilakukan frontend dengan response ini:**

```js
emit("show-toast", { message: response.message, type: "success" });
await loadUsers(1); // Reload tabel setelah sync selesai
```

---

#### `POST /api/auth/logout` — Sinyal Logout ke Backend

**Dipanggil dari:** `stores/auth.js` action `logout()`, setelah SSO end_session selesai.

**Request yang dikirim:**

```http
POST /api/auth/logout
Authorization: Bearer {sso_token}
```

**Response dari Backend:**

```json
{
  "status": "success",
  "message": "Logged out successfully"
}
```

Response ini **diabaikan** oleh frontend (`.catch(() => {})`). Ini murni sinyal ke backend bahwa user telah logout — backend CSR tidak menyimpan state apapun yang perlu dihapus.

---

## Konfigurasi Proxy Vite

File: [`vite.config.js`](vite.config.js)

```js
proxy: {
  '/api': {
    target:       'http://localhost:3011',
    changeOrigin: true,
  }
}
```

Setiap request dari browser ke `http://localhost:5011/api/...` di-forward ke `http://localhost:3011/api/...` oleh Vite. Di mata browser, request tetap ke origin yang sama sehingga tidak ada CORS issue.

Di production, fungsi ini digantikan oleh reverse proxy seperti Nginx atau Apache.

---

## Storage Map

| Kunci               | Storage        | Kapan Dibuat                               | Kapan Dihapus                                              |
| :------------------ | :------------- | :----------------------------------------- | :--------------------------------------------------------- |
| `sso_token`         | localStorage   | Callback — setelah token exchange berhasil | Saat logout                                                |
| `sso_id_token`      | localStorage   | Callback — setelah token exchange berhasil | Saat logout                                                |
| `sso_code_verifier` | sessionStorage | Login — sebelum redirect ke SSO            | Callback — setelah token exchange                          |
| `sso_oauth_state`   | sessionStorage | Login — sebelum redirect ke SSO            | Callback — di awal validasi state                          |
| `theme`             | localStorage   | Dashboard — saat user toggle tema          | Tidak pernah (persisten)                                   |
| `just_logged_out`   | sessionStorage | Awal proses logout                         | Tidak di-remove secara eksplisit — hilang saat tab ditutup |

---

## Git Safety

File-file berikut **tidak ter-commit** ke repositori:

```
.env        ← Berisi VITE_SSO_CLIENT_ID dan URL SSO
dist/       ← Hasil build produksi
node_modules/
```

File `.env.example` **boleh** di-commit karena hanya berisi template tanpa nilai sensitif.
