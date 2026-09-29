import axios from 'axios';

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '/api';

const api = axios.create({
    baseURL: API_BASE_URL,
    withCredentials: true, // ✅ REQUIRED for session-cookie auth
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json'
    }
});

api.interceptors.request.use(async (config) => {
    const method = (config.method || 'get').toLowerCase();
    const isSafe = ['get', 'head', 'options'].includes(method);

    // Every state-changing request needs a CSRF token, including pre-login ones
    // (forgot/reset password), so fetch one if we don't have it yet.
    if (!isSafe && !localStorage.getItem('csrfToken')) {
        const res = await axios.get(`${API_BASE_URL}/auth/csrf`, { withCredentials: true });
        localStorage.setItem('csrfToken', res.data.csrfToken);
    }

    const csrf = localStorage.getItem('csrfToken');
    if (csrf) config.headers['X-CSRF-TOKEN'] = csrf;
    return config;
});

// ✅ If CSRF fails, refresh token once then retry request
api.interceptors.response.use(
    (res) => res,
    async (err) => {
        const status = err?.response?.status;
        const method = (err?.config?.method || '').toLowerCase();
        const errorData = err?.response?.data || {};
        const message = errorData?.message || errorData?.messages?.error || '';

        if (status === 403 && method !== 'get' && !err.config?._csrfRetry && /csrf|action you requested is not allowed/i.test(message)) {
            err.config._csrfRetry = true;

            try {
                const csrfRes = await api.get('/auth/csrf');

                localStorage.setItem('csrfToken', csrfRes.data.csrfToken);

                err.config.headers = err.config.headers || {};
                err.config.headers['X-CSRF-TOKEN'] = csrfRes.data.csrfToken;

                return api.request(err.config);
            } catch (e) {
                // ignore and fall through
            }
        }

        return Promise.reject(err);
    }
);

export default api;
