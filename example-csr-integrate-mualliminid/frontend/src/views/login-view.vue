<template>
  <div class="min-h-screen flex items-center justify-center p-4 bg-slate-50 dark:bg-slate-950 transition-colors duration-200">
    <Card class="max-w-sm text-center p-6 pb-4 border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/40 shadow-xl dark:shadow-none">
      <div class="border-b border-slate-200 dark:border-slate-800/60 pb-6 mb-5">
        <h1 class="text-4xl font-black tracking-tight text-slate-800 dark:text-white block w-full text-center uppercase">
          Sign in
        </h1>
        <p class="text-sm text-indigo-700 dark:text-indigo-400 mt-2 uppercase tracking-wide font-bold">
          Example CSR Integrate MualliminID
        </p>
      </div>

      <p class="text-sm text-slate-600 dark:text-slate-400 mb-6 leading-relaxed">
        Aplikasi demo integrasi otentikasi Single Sign-On (SSO) MualliminID berbasis stateless menggunakan Laravel 13, Vue 3, dan Vite.
      </p>

      <button @click="redirectToSSO" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-4 w-full block rounded-xl transition-all shadow-lg shadow-indigo-600/10 uppercase tracking-wider text-xs mb-2 cursor-pointer">
        Login MualliminID
      </button>

      <template #footer>
        <p class="text-xs text-slate-400 dark:text-slate-600 text-center w-full">
          &copy; {{ new Date().getFullYear() }} Example CSR Integrate MualliminID
        </p>
      </template>
    </Card>
  </div>
</template>

<script setup>
import Card from "../components/Card.vue";
import { generateCodeVerifier, generateCodeChallenge } from "@/utils/pkce";
import { STORAGE_KEYS } from "@/utils/constants";

const redirectToSSO = async () => {
  const verifier = generateCodeVerifier();
  sessionStorage.setItem(STORAGE_KEYS.CODE_VERIFIER, verifier);
  const challenge = await generateCodeChallenge(verifier);

  const authUrl = new URL(`${import.meta.env.VITE_API_SSO_URL}/auth/sso/authorize`);
  authUrl.searchParams.set("client_id", import.meta.env.VITE_SSO_CLIENT_ID);
  authUrl.searchParams.set("redirect_uri", `${window.location.origin}/callback`);
  authUrl.searchParams.set("response_type", "code");
  authUrl.searchParams.set("scope", "openid profile email");
  const oauthState = Math.random().toString(36).substring(2) + Date.now().toString(36);
  sessionStorage.setItem(STORAGE_KEYS.OAUTH_STATE, oauthState);
  authUrl.searchParams.set("state", oauthState);
  authUrl.searchParams.set("code_challenge", challenge);
  authUrl.searchParams.set("code_challenge_method", "S256");

  window.location.href = authUrl.toString();
};
</script>
