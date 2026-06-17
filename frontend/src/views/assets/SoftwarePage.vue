<script setup>
import { softwaresApi, softwareTypesApi } from '@/api/assets';
import { usePermissions } from '@/composables/usePermissions';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, reactive, ref } from 'vue';

const toast = useToast();
const dt = ref();

const loading = ref(false);
const saving = ref(false);
const { can } = usePermissions();

const softwares = ref([]);
const softwareTypeOptions = ref([]);

const softwareDialog = ref(false);
const deleteSoftwareDialog = ref(false);
const selectedSoftwares = ref(null);
const deleteSoftwaresDialog = ref(false);

const software = ref({});

const errorMsg = ref('');
const errors = reactive({
    software_type_id: '',
    name: '',
    version: '',
    status: ''
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

const softwareTypeMap = computed(() => {
    const map = {};
    for (const item of softwareTypeOptions.value) {
        map[item.id] = item.name;
    }
    return map;
});

async function load() {
    const [softwareRes, typeRes] = await Promise.all([softwaresApi.list(), softwareTypesApi.dropdown()]);

    softwares.value = (softwareRes.softwares || []).map((row) => ({
        ...row,
        software_type_id: row.software_type_id ? row.software_type_id : null,
        status: row.status == 1 || row.status === true
    }));

    softwareTypeOptions.value = (typeRes.software_types || []).map((row) => ({
        ...row,
        id: row.id
    }));
}

onMounted(load);

function clearErrors() {
    errors.software_type_id = '';
    errors.name = '';
    errors.version = '';
    errors.status = '';
    errorMsg.value = '';
}

function validate() {
    clearErrors();

    let ok = true;

    if (!software.value.software_type_id) {
        errors.software_type_id = 'Software type is required.';
        ok = false;
    }

    if (!software.value.name?.trim()) {
        errors.name = 'Name is required.';
        ok = false;
    }

    return ok;
}

function openNew() {
    clearErrors();
    software.value = {
        software_type_id: null,
        name: '',
        version: '',
        status: true
    };
    softwareDialog.value = true;
}

function hideDialog() {
    clearErrors();
    softwareDialog.value = false;
}

function editSoftware(row) {
    clearErrors();
    software.value = {
        ...row,
        software_type_id: row.software_type_id ? row.software_type_id : null,
        status: row.status == 1 || row.status === true
    };
    softwareDialog.value = true;
}

function confirmDeleteSoftware(row) {
    software.value = row;
    deleteSoftwareDialog.value = true;
}

function confirmDeleteSelected() {
    deleteSoftwaresDialog.value = true;
}

async function saveSoftware() {
    if (!validate()) return;

    loading.value = true;
    saving.value = true;

    try {
        const payload = {
            software_type_id: software.value.software_type_id,
            name: software.value.name,
            version: software.value.version ?? '',
            status: software.value.status ? 1 : 0
        };

        if (software.value.id) {
            await softwaresApi.update(software.value.id, payload);
            toast.add({ severity: 'success', summary: 'Successful', detail: 'Software Updated', life: 3000 });
        } else {
            await softwaresApi.create(payload);
            toast.add({ severity: 'success', summary: 'Successful', detail: 'Software Created', life: 3000 });
        }

        softwareDialog.value = false;
        software.value = {};
        await load();
    } catch (err) {
        clearErrors();

        const status = err?.response?.status;
        const data = err?.response?.data || {};

        if (status === 422) {
            const fields = data?.messages?.fields || {};
            errors.software_type_id = fields.software_type_id || '';
            errors.name = fields.name || '';
            errors.version = fields.version || '';
            errors.status = fields.status || '';

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

async function deleteSoftware() {
    try {
        await softwaresApi.remove(software.value.id);
        toast.add({ severity: 'success', summary: 'Successful', detail: 'Software Deleted', life: 3000 });
        deleteSoftwareDialog.value = false;
        software.value = {};
        await load();
    } catch (e) {
        const msg = e?.response?.data?.message || e?.response?.data?.messages?.error || e?.response?.data?.messages?.message || e?.message || 'Error';

        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    }
}

async function deleteSelectedSoftwares() {
    const items = selectedSoftwares.value || [];
    try {
        for (const row of items) await softwaresApi.remove(row.id);
        toast.add({ severity: 'success', summary: 'Successful', detail: 'Softwares Deleted', life: 3000 });
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Some deletes failed', life: 4000 });
    } finally {
        deleteSoftwaresDialog.value = false;
        selectedSoftwares.value = null;
        await load();
    }
}

function exportCSV() {
    dt.value.exportCSV();
}
</script>

<template>
    <div class="card">
        <Toolbar class="mb-6">
            <template #start>
                <Button label="New" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" v-if="can('softwares.create')" />
                <Button label="Delete" icon="pi pi-trash" severity="secondary" @click="confirmDeleteSelected" :disabled="!selectedSoftwares || !selectedSoftwares.length" v-if="can('softwares.delete')" />
            </template>
            <template #end>
                <Button label="Export" icon="pi pi-upload" severity="secondary" @click="exportCSV" />
            </template>
        </Toolbar>

        <DataTable
            ref="dt"
            v-model:selection="selectedSoftwares"
            :value="softwares"
            dataKey="id"
            :paginator="true"
            :rows="10"
            :filters="filters"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[5, 10, 25]"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} software records"
        >
            <template #header>
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <h4 class="m-0">Manage Softwares</h4>
                    <IconField>
                        <InputIcon><i class="pi pi-search" /></InputIcon>
                        <InputText v-model="filters['global'].value" placeholder="Search..." />
                    </IconField>
                </div>
            </template>

            <Column selectionMode="multiple" style="width: 3rem" :exportable="false" v-if="can('softwares.delete')" />
            <Column header="Software Type" sortable style="min-width: 14rem">
                <template #body="{ data }">
                    {{ softwareTypeMap[data.software_type_id] || data.software_type_id }}
                </template>
            </Column>
            <Column field="name" header="Name" sortable style="min-width: 16rem" />
            <Column field="version" header="Version" sortable style="min-width: 12rem" />
            <Column field="status" header="Active" sortable style="min-width: 10rem">
                <template #body="{ data }">
                    <Tag :value="data.status ? 'YES' : 'NO'" :severity="data.status ? 'success' : 'danger'" />
                </template>
            </Column>
            <Column :exportable="false" style="min-width: 10rem" v-if="can('softwares.update') || can('softwares.delete')">
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editSoftware(data)" v-if="can('softwares.update')" />
                    <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteSoftware(data)" v-if="can('softwares.delete')" />
                </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="softwareDialog" :style="{ width: '500px' }" header="Software Details" :modal="true">
            <div class="flex flex-col gap-4">
                <Message v-if="errorMsg" severity="error">
                    {{ errorMsg }}
                </Message>

                <div>
                    <label class="block font-bold mb-2">Software Type</label>
                    <Select v-model="software.software_type_id" :options="softwareTypeOptions" optionLabel="name" optionValue="id" placeholder="Select software type" :disabled="loading" :invalid="!!errors.software_type_id" fluid />
                    <small v-if="errors.software_type_id" class="text-red-500">{{ errors.software_type_id }}</small>
                </div>

                <div>
                    <label class="block font-bold mb-2">Name</label>
                    <InputText v-model.trim="software.name" :disabled="loading" :invalid="!!errors.name" fluid />
                    <small v-if="errors.name" class="text-red-500">{{ errors.name }}</small>
                </div>

                <div>
                    <label class="block font-bold mb-2">Version</label>
                    <InputText v-model.trim="software.version" :disabled="loading" :invalid="!!errors.version" fluid />
                    <small v-if="errors.version" class="text-red-500">{{ errors.version }}</small>
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox v-model="software.status" :disabled="loading" :binary="true" />
                    <label>Active</label>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Save" icon="pi pi-check" :loading="saving" @click="saveSoftware" />
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteSoftwareDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl!" />
                <span v-if="software">
                    Are you sure you want to delete <b>{{ software.name }}</b
                    >?
                </span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteSoftwareDialog = false" />
                <Button label="Yes" icon="pi pi-check" @click="deleteSoftware" />
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteSoftwaresDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl!" />
                <span>Are you sure you want to delete the selected software records?</span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteSoftwaresDialog = false" />
                <Button label="Yes" icon="pi pi-check" text @click="deleteSelectedSoftwares" />
            </template>
        </Dialog>
    </div>
</template>
