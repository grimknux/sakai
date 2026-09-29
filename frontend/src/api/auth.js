import api from '@/lib/api';

export async function getCsrf() {
    const res = await api.get('/auth/csrf');
    const token = res.data.csrfToken;
    localStorage.setItem('csrfToken', token);
    return token;
}

export async function login(username, password) {
    // ✅ ensure CSRF token exists before POST
    if (!localStorage.getItem('csrfToken')) {
        await getCsrf();
    }
    const res = await api.post('/auth/login', { username, password });
    return res.data;
}

export async function me() {
    const res = await api.get('/auth/me');
    return res.data;
}

export async function logout() {
    if (!localStorage.getItem('csrfToken')) {
        await getCsrf();
    }
    const res = await api.post('/auth/logout');
    // Session (and its CSRF secret) is destroyed server-side; drop the stale token.
    localStorage.removeItem('csrfToken');
    return res.data;
}

export async function requestPasswordReset(email) {
    const res = await api.post('/auth/forgot-password', { email });
    return res.data;
}

// ✅ NEW: reset password using token
export async function resetPassword(token, password, password_confirm) {
    const res = await api.post('/auth/reset-password', {
        token,
        password,
        password_confirm
    });
    return res.data;
}

export async function changePassword({ current_password, new_password, confirm_password }) {
    const res = await api.post('/auth/change-password', {
        current_password,
        new_password,
        confirm_password
    });
    return res.data;
}
