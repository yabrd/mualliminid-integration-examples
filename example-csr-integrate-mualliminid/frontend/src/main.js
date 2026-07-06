import './utils/validateEnv';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import '@/assets/main.css';
import App from './App.vue';
import router from './router';
import { useAuthStore } from './stores/auth';

const app = createApp(App);
const pinia = createPinia();
app.use(pinia);

const authStore = useAuthStore();
authStore.listenChannel();

(async () => {
  await authStore.hydrate();
  app.use(router);
  app.mount('#app');
})();
