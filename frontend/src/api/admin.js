import api from '@/lib/api';

// USERS
export const usersApi = {
    list: () => api.get('/admin/users').then((r) => r.data),
    create: (payload) => api.post('/admin/users', payload).then((r) => r.data),
    update: (id, payload) => api.put(`/admin/users/${id}`, payload).then((r) => r.data),
    remove: (id) => api.delete(`/admin/users/${id}`).then((r) => r.data),
    roles: (id) => api.get(`/admin/users/${id}/roles`).then((r) => r.data),
    setRoles: (id, roleIds) => api.put(`/admin/users/${id}/roles`, { role_ids: roleIds }).then((r) => r.data),
    setSuperadmin: (id, value) => api.put(`/admin/users/${id}/superadmin`, { is_superadmin: value ? 1 : 0 }).then((r) => r.data),

    unlock: (id) => api.post(`/admin/users/${id}/unlock`).then((r) => r.data)
};

// ROLES
export const rolesApi = {
    list: () => api.get('/admin/roles').then((r) => r.data),
    create: (payload) => api.post('/admin/roles', payload).then((r) => r.data),
    update: (id, payload) => api.put(`/admin/roles/${id}`, payload).then((r) => r.data),
    remove: (id) => api.delete(`/admin/roles/${id}`).then((r) => r.data),
    dropdown: () => api.get('/admin/roles/dropdown').then((r) => r.data)
};

// PERMISSIONS (viewer)
export const permsApi = {
    list: () => api.get('/admin/permissions').then((r) => r.data)
};

// ROLE PERMISSIONS
export const rolePermsApi = {
    get: (roleId) => api.get(`/admin/roles/${roleId}/permissions`).then((r) => r.data),
    set: (roleId, permIds) => api.put(`/admin/roles/${roleId}/permissions`, { permission_ids: permIds }).then((r) => r.data)
};

// AUDIT LOGS
export const auditApi = {
    list: () => api.get('/admin/audit-logs').then((r) => r.data)
};

// LOGIN ATTEMPTS / LOCKS
export const securityApi = {
    attempts: () => api.get('/admin/login-attempts').then((r) => r.data),
    unlockUser: (userId) => api.post(`/admin/users/${userId}/unlock`).then((r) => r.data)
};

export const sectionsApi = {
    list: () => api.get('/admin/org/sections').then((r) => r.data),
    create: (payload) => api.post('/admin/org/sections', payload).then((r) => r.data),
    update: (id, payload) => api.put(`/admin/org/sections/${id}`, payload).then((r) => r.data),
    remove: (id) => api.delete(`/admin/org/sections/${id}`).then((r) => r.data),

    dropdown: () => api.get('/admin/org/sections/dropdown').then((r) => r.data)
};

export const divisionsApi = {
    list: () => api.get('/admin/org/divisions').then((r) => r.data),
    create: (payload) => api.post('/admin/org/divisions', payload).then((r) => r.data),
    update: (id, payload) => api.put(`/admin/org/divisions/${id}`, payload).then((r) => r.data),
    remove: (id) => api.delete(`/admin/org/divisions/${id}`).then((r) => r.data),

    dropdown: () => api.get('/admin/org/divisions/dropdown').then((r) => r.data)
};

export const buildingsApi = {
    list: () => api.get('/admin/org/buildings').then((r) => r.data),
    create: (payload) => api.post('/admin/org/buildings', payload).then((r) => r.data),
    update: (id, payload) => api.put(`/admin/org/buildings/${id}`, payload).then((r) => r.data),
    remove: (id) => api.delete(`/admin/org/buildings/${id}`).then((r) => r.data),

    dropdown: () => api.get('/admin/org/buildings/dropdown').then((r) => r.data)
};
