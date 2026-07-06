<template>
  <div class="min-h-screen transition-colors duration-200">
    <router-view 
      v-if="authStore.isHydrated" 
      :user="authStore.user" 
      @logout="handleLogout" 
      @show-toast="triggerToast" 
    />

    <div v-else class="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-950">
      <div class="text-center">
        <div class="w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
        <p class="mt-2 text-xs text-slate-500 font-semibold uppercase tracking-wider">Memuat Aplikasi...</p>
      </div>
    </div>

    <div class="fixed top-6 right-6 z-50 flex flex-col gap-3">
      <div 
        v-for="toast in toasts" 
        :key="toast.id" 
        class="bg-white dark:bg-slate-900/80 backdrop-blur-md border border-slate-200 dark:border-slate-800/80 p-4 pl-6 shadow-2xl flex items-center justify-between gap-4 w-96 rounded-xl transition-all duration-300 relative"
      >
        <div class="absolute left-0 top-0 bottom-0 w-1 rounded-l-xl" :class="toast.type === 'success' ? 'bg-emerald-500' : 'bg-red-500'"></div>
        <div class="flex-1 min-w-0">
          <span class="text-xs font-bold uppercase tracking-wider block mb-0.5" :class="toast.type === 'success' ? 'text-emerald-500' : 'text-red-500'">
            {{ toast.type === 'success' ? 'Sukses' : 'Gagal' }}
          </span>
          <span class="text-xs text-slate-600 dark:text-slate-300 block leading-tight">{{ toast.message }}</span>
        </div>
        <button @click="removeToast(toast.id)" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors shrink-0 p-1 cursor-pointer font-bold text-sm">
          &times;
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from './stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const toasts = ref([]);

const triggerToast = ({ message, type = 'success' }) => {
  const id = Date.now() + Math.random();
  toasts.value.push({ id, message, type });
  setTimeout(() => removeToast(id), 4000);
};

const removeToast = (id) => {
  toasts.value = toasts.value.filter(t => t.id !== id);
};

const handleLogout = () => {
  router.push({ name: 'Login' });
};

</script>
