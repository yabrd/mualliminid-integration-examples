<template>
  <div class="min-h-screen transition-colors duration-200">
    <Navbar :user="user" :theme="theme" @toggleTheme="toggleTheme" @logout="logout" />
    <div class="max-w-full mx-auto px-4 py-4 space-y-3">
      <div class="space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <Card>
            <span class="text-xs text-slate-500 block mb-1"
              >Total Pengguna Lokal</span
            >
            <span
              class="text-2xl font-bold text-slate-800 dark:text-slate-200"
              >{{ stats.total }}</span
            >
          </Card>
          <Card>
            <span class="text-xs text-slate-500 block mb-1"
              >Pengguna Ter-sinkronisasi</span
            >
            <span
              class="text-2xl font-bold text-slate-800 dark:text-slate-200"
              >{{ stats.synced }}</span
            >
          </Card>
          <Card>
            <span class="text-xs text-slate-500 block mb-1"
              >Pengguna Tanpa Akses Role</span
            >
            <span
              class="text-2xl font-bold text-slate-800 dark:text-slate-200"
              >{{ stats.no_role }}</span
            >
          </Card>
        </div>

        <Card body-class="p-0">
          <template #title>
            <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-200">
              Manajemen Pengguna
            </h2>
          </template>

          <template #headerActions>
            <div
              class="flex items-center gap-2 sm:gap-3 w-full sm:w-auto justify-between sm:justify-end"
            >
              <div>
                <select
                  v-model="perPage"
                  @change="loadUsers(1)"
                  class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-800/80 text-slate-700 dark:text-slate-300 text-xs px-2.5 py-2 focus:outline-none focus:border-indigo-500 font-semibold rounded-lg cursor-pointer shadow-sm dark:shadow-none"
                >
                  <option v-for="opt in perPageOptions" :key="opt" :value="opt">
                    {{ opt }} / Halaman
                  </option>
                </select>
              </div>
              <div>
                <button
                  @click="syncUsers"
                  :disabled="syncing"
                  class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-500 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-all border border-slate-200 dark:border-slate-800/80 shadow-sm dark:shadow-none flex items-center gap-2 cursor-pointer"
                >
                  <span
                    v-if="syncing"
                    class="w-3.5 h-3.5 border-2 border-white/30 border-t-white animate-spin"
                  ></span>
                  Sinkronkan dari SSO
                </button>
              </div>
            </div>
          </template>

          <div class="w-full relative h-[460px]">
            <div
              v-if="loading || syncing"
              class="absolute inset-0 w-full h-full bg-white/60 dark:bg-slate-950/60 backdrop-blur-[1px] z-20 flex items-center justify-center"
            >
              <svg
                class="animate-spin h-8 w-8 text-indigo-600"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
            </div>

            <div class="overflow-auto h-full w-full">
              <Table :headers="headers" :rows="users" :columns="columns" />
            </div>
          </div>

          <template #footer>
            <Pagination
              :first-item="pagination.firstItem"
              :last-item="pagination.lastItem"
              :total="pagination.total"
              :current-page="pagination.currentPage"
              :last-page="pagination.lastPage"
              @change-page="loadUsers"
            />
          </template>
        </Card>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import Card from "../components/Card.vue";
import Table from "../components/Table.vue";
import Pagination from "../components/Pagination.vue";
import Navbar from "../components/navbar.vue";
import { UserAPI } from "../services/api";
import { useAuthStore } from "../stores/auth";
import { STORAGE_KEYS } from "../utils/constants";

const props = defineProps({
  user: { type: Object, required: false, default: null },
});

const emit = defineEmits(["logout", "show-toast"]);

const authStore = useAuthStore();

const stats = ref({ total: 0, synced: 0, no_role: 0 });
const users = ref([]);
const loading = ref(false);
const syncing = ref(false);
const perPage = ref(10);
const perPageOptions = [10, 20, 60, 80, 100];
const theme = ref("light");

const headers = ["Nama", "Email", "Role", "NBM", "WhatsApp"];
const columns = [
  { key: "name", type: "title" },
  { key: "email", type: "text" },
  { key: "role", type: "badge" },
  { key: "nbm", type: "code" },
  { key: "whatsapp_number", type: "code" },
];

const pagination = ref({
  firstItem: 0,
  lastItem: 0,
  total: 0,
  currentPage: 1,
  lastPage: 1,
});

const toggleTheme = () => {
  if (document.documentElement.classList.contains("dark")) {
    document.documentElement.classList.remove("dark");
    localStorage.setItem(STORAGE_KEYS.THEME, "light");
    theme.value = "light";
  } else {
    document.documentElement.classList.add("dark");
    localStorage.setItem(STORAGE_KEYS.THEME, "dark");
    theme.value = "dark";
  }
};

const loadUsers = async (page = 1) => {
  loading.value = true;
  try {
    const data = await UserAPI.getAll({ page, per_page: perPage.value });
    stats.value = data.stats;
    users.value = data.users.data;
    pagination.value = {
      firstItem: data.users.from ? data.users.from : 0,
      lastItem: data.users.to ? data.users.to : 0,
      total: data.users.total,
      currentPage: data.users.current_page,
      lastPage: data.users.last_page,
    };
  } catch (err) {
    emit("show-toast", {
      message: "Gagal memuat daftar pengguna.",
      type: "error",
    });
  } finally {
    loading.value = false;
  }
};

const syncUsers = async () => {
  syncing.value = true;
  try {
    const response = await UserAPI.sync();
    emit("show-toast", { message: response.message, type: "success" });
    await loadUsers(1);
  } catch (err) {
    emit("show-toast", { message: err.message, type: "error" });
  } finally {
    syncing.value = false;
  }
};

const logout = async () => {
  await authStore.logout();
  emit("logout");
};

onMounted(() => {
  theme.value = document.documentElement.classList.contains("dark")
    ? "dark"
    : "light";
  loadUsers(1);
});
</script>
