<script setup>
import { rolesApi } from '@/api/admin';
import { usePermissions } from '@/composables/usePermissions';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import { onMounted, reactive, ref } from 'vue';

const toast = useToast();
const dt = ref();
const loading = ref(false);
const { can } = usePermissions();

const saving = ref(false);

const roles = ref([]);
const roleDialog = ref(false);
const deleteRoleDialog = ref(false);
const selectedRoles = ref(null);
const deleteRolesDialog = ref(false);

const role = ref({});

const errorMsg = ref('');
const errors = reactive({
    name: '',
    slug: '',
    description: ''
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

async function load() {
    const res = await rolesApi.list();
    roles.value = res.roles || [];
}

onMounted(load);

function clearErrors() {
    errors.name = '';
    errors.slug = '';
    errors.description = '';
    errorMsg.value = '';
}

function validate() {
    clearErrors();

    let ok = true;

    if (!role.value.name?.trim()) {
        errors.name = 'Name is required.';
        ok = false;
    }

    if (!role.value.slug?.trim()) {
        errors.slug = 'Slug is required.';
        ok = false;
    }

    // optional: basic slug format validation (letters/numbers/dash/underscore)
    // if (role.value.slug?.trim() && !/^[a-z0-9-_]+$/i.test(role.value.slug.trim())) {
    //     errors.slug = 'Slug must contain only letters, numbers, dash (-), or underscore (_).';
    //     ok = false;
    // }

    return ok;
}

function openNew() {
    clearErrors();
    role.value = {};
    roleDialog.value = true;
}

function hideDialog() {
    clearErrors();
    roleDialog.value = false;
}

function editRole(r) {
    clearErrors();
    role.value = { ...r };
    roleDialog.value = true;
}

function confirmDeleteRole(r) {
    role.value = r;
    deleteRoleDialog.value = true;
}

function confirmDeleteSelected() {
    deleteRolesDialog.value = true;
}

async function saveRole() {
    if (!validate()) return;

    loading.value = true;
    saving.value = true;

    try {
        if (role.value.id) {
            await rolesApi.update(role.value.id, {
                name: role.value.name,
                slug: role.value.slug,
                description: role.value.description ?? ''
            });
            toast.add({ severity: 'success', summary: 'Successful', detail: 'Role Updated', life: 3000 });
        } else {
            await rolesApi.create({
                name: role.value.name,
                slug: role.value.slug,
                description: role.value.description ?? ''
            });
            toast.add({ severity: 'success', summary: 'Successful', detail: 'Role Created', life: 3000 });
        }

        roleDialog.value = false;
        role.value = {};
        await load();
    } catch (err) {
        // keep frontend errors cleared before applying backend ones
        clearErrors();

        const status = err?.response?.status;
        const data = err?.response?.data || {};

        // ✅ backend validation
        if (status === 422) {
            const fields = data?.messages?.fields || {};
            errors.name = fields.name || '';
            errors.slug = fields.slug || '';
            errors.description = fields.description || '';

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

async function deleteRole() {
    try {
        await rolesApi.remove(role.value.id);
        toast.add({ severity: 'success', summary: 'Successful', detail: 'Role Deleted', life: 3000 });
        deleteRoleDialog.value = false;
        role.value = {};
        await load();
    } catch (e) {
        const msg = e?.response?.data?.message || e?.response?.data?.messages?.error || e?.response?.data?.messages?.message || e?.message || 'Error';

        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    }
}

async function deleteSelectedRoles() {
    const items = selectedRoles.value || [];
    try {
        for (const r of items) await rolesApi.remove(r.id);
        toast.add({ severity: 'success', summary: 'Successful', detail: 'Roles Deleted', life: 3000 });
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Some deletes failed', life: 4000 });
    } finally {
        deleteRolesDialog.value = false;
        selectedRoles.value = null;
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
                <Button label="New" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" v-if="can('roles.create')" />
                <Button label="Delete" icon="pi pi-trash" severity="secondary" @click="confirmDeleteSelected" :disabled="!selectedRoles || !selectedRoles.length" v-if="can('roles.delete')" />
            </template>
            <template #end>
                <Button label="Export" icon="pi pi-upload" severity="secondary" @click="exportCSV" />
            </template>
        </Toolbar>

        <DataTable
            ref="dt"
            v-model:selection="selectedRoles"
            :value="roles"
            dataKey="id"
            :paginator="true"
            :rows="10"
            :filters="filters"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[5, 10, 25]"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} roles"
        >
            <template #header>
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <h4 class="m-0">Manage Roles</h4>
                    <IconField>
                        <InputIcon><i class="pi pi-search" /></InputIcon>
                        <InputText v-model="filters['global'].value" placeholder="Search..." />
                    </IconField>
                </div>
            </template>

            <Column selectionMode="multiple" style="width: 3rem" :exportable="false" v-if="can('roles.delete')" />
            <Column field="name" header="Name" sortable style="min-width: 14rem" />
            <Column field="slug" header="Slug" sortable style="min-width: 14rem" />
            <Column field="description" header="Description" style="min-width: 18rem" />
            <Column :exportable="false" style="min-width: 10rem" v-if="can('roles.delete') || can('roles.update')">
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editRole(data)" v-if="can('roles.update')" />
                    <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteRole(data)" v-if="can('roles.delete')" />
                </template>
            </Column>
        </DataTable>

        <!-- Create/Edit Role -->
        <Dialog v-model:visible="roleDialog" :style="{ width: '450px' }" header="Role Details" :modal="true">
            <div class="flex flex-col gap-4">
                <Message v-if="errorMsg" severity="error">
                    {{ errorMsg }}
                </Message>

                <div>
                    <label class="block font-bold mb-2">Name</label>
                    <InputText v-model.trim="role.name" :disabled="loading" :invalid="!!errors.name" fluid />
                    <small v-if="errors.name" class="text-red-500">{{ errors.name }}</small>
                </div>

                <div>
                    <label class="block font-bold mb-2">Slug</label>
                    <InputText v-model.trim="role.slug" :disabled="loading" :invalid="!!errors.slug" fluid />
                    <small v-if="errors.slug" class="text-red-500">{{ errors.slug }}</small>
                </div>

                <div>
                    <label class="block font-bold mb-2">Description</label>
                    <Textarea v-model="role.description" :disabled="loading" rows="3" fluid />
                    <small v-if="errors.description" class="text-red-500">{{ errors.description }}</small>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Save" icon="pi pi-check" :loading="saving" @click="saveRole" />
            </template>
        </Dialog>

        <!-- Delete single -->
        <Dialog v-model:visible="deleteRoleDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl!" />
                <span v-if="role"
                    >Are you sure you want to delete <b>{{ role.name }}</b
                    >?</span
                >
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteRoleDialog = false" />
                <Button label="Yes" icon="pi pi-check" @click="deleteRole" />
            </template>
        </Dialog>

        <!-- Delete selected -->
        <Dialog v-model:visible="deleteRolesDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl!" />
                <span>Are you sure you want to delete the selected roles?</span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteRolesDialog = false" />
                <Button label="Yes" icon="pi pi-check" text @click="deleteSelectedRoles" />
            </template>
        </Dialog>
    </div>
</template>
