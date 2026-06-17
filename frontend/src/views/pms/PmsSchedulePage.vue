<script setup>
import { pmsSchedulesApi } from '@/api/pms';
import { usePermissions } from '@/composables/usePermissions';
import { FilterMatchMode } from '@primevue/core/api';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, reactive, ref } from 'vue';

const toast = useToast();
const confirm = useConfirm();
const { can } = usePermissions();

const dt = ref();
const loading = ref(false);
const saving = ref(false);

const schedules = ref([]);
const selectedSchedules = ref(null);

const scheduleDialog = ref(false);

const schedule = ref({});
const attachmentFile = ref(null);

const errorMsg = ref('');
const errors = reactive({
    year: '',
    semester: '',
    schedule_start: '',
    schedule_end: '',
    attachment: '',
    remarks: ''
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

const currentYear = new Date().getFullYear();

const yearOptions = computed(() => {
    const years = [];
    for (let year = 2025; year <= currentYear; year++) {
        years.push({ label: String(year), value: year });
    }
    return years;
});

const semesterOptions = ref([
    { label: 'First', value: 'first' },
    { label: 'Second', value: 'second' }
]);

async function load() {
    const res = await pmsSchedulesApi.list();
    schedules.value = (res.schedules || res.items || []).map((row) => ({
        ...row,
        id: row.id
    }));
}

onMounted(async () => {
    await load();
});

function clearErrors() {
    errors.year = '';
    errors.semester = '';
    errors.schedule_start = '';
    errors.schedule_end = '';
    errors.attachment = '';
    errors.remarks = '';
    errorMsg.value = '';
}

function validate() {
    clearErrors();
    let ok = true;

    if (!schedule.value.year) {
        errors.year = 'Year is required.';
        ok = false;
    }

    if (!schedule.value.semester) {
        errors.semester = 'Semester is required.';
        ok = false;
    }

    if (!schedule.value.schedule_start) {
        errors.schedule_start = 'Schedule start is required.';
        ok = false;
    }

    if (!schedule.value.schedule_end) {
        errors.schedule_end = 'Schedule end is required.';
        ok = false;
    }

    if (schedule.value.schedule_start && schedule.value.schedule_end && schedule.value.schedule_end < schedule.value.schedule_start) {
        errors.schedule_end = 'Schedule end must be after or equal to schedule start.';
        ok = false;
    }

    if (attachmentFile.value) {
        const isPdf = attachmentFile.value.type === 'application/pdf';
        if (!isPdf) {
            errors.attachment = 'Only PDF files are allowed.';
            ok = false;
        }
    }

    return ok;
}

function openNew() {
    clearErrors();
    attachmentFile.value = null;
    schedule.value = {
        year: Number(currentYear),
        semester: '',
        schedule_start: '',
        schedule_end: '',
        remarks: '',
        attachment_name: ''
    };
    scheduleDialog.value = true;
}

function hideDialog() {
    clearErrors();
    attachmentFile.value = null;
    scheduleDialog.value = false;
    loading.value = false;
}

function editSchedule(row) {
    clearErrors();
    attachmentFile.value = null;
    schedule.value = {
        ...row,
        year: row.year ? Number(row.year) : ''
    };
    scheduleDialog.value = true;
}

function confirmDeleteSchedule(row) {
    confirm.require({
        message: `Are you sure you want to delete this PMS schedule?`,
        header: 'Confirm Delete',
        icon: 'pi pi-exclamation-triangle',
        group: 'delete',
        scheduleText: `${row.year} · ${row.semester} semester · ${row.schedule_start} to ${row.schedule_end}`,

        acceptLabel: 'Yes',
        rejectLabel: 'No',

        acceptProps: {
            severity: 'danger',
            icon: 'pi pi-check'
        },
        rejectProps: {
            severity: 'secondary',
            icon: 'pi pi-times'
        },
        accept: async () => {
            try {
                await pmsSchedulesApi.remove(row.id);
                toast.add({ severity: 'success', summary: 'Successful', detail: 'PMS schedule deleted', life: 3000 });
                await load();
            } catch (e) {
                const msg = e?.response?.data?.message || e?.response?.data?.messages?.message || e?.message || 'Error';
                toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
            }
        }
    });
}

function confirmDeleteSelected() {
    const items = selectedSchedules.value || [];
    if (!items.length) return;

    confirm.require({
        message: `Are you sure you want to delete the selected PMS schedules?`,
        header: 'Confirm Bulk Delete',
        icon: 'pi pi-exclamation-triangle',
        group: 'delete',

        acceptLabel: 'Yes',
        rejectLabel: 'No',

        acceptProps: {
            severity: 'danger',
            icon: 'pi pi-check'
        },
        rejectProps: {
            severity: 'secondary',
            icon: 'pi pi-times'
        },
        accept: async () => {
            try {
                for (const item of items) {
                    await pmsSchedulesApi.remove(item.id);
                }
                toast.add({ severity: 'success', summary: 'Successful', detail: 'Selected PMS schedules deleted', life: 3000 });
            } catch {
                toast.add({ severity: 'error', summary: 'Error', detail: 'Some deletes failed', life: 4000 });
            } finally {
                selectedSchedules.value = null;
                await load();
            }
        }
    });
}

function onFileSelect(event) {
    const files = event.files || [];
    attachmentFile.value = files.length ? files[0] : null;
}

async function saveSchedule() {
    if (!validate()) return;

    loading.value = true;
    saving.value = true;

    try {
        const formData = new FormData();
        formData.append('year', schedule.value.year);
        formData.append('semester', schedule.value.semester);
        formData.append('schedule_start', schedule.value.schedule_start);
        formData.append('schedule_end', schedule.value.schedule_end);
        formData.append('remarks', schedule.value.remarks || '');

        if (attachmentFile.value) {
            formData.append('attachment', attachmentFile.value);
        }

        if (schedule.value.id) {
            await pmsSchedulesApi.update(schedule.value.id, formData);
            toast.add({ severity: 'success', summary: 'Successful', detail: 'PMS schedule updated', life: 3000 });
        } else {
            await pmsSchedulesApi.create(formData);
            toast.add({ severity: 'success', summary: 'Successful', detail: 'PMS schedule created', life: 3000 });
        }

        scheduleDialog.value = false;
        schedule.value = {};
        attachmentFile.value = null;
        await load();
    } catch (err) {
        clearErrors();

        const status = err?.response?.status;
        const data = err?.response?.data || {};

        if (status === 422) {
            const fields = data?.messages?.fields || {};
            errors.year = fields.year || '';
            errors.semester = fields.semester || '';
            errors.schedule_start = fields.schedule_start || '';
            errors.schedule_end = fields.schedule_end || '';
            errors.attachment = fields.attachment || '';
            errors.remarks = fields.remarks || '';
            errorMsg.value = data?.messages?.error || 'Validation failed.';
            return;
        }

        const msg = data?.message || data?.messages?.error || err?.message || 'Error';
        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    } finally {
        loading.value = false;
        saving.value = false;
    }
}

function exportCSV() {
    dt.value.exportCSV();
}

function viewAttachment(row) {
    pmsSchedulesApi.view(row.id);
}
</script>

<template>
    <div class="card">
        <ConfirmDialog group="delete">
            <template #message="{ message }">
                <div>
                    <p class="mb-2">{{ message.message }}</p>
                    <p v-if="message.scheduleText" class="text-sm text-surface-500">
                        {{ message.scheduleText }}
                    </p>
                </div>
            </template>
        </ConfirmDialog>

        <Toolbar class="mb-6">
            <template #start>
                <Button label="New" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" v-if="can('pms.schedule.create')" />
                <Button label="Delete" icon="pi pi-trash" severity="secondary" @click="confirmDeleteSelected" :disabled="!selectedSchedules || !selectedSchedules.length" v-if="can('pms.schedule.delete')" />
            </template>

            <template #end>
                <Button label="Export" icon="pi pi-upload" severity="secondary" @click="exportCSV" />
            </template>
        </Toolbar>

        <DataTable
            ref="dt"
            v-model:selection="selectedSchedules"
            :value="schedules"
            dataKey="id"
            :paginator="true"
            :rows="10"
            :filters="filters"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[5, 10, 25]"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} items"
        >
            <template #header>
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <h4 class="m-0">Manage PMS Schedules</h4>
                    <IconField>
                        <InputIcon><i class="pi pi-search" /></InputIcon>
                        <InputText v-model="filters['global'].value" placeholder="Search..." />
                    </IconField>
                </div>
            </template>

            <Column selectionMode="multiple" style="width: 3rem" :exportable="false" v-if="can('pms.schedule.delete')" />

            <Column :exportable="false" header="Action" style="min-width: 12rem" v-if="can('pms.schedule.update') || can('pms.schedule.delete')">
                <template #body="{ data }">
                    <Button icon="pi pi-file" outlined rounded class="mr-2" @click="viewAttachment(data)" v-if="data.attachment_path" v-tooltip.top="'View Attachment'" />
                    <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editSchedule(data)" v-if="can('pms.schedule.update')" v-tooltip.top="'Edit Schedule'" />
                    <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteSchedule(data)" v-if="can('pms.schedule.delete')" v-tooltip.top="'Delete Schedule'" />
                </template>
            </Column>

            <Column field="year" header="Year" sortable style="min-width: 8rem" />
            <Column field="semester" header="Semester" sortable style="min-width: 10rem" />
            <Column field="schedule_start" header="Schedule Start" sortable style="min-width: 12rem" />
            <Column field="schedule_end" header="Schedule End" sortable style="min-width: 12rem" />
            <Column field="attachment_name" header="Attachment" sortable style="min-width: 16rem" />
            <Column field="remarks" header="Remarks" style="min-width: 18rem" />
        </DataTable>

        <Dialog v-model:visible="scheduleDialog" :style="{ width: '700px' }" header="PMS Schedule Details" :modal="true">
            <div class="flex flex-col gap-4">
                <div v-if="errorMsg" class="text-red-500 text-sm">
                    {{ errorMsg }}
                </div>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-4">
                        <label class="block font-bold mb-2">Year</label>
                        <Select v-model="schedule.year" :options="yearOptions" optionLabel="label" optionValue="value" placeholder="Select year" :disabled="loading" :invalid="!!errors.year" fluid />
                        <small v-if="errors.year" class="text-red-500">{{ errors.year }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <label class="block font-bold mb-2">Semester</label>
                        <Select v-model="schedule.semester" :options="semesterOptions" optionLabel="label" optionValue="value" placeholder="Select semester" :disabled="loading" :invalid="!!errors.semester" fluid />
                        <small v-if="errors.semester" class="text-red-500">{{ errors.semester }}</small>
                    </div>
                </div>
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">Schedule Start</label>
                        <InputText v-model="schedule.schedule_start" type="date" :disabled="loading" :invalid="!!errors.schedule_start" fluid />
                        <small v-if="errors.schedule_start" class="text-red-500">{{ errors.schedule_start }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">Schedule End</label>
                        <InputText v-model="schedule.schedule_end" type="date" :disabled="loading" :invalid="!!errors.schedule_end" fluid />
                        <small v-if="errors.schedule_end" class="text-red-500">{{ errors.schedule_end }}</small>
                    </div>
                </div>

                <div>
                    <label class="block font-bold mb-2">Attachment (PDF)</label>
                    <FileUpload mode="basic" name="attachment" accept="application/pdf" :maxFileSize="10485760" chooseLabel="Choose PDF" :auto="false" customUpload @select="onFileSelect" />
                    <small v-if="errors.attachment" class="text-red-500 block mt-1">{{ errors.attachment }}</small>
                    <small v-if="schedule.attachment_name" class="text-surface-500 block mt-1">Current file: {{ schedule.attachment_name }}</small>
                </div>

                <div>
                    <label class="block font-bold mb-2">Remarks</label>
                    <Textarea v-model="schedule.remarks" rows="4" :disabled="loading" fluid />
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Save" icon="pi pi-check" :loading="saving" @click="saveSchedule" />
            </template>
        </Dialog>
    </div>
</template>
