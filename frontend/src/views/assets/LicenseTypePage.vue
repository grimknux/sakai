<script setup>
import { licenseTypesApi } from '@/api/assets';
import { usePermissions } from '@/composables/usePermissions';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import { onMounted, reactive, ref } from 'vue';

const toast = useToast();
const dt = ref();

const loading = ref(false);
const saving = ref(false);
const { can } = usePermissions();

const licenseTypes = ref([]);
const licenseTypeDialog = ref(false);
const deleteLicenseTypeDialog = ref(false);
const selectedLicenseTypes = ref(null);
const deleteLicenseTypesDialog = ref(false);

const licenseType = ref({});

const errorMsg = ref('');
const errors = reactive({
    name: ''
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

async function load() {
    const res = await licenseTypesApi.list();
    licenseTypes.value = res.license_types || [];
}

onMounted(load);

function clearErrors() {
    errors.name = '';
    errorMsg.value = '';
}

function validate() {
    clearErrors();

    let ok = true;

    if (!licenseType.value.name?.trim()) {
        errors.name = 'Name is required.';
        ok = false;
    }

    return ok;
}

function openNew() {
    clearErrors();
    licenseType.value = {};
    licenseTypeDialog.value = true;
}

function hideDialog() {
    clearErrors();
    licenseTypeDialog.value = false;
}

function editLicenseType(row) {
    clearErrors();
    licenseType.value = { ...row };
    licenseTypeDialog.value = true;
}

function confirmDeleteLicenseType(row) {
    licenseType.value = row;
    deleteLicenseTypeDialog.value = true;
}

function confirmDeleteSelected() {
    deleteLicenseTypesDialog.value = true;
}

async function saveLicenseType() {
    if (!validate()) return;

    loading.value = true;
    saving.value = true;

    try {
        if (licenseType.value.id) {
            await licenseTypesApi.update(licenseType.value.id, {
                name: licenseType.value.name
            });
            toast.add({ severity: 'success', summary: 'Successful', detail: 'License Type Updated', life: 3000 });
        } else {
            await licenseTypesApi.create({
                name: licenseType.value.name
            });
            toast.add({ severity: 'success', summary: 'Successful', detail: 'License Type Created', life: 3000 });
        }

        licenseTypeDialog.value = false;
        licenseType.value = {};
        await load();
    } catch (err) {
        clearErrors();

        const status = err?.response?.status;
        const data = err?.response?.data || {};

        if (status === 422) {
            const fields = data?.messages?.fields || {};
            errors.name = fields.name || '';

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

async function deleteLicenseType() {
    try {
        await licenseTypesApi.remove(licenseType.value.id);
        toast.add({ severity: 'success', summary: 'Successful', detail: 'License Type Deleted', life: 3000 });
        deleteLicenseTypeDialog.value = false;
        licenseType.value = {};
        await load();
    } catch (e) {
        const msg = e?.response?.data?.message || e?.response?.data?.messages?.error || e?.response?.data?.messages?.message || e?.message || 'Error';

        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    }
}

async function deleteSelectedLicenseTypes() {
    const items = selectedLicenseTypes.value || [];
    try {
        for (const row of items) await licenseTypesApi.remove(row.id);
        toast.add({ severity: 'success', summary: 'Successful', detail: 'License Types Deleted', life: 3000 });
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Some deletes failed', life: 4000 });
    } finally {
        deleteLicenseTypesDialog.value = false;
        selectedLicenseTypes.value = null;
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
                <Button label="New" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" v-if="can('license_types.create')" />
                <Button label="Delete" icon="pi pi-trash" severity="secondary" @click="confirmDeleteSelected" :disabled="!selectedLicenseTypes || !selectedLicenseTypes.length" v-if="can('license_types.delete')" />
            </template>
            <template #end>
                <Button label="Export" icon="pi pi-upload" severity="secondary" @click="exportCSV" />
            </template>
        </Toolbar>

        <DataTable
            ref="dt"
            v-model:selection="selectedLicenseTypes"
            :value="licenseTypes"
            dataKey="id"
            :paginator="true"
            :rows="10"
            :filters="filters"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[5, 10, 25]"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} license types"
        >
            <template #header>
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <h4 class="m-0">Manage License Types</h4>
                    <IconField>
                        <InputIcon><i class="pi pi-search" /></InputIcon>
                        <InputText v-model="filters['global'].value" placeholder="Search..." />
                    </IconField>
                </div>
            </template>

            <Column selectionMode="multiple" style="width: 3rem" :exportable="false" v-if="can('license_types.delete')" />
            <Column field="name" header="Name" sortable style="min-width: 14rem" />
            <Column :exportable="false" style="min-width: 10rem" v-if="can('license_types.update') || can('license_types.delete')">
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editLicenseType(data)" v-if="can('license_types.update')" />
                    <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteLicenseType(data)" v-if="can('license_types.delete')" />
                </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="licenseTypeDialog" :style="{ width: '450px' }" header="License Type Details" :modal="true">
            <div class="flex flex-col gap-4">
                <Message v-if="errorMsg" severity="error">
                    {{ errorMsg }}
                </Message>

                <div>
                    <label class="block font-bold mb-2">Name</label>
                    <InputText v-model.trim="licenseType.name" :disabled="loading" :invalid="!!errors.name" fluid />
                    <small v-if="errors.name" class="text-red-500">{{ errors.name }}</small>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Save" icon="pi pi-check" :loading="saving" @click="saveLicenseType" />
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteLicenseTypeDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl!" />
                <span v-if="licenseType">
                    Are you sure you want to delete <b>{{ licenseType.name }}</b
                    >?
                </span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteLicenseTypeDialog = false" />
                <Button label="Yes" icon="pi pi-check" @click="deleteLicenseType" />
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteLicenseTypesDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl!" />
                <span>Are you sure you want to delete the selected license types?</span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteLicenseTypesDialog = false" />
                <Button label="Yes" icon="pi pi-check" text @click="deleteSelectedLicenseTypes" />
            </template>
        </Dialog>
    </div>
</template>
