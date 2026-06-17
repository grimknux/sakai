import api from '@/lib/api';

export const pmsRecordsApi = {
    list: (params = {}) => api.get('/pms/pms-records', { params }).then((r) => r.data),
    create: (payload) => api.post('/pms/pms-records', payload).then((r) => r.data),
    update: (id, payload) => api.put(`/pms/pms-records/${id}`, payload).then((r) => r.data),
    remove: (id) => api.delete(`/pms/pms-records/${id}`).then((r) => r.data),
    answers: (id) => api.get(`/pms/pms-records/${id}/answers`).then((r) => r.data),
    updateAnswers: (id, payload) => api.put(`/pms/pms-records/${id}/answers`, payload).then((r) => r.data),
    clearAnswers: (id) => api.delete(`/pms/pms-records/${id}/answers`).then((r) => r.data),
    view: (id) => window.open(`/viewer/pms/pms-records/${id}/view-pdf`, '_blank'),
    view_bulk: (params = {}) => {
        const query = new URLSearchParams(params).toString();
        const url = `/viewer/pms/pms-records/bulk${query ? `?${query}` : ''}`;
        window.open(url, '_blank');
    }
};

export const pmsReportsApi = {
    list: (params = {}) => api.get('/report/pms-records', { params }).then((r) => r.data),
    pdf: (params) => {
        const query = new URLSearchParams(params).toString();
        const url = `/viewer/report/pms-records${query ? `?${query}` : ''}`;
        window.open(url, '_blank');
    }
};

export const pmsQuestionsApi = {
    list: () => api.get('/pms/pms-questions').then((r) => r.data),
    create: (payload) => api.post('/pms/pms-questions', payload).then((r) => r.data),
    update: (id, payload) => api.put(`/pms/pms-questions/${id}`, payload).then((r) => r.data),
    remove: (id) => api.delete(`/pms/pms-questions/${id}`).then((r) => r.data),
    active: () => api.get(`/pms/pms-questions/active`).then((r) => r.data)
};

export const pmsSchedulesApi = {
    list: () => api.get('/pms/pms-schedules').then((r) => r.data),
    create: (formData) =>
        api
            .post('/pms/pms-schedules', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
            .then((r) => r.data),

    update: (id, formData) =>
        api
            .post(`/pms/pms-schedules/${id}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
            .then((r) => r.data),
    remove: (id) => api.delete(`/pms/pms-schedules/${id}`).then((r) => r.data),
    view: (id) => window.open(`/viewer/pms/pms-schedules/${id}/view-attachment`, '_blank'),
    dropdown: () => api.get('/pms/pms-schedules/dropdown').then((r) => r.data)
};
