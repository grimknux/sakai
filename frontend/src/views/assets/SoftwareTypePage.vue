<script setup>
import { softwareTypesApi } from '@/api/assets';
import { usePermissions } from '@/composables/usePermissions';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import { onMounted, reactive, ref } from 'vue';

const toast = useToast();
const dt = ref();

const loading = ref(false);
const saving = ref(false);
const { can } = usePermissions();

const softwareTypes = ref([]);
const softwareTypeDialog = ref(false);
const deleteSoftwareTypeDialog = ref(false);
const selectedSoftwareTypes = ref(null);
const deleteSoftwareTypesDialog = ref(false);

const softwareType = ref({});

const errorMsg = ref('');
const errors = reactive({
    name: '',
    shortname: ''
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

async function load() {
    const res = await softwareTypesApi.list();
    softwareTypes.value = res.software_types || [];
}

onMounted(load);

function clearErrors() {
    errors.name = '';
    errors.shortname = '';
    errorMsg.value = '';
}

function validate() {
    clearErrors();

    let ok = true;

    if (!softwareType.value.name?.trim()) {
        errors.name = 'Name is required.';
        ok = false;
    }

    if (!softwareType.value.shortname?.trim()) {
        errors.shortname = 'Shortname is required.';
        ok = false;
    }

    return ok;
}

function openNew() {
    clearErrors();
    softwareType.value = {};
    softwareTypeDialog.value = true;
}

function hideDialog() {
    clearErrors();
    softwareTypeDialog.value = false;
}

function editSoftwareType(row) {
    clearErrors();
    softwareType.value = { ...row };
    softwareTypeDialog.value = true;
}

function confirmDeleteSoftwareType(row) {
    softwareType.value = row;
    deleteSoftwareTypeDialog.value = true;
}

function confirmDeleteSelected() {
    deleteSoftwareTypesDialog.value = true;
}

async function saveSoftwareType() {
    if (!validate()) return;

    loading.value = true;
    saving.value = true;

    try {
        if (softwareType.value.id) {
            await softwareTypesApi.update(softwareType.value.id, {
                name: softwareType.value.name,
                shortname: softwareType.value.shortname
            });
            toast.add({ severity: 'success', summary: 'Successful', detail: 'Software Type Updated', life: 3000 });
        } else {
            await softwareTypesApi.create({
                name: softwareType.value.name,
                shortname: softwareType.value.shortname
            });
            toast.add({ severity: 'success', summary: 'Successful', detail: 'Software Type Created', life: 3000 });
        }

        softwareTypeDialog.value = false;
        softwareType.value = {};
        await load();
    } catch (err) {
        clearErrors();

        const status = err?.response?.status;
        const data = err?.response?.data || {};

        if (status === 422) {
            const fields = data?.messages?.fields || {};
            errors.name = fields.name || '';
            errors.shortname = fields.shortname || '';

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

async function deleteSoftwareType() {
    try {
        await softwareTypesApi.remove(softwareType.value.id);
        toast.add({ severity: 'success', summary: 'Successful', detail: 'Software Type Deleted', life: 3000 });
        deleteSoftwareTypeDialog.value = false;
        softwareType.value = {};
        await load();
    } catch (e) {
        const msg = e?.response?.data?.message || e?.response?.data?.messages?.error || e?.response?.data?.messages?.message || e?.message || 'Error';

        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    }
}

async function deleteSelectedSoftwareTypes() {
    const items = selectedSoftwareTypes.value || [];
    try {
        for (const row of items) await softwareTypesApi.remove(row.id);
        toast.add({ severity: 'success', summary: 'Successful', detail: 'Software Types Deleted', life: 3000 });
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Some deletes failed', life: 4000 });
    } finally {
        deleteSoftwareTypesDialog.value = false;
        selectedSoftwareTypes.value = null;
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
                <Button label="New" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" v-if="can('software_types.create')" />
                <Button label="Delete" icon="pi pi-trash" severity="secondary" @click="confirmDeleteSelected" :disabled="!selectedSoftwareTypes || !selectedSoftwareTypes.length" v-if="can('software_types.delete')" />
            </template>
            <template #end>
                <Button label="Export" icon="pi pi-upload" severity="secondary" @click="exportCSV" />
            </template>
        </Toolbar>

        <DataTable
            ref="dt"
            v-model:selection="selectedSoftwareTypes"
            :value="softwareTypes"
            dataKey="id"
            :paginator="true"
            :rows="10"
            :filters="filters"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[5, 10, 25]"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} software types"
        >
            <template #header>
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <h4 class="m-0">Manage Software Types</h4>
                    <IconField>
                        <InputIcon><i class="pi pi-search" /></InputIcon>
                        <InputText v-model="filters['global'].value" placeholder="Search..." />
                    </IconField>
                </div>
            </template>

            <Column selectionMode="multiple" style="width: 3rem" :exportable="false" v-if="can('software_types.delete')" />
            <Column field="name" header="Name" sortable style="min-width: 14rem" />
            <Column field="shortname" header="Shortname" sortable style="min-width: 14rem" />
            <Column :exportable="false" style="min-width: 10rem" v-if="can('software_types.update') || can('software_types.delete')">
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editSoftwareType(data)" v-if="can('software_types.update')" />
                    <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteSoftwareType(data)" v-if="can('software_types.delete')" />
                </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="softwareTypeDialog" :style="{ width: '450px' }" header="Software Type Details" :modal="true">
            <div class="flex flex-col gap-4">
                <Message v-if="errorMsg" severity="error">
                    {{ errorMsg }}
                </Message>

                <div>
                    <label class="block font-bold mb-2">Name</label>
                    <InputText v-model.trim="softwareType.name" :disabled="loading" :invalid="!!errors.name" fluid />
                    <small v-if="errors.name" class="text-red-500">{{ errors.name }}</small>
                </div>
                <div>
                    <label class="block font-bold mb-2">Shortname</label>
                    <InputText v-model.trim="softwareType.shortname" :disabled="loading" :invalid="!!errors.shortname" fluid />
                    <small v-if="errors.shortname" class="text-red-500">{{ errors.shortname }}</small>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Save" icon="pi pi-check" :loading="saving" @click="saveSoftwareType" />
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteSoftwareTypeDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl!" />
                <span v-if="softwareType">
                    Are you sure you want to delete <b>{{ softwareType.name }}</b
                    >?
                </span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteSoftwareTypeDialog = false" />
                <Button label="Yes" icon="pi pi-check" @click="deleteSoftwareType" />
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteSoftwareTypesDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl!" />
                <span>Are you sure you want to delete the selected software types?</span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteSoftwareTypesDialog = false" />
                <Button label="Yes" icon="pi pi-check" text @click="deleteSelectedSoftwareTypes" />
            </template>
        </Dialog>
    </div>
</template>
