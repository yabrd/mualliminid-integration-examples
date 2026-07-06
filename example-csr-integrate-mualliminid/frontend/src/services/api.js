import axios from 'axios';
import { STORAGE_KEYS } from '@/utils/constants';

const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  }
});

apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem(STORAGE_KEYS.SSO_TOKEN);
  if (token) {
    config.headers['Authorization'] = `Bearer ${token}`;
  }
  return config;
});

let isRefreshing = false;
let failedQueue = [];
let _isLoggingOut = false;

export const setLoggingOut = (v) => {
  _isLoggingOut = v;
};

const processQueue = (error, token = null) => {
  failedQueue.forEach((prom) => {
    if (error) {
      prom.reject(error);
    } else {
      prom.resolve(token);
    }
  });
  failedQueue = [];
};

apiClient.interceptors.response.use(
  (res) => res,
  async (err) => {
    const originalRequest = err.config;

    if (err.response?.status === 401 && localStorage.getItem(STORAGE_KEYS.SSO_TOKEN) && !_isLoggingOut && !originalRequest._retry) {
      if (isRefreshing) {
        return new Promise((resolve, reject) => {
          failedQueue.push({ resolve, reject });
        })
          .then((token) => {
            originalRequest.headers['Authorization'] = `Bearer ${token}`;
            return apiClient(originalRequest);
          })
          .catch((error) => Promise.reject(error));
      }

      originalRequest._retry = true;
      isRefreshing = true;

      try {
        const response = await axios.post(
          `${import.meta.env.VITE_API_SSO_URL}/auth/sso/token`,
          {
            grant_type: 'refresh_token',
            client_id: import.meta.env.VITE_SSO_CLIENT_ID,
          },
          {
            withCredentials: true,
          }
        );

        const newAccessToken = response.data?.data?.access_token;
        if (!newAccessToken) {
          throw new Error('Refresh token invalid');
        }

        localStorage.setItem(STORAGE_KEYS.SSO_TOKEN, newAccessToken);
        processQueue(null, newAccessToken);

        originalRequest.headers['Authorization'] = `Bearer ${newAccessToken}`;
        return apiClient(originalRequest);
      } catch (refreshError) {
        processQueue(refreshError, null);
        localStorage.removeItem(STORAGE_KEYS.SSO_TOKEN);
        localStorage.removeItem(STORAGE_KEYS.SSO_ID_TOKEN);
        import('../router')
          .then((m) => {
            const router = m.default;
            if (router.currentRoute.value.name !== 'Login') {
              router.push({ name: 'Login' });
            }
          })
          .catch(() => {});
        return Promise.reject(refreshError);
      } finally {
        isRefreshing = false;
      }
    }

    if (err.response?.status === 401 && _isLoggingOut) {
      return Promise.reject(err);
    }

    let msg = "Terjadi kesalahan pada server";

    if (err.response?.data) {
      const data = err.response.data;
      msg = data.message ? data.message : msg;

      if (data.errors && Array.isArray(data.errors) && data.errors.length > 0) {
        const detailMsgs = data.errors
          .map((e) => {
            if (typeof e === "string") return e;
            return e.message ? e.message : (e.msg ? e.msg : JSON.stringify(e));
          })
          .filter(Boolean);
        if (detailMsgs.length > 0) {
          if (msg === "Validasi gagal" || msg === "Bad Request") {
            msg = detailMsgs.join(", ");
          } else {
            msg = msg + ": " + detailMsgs.join(", ");
          }
        }
      }
    } else if (err.message) {
      msg = err.message;
    }

    const customError = new Error(msg);
    customError.response = err.response;
    customError.original = err;
    return Promise.reject(customError);
  }
);

const unwrap = (res) => {
  const body = res.data;
  if (body?.status === 'success') {
    return body;
  }
  if (body?.success) {
    return body;
  }
  let msg = 'Terjadi kesalahan';
  if (body?.message) {
    msg = body.message;
  }
  const error = new Error(msg);
  error.response = res;
  throw error;
};

const apiCall = async (endpoint, { method = 'GET', data, params, headers } = {}) => {
  if (_isLoggingOut) {
    return { data: null };
  }
  const res = await apiClient({ url: endpoint, method, data, params, headers });
  return unwrap(res);
};

export const AuthAPI = {
  me: () => apiCall('/auth/me'),
  logout: () => apiClient.post('/auth/logout').catch(() => {})
};

export const UserAPI = {
  getAll: (params) => apiCall('/users', { params }),
  sync: () => apiCall('/users/sync', { method: 'POST' })
};

export default apiClient;
