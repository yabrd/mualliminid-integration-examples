<template>
  <div
    class="min-h-screen flex items-center justify-center p-4 bg-slate-50 dark:bg-slate-950 transition-colors duration-200"
  >
    <Card
      class="max-w-sm text-center p-6 pb-4 border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/40 shadow-xl dark:shadow-none"
    >
      <div v-if="status === 'processing'" class="space-y-4">
        <div
          class="w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin mx-auto"
        ></div>
        <h2
          class="text-xl font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide"
        >
          Menghubungkan Sesi...
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">
          Sedang memverifikasi kode otorisasi dari server Muallimin ID.
        </p>
        <div class="w-full bg-slate-200 dark:bg-slate-800 h-1.5 mt-2">
          <div
            class="bg-indigo-600 h-1.5 transition-all duration-300"
            :style="{ width: progress + '%' }"
          ></div>
        </div>
      </div>

      <div v-else-if="status === 'error'" class="space-y-4">
        <div class="text-red-500 text-4xl font-bold">&otimes;</div>
        <h2
          class="text-xl font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wide"
        >
          Autentikasi Gagal
        </h2>
        <p class="text-xs text-slate-500 dark:text-slate-400">
          {{ errorMessage }}
        </p>
        <p
          class="text-xs text-red-500 dark:text-red-400 bg-red-50 dark:bg-red-950/20 p-2 font-mono break-all"
        >
          {{ errorDetails }}
        </p>
        <button
          @click="goToLogin"
          class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 w-full block rounded-xl transition-all uppercase tracking-wider text-xs cursor-pointer"
        >
          Kembali ke Login
        </button>
      </div>
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import axios from "axios";
import Card from "../components/Card.vue";
import { useAuthStore } from "../stores/auth";
import { STORAGE_KEYS } from "../utils/constants";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

const status = ref("processing");
const progress = ref(20);
const errorMessage = ref("");
const errorDetails = ref("");

const goToLogin = () => {
  router.push({ name: "Login" });
};

const exchangeCode = async () => {
  const { code, state, error, error_description } = route.query;

  const savedState = sessionStorage.getItem(STORAGE_KEYS.OAUTH_STATE);
  sessionStorage.removeItem(STORAGE_KEYS.OAUTH_STATE);

  if (!state || !savedState || state !== savedState) {
    status.value = "error";
    errorMessage.value = "Validasi keamanan OAuth gagal.";
    errorDetails.value =
      "Parameter state tidak valid atau kedaluwarsa. Silakan coba login kembali.";
    return;
  }

  if (error) {
    status.value = "error";
    errorMessage.value = "SSO Server returned an error.";
    errorDetails.value = error_description;
    return;
  }

  if (!code) {
    router.replace({ name: "Login" });
    return;
  }

  try {
    progress.value = 50;
    const verifier = sessionStorage.getItem(STORAGE_KEYS.CODE_VERIFIER);
    if (!verifier) {
      throw new Error(
        "PKCE verifier tidak ditemukan di sessionStorage. Silakan coba login kembali.",
      );
    }

    const tokenResponse = await axios.post(
      import.meta.env.VITE_API_SSO_URL + "/auth/sso/token",
      {
        grant_type: "authorization_code",
        code,
        client_id: import.meta.env.VITE_SSO_CLIENT_ID,
        redirect_uri: window.location.origin + "/callback",
        code_verifier: verifier,
      },
      {
        withCredentials: true,
      },
    );

    const tokenData = tokenResponse.data?.data;
    const accessToken = tokenData?.access_token;
    const idToken = tokenData?.id_token;

    if (!accessToken) {
      throw new Error("Access token tidak ditemukan dalam respon.");
    }

    progress.value = 75;
    localStorage.setItem(STORAGE_KEYS.SSO_TOKEN, accessToken);
    if (idToken) {
      localStorage.setItem(STORAGE_KEYS.SSO_ID_TOKEN, idToken);
    }
    sessionStorage.removeItem(STORAGE_KEYS.CODE_VERIFIER);

    progress.value = 90;
    try {
      await authStore.hydrate();
      if (!authStore.isLoggedIn) {
        throw new Error("Gagal menyinkronkan data profil dari backend.");
      }
    } catch (backendErr) {
      throw new Error(
        "Gagal memverifikasi akun lokal di backend: " + backendErr.message,
      );
    }

    progress.value = 100;
    setTimeout(() => {
      router.push({ name: "Dashboard" });
    }, 500);
  } catch (err) {
    status.value = "error";
    errorMessage.value = "Gagal memproses pertukaran token.";
    errorDetails.value = err.message;
  }
};

onMounted(() => {
  exchangeCode();
});
</script>
