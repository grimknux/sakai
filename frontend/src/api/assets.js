import api from '@/lib/api';

// INVENTORY
export const inventoryApi = {
    list: () => api.get('/assets/inventory').then((r) => r.data),
    dropdown: () => api.get('/assets/inventory/dropdown').then((r) => r.data),
    dropdownAvailableForPmsSchedule: (id) => api.get(`/assets/inventory/dropdown-available-pms/${id}`).then((r) => r.data),
    create: (payload) => api.post('/assets/inventory', payload).then((r) => r.data),
    update: (id, payload) => api.put(`/assets/inventory/${id}`, payload).then((r) => r.data),
    remove: (id) => api.delete(`/assets/inventory/${id}`).then((r) => r.data),

    syncPms: (id, payload) => api.post(`/assets/inventory/${id}/sync-location-from-pms`, payload).then((r) => r.data),
    updateLocation: (id, payload) => api.put(`/assets/inventory/${id}/location`, payload).then((r) => r.data),

    deviceTypes: () => api.get('/assets/device-types/dropdown').then((r) => r.data),
    softwareList: () => api.get('/assets/softwares/dropdown').then((r) => r.data),
    licenseType: () => api.get('/assets/license-types/dropdown').then((r) => r.data),

    softwareHistory: (id) => api.get(`/assets/inventory/${id}/software`).then((r) => r.data),
    addSoftware: (id, payload) => api.post(`/assets/inventory/${id}/software`, payload).then((r) => r.data),
    updateSoftware: (id, historyId, payload) => api.put(`/assets/inventory/${id}/software/${historyId}`, payload).then((r) => r.data),
    deleteSoftware: (id, historyId) => api.delete(`/assets/inventory/${id}/software/${historyId}`).then((r) => r.data)
};

// SOFTWARES
export const softwaresApi = {
    list: () => api.get('/assets/softwares').then((r) => r.data),
    create: (payload) => api.post('/assets/softwares', payload).then((r) => r.data),
    update: (id, payload) => api.put(`/assets/softwares/${id}`, payload).then((r) => r.data),
    remove: (id) => api.delete(`/assets/softwares/${id}`).then((r) => r.data),

    dropdown: () => api.get('/assets/softwares/dropdown').then((r) => r.data)
};

// DEVICE TYPES
export const deviceTypesApi = {
    list: () => api.get('/assets/device-types').then((r) => r.data),
    create: (payload) => api.post('/assets/device-types', payload).then((r) => r.data),
    update: (id, payload) => api.put(`/assets/device-types/${id}`, payload).then((r) => r.data),
    remove: (id) => api.delete(`/assets/device-types/${id}`).then((r) => r.data),

    dropdown: () => api.get('/assets/device-types/dropdown').then((r) => r.data)
};

// SOFTWARE TYPES
export const softwareTypesApi = {
    list: () => api.get('/assets/software-types').then((r) => r.data),
    create: (payload) => api.post('/assets/software-types', payload).then((r) => r.data),
    update: (id, payload) => api.put(`/assets/software-types/${id}`, payload).then((r) => r.data),
    remove: (id) => api.delete(`/assets/software-types/${id}`).then((r) => r.data),

    dropdown: () => api.get('/assets/software-types/dropdown').then((r) => r.data)
};

// LICENSE TYPES
export const licenseTypesApi = {
    list: () => api.get('/assets/license-types').then((r) => r.data),
    create: (payload) => api.post('/assets/license-types', payload).then((r) => r.data),
    update: (id, payload) => api.put(`/assets/license-types/${id}`, payload).then((r) => r.data),
    remove: (id) => api.delete(`/assets/license-types/${id}`).then((r) => r.data)
};
