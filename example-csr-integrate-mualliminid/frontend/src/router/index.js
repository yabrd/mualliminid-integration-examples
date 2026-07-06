import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import DashboardView from '../views/dashboard-view.vue';
import LoginView from '../views/login-view.vue';
import CallbackView from '../views/callback-view.vue';

const routes = [
  {
    path: '/',
    name: 'Dashboard',
    component: DashboardView,
    meta: { requiresAuth: true }
  },
  {
    path: '/login',
    name: 'Login',
    component: LoginView
  },
  {
    path: '/callback',
    name: 'Callback',
    component: CallbackView
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

router.beforeEach((to, from) => {
  const authStore = useAuthStore();

  if (to.meta.requiresAuth && !authStore.isLoggedIn) {
    return { name: 'Login' };
  }
  if (to.name === 'Login' && authStore.isLoggedIn) {
    return { name: 'Dashboard' };
  }
});

export default router;
