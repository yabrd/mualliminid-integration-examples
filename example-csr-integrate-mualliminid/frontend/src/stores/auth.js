import { defineStore } from "pinia";
import axios from "axios";
import { AuthAPI, setLoggingOut } from "@/services/api";
import { STORAGE_KEYS } from "@/utils/constants";

const channel = new BroadcastChannel("auth-example");

export const useAuthStore = defineStore("auth", {
  state: () => ({
    user: null,
    isHydrated: false,
  }),

  getters: {
    isLoggedIn: (state) => !!state.user,
    currentUser: (state) => state.user,
    appRole: (state) => state.user?.role ? state.user.role.toUpperCase() : null,
  },

  actions: {
    setUserData(payload) {
      const data = payload?.data ? payload.data : payload;
      const user = data?.user ? data.user : data;
      if (!user || !user.id) return;
      this.user = {
        ...user,
        role: (user.role ? user.role : "").toUpperCase(),
      };
    },

    async hydrate() {
      try {
        const res = await AuthAPI.me();
        if (!res.data?.id) throw new Error("no_user");
        this.setUserData(res.data);
      } catch {
        this.user = null;
      } finally {
        this.isHydrated = true;
      }
    },

    async logout(broadcast = true) {
      setLoggingOut(true);
      try {
        sessionStorage.setItem(STORAGE_KEYS.JUST_LOGGED_OUT, "true");
        if (broadcast) channel.postMessage("logout");

        const ssoToken = localStorage.getItem(STORAGE_KEYS.SSO_TOKEN);
        const idTokenHint = localStorage.getItem(STORAGE_KEYS.SSO_ID_TOKEN);

        if (ssoToken && idTokenHint) {
          try {
            await axios.post(
              `${import.meta.env.VITE_API_SSO_URL}/auth/sso/end_session`,
              {
                id_token_hint: idTokenHint,
                client_id: import.meta.env.VITE_SSO_CLIENT_ID,
              },
              {
                headers: { Authorization: `Bearer ${ssoToken}` },
                withCredentials: true,
              }
            );
          } catch {}
        }

        this.user = null;
        localStorage.removeItem(STORAGE_KEYS.SSO_TOKEN);
        localStorage.removeItem(STORAGE_KEYS.SSO_ID_TOKEN);
        await AuthAPI.logout();
      } catch {
      } finally {
        setLoggingOut(false);
      }
    },

    listenChannel() {
      channel.onmessage = async (event) => {
        if (event.data === "logout") {
          await this.logout(false);
        }
      };
    },
  },
});
