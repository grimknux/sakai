<script setup>
import { usersApi } from '@/api/admin';
import { usePermissions } from '@/composables/usePermissions';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, reactive, ref } from 'vue';

const toast = useToast();
const dt = ref();
const loading = ref(false);
const { can } = usePermissions();

const users = ref([]);
const selectedUsers = ref(null);

const userDialog = ref(false);
const deleteUserDialog = ref(false);
const deleteUsersDialog = ref(false);
const rolesDialog = ref(false);
const errorMsg = ref('');
const errors = reactive({
    username: '',
    password: '',
    email: '',
    firstname: '',
    lastname: '',
    middlename: ''
});

const submitted = ref(false);
const saving = ref(false);

const user = ref({});
const availableRoles = ref([]);
const assignedRoleIds = ref([]);

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

const currentUser = computed(() => {
    try {
        return JSON.parse(localStorage.getItem('user') || 'null');
    } catch {
        return null;
    }
});

async function load() {
    const res = await usersApi.list();
    users.value = (res.users || []).map((u) => ({
        ...u,
        is_active: Number(u.is_active),
        is_superadmin: Number(u.is_superadmin)
    }));
}

onMounted(load);

function clearErrors() {
    errors.username = '';
    errors.password = '';
    errors.email = '';
    errors.firstname = '';
    errors.lastname = '';
    errors.middlename = '';
    errorMsg.value = '';
}

function validate() {
    errors.username = '';
    errors.password = '';
    errors.email = '';
    errors.firstname = '';
    errors.lastname = '';
    errors.middlename = '';
    errorMsg.value = '';

    let ok = true;

    if (!user.value.username?.trim()) {
        errors.username = 'Username is required.';
        ok = false;
    }

    // password required only when creating user
    if (!user.value.id && !user.value.password?.trim()) {
        errors.password = 'Password is required.';
        ok = false;
    }

    if (!user.value.email?.trim()) {
        errors.email = 'Email is required.';
        ok = false;
    } else {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!emailRegex.test(user.value.email)) {
            errors.email = 'Please enter a valid email address.';
            ok = false;
        }
    }

    if (!user.value.firstname?.trim()) {
        errors.firstname = 'First name is required.';
        ok = false;
    }

    if (!user.value.lastname?.trim()) {
        errors.lastname = 'Last name is required.';
        ok = false;
    }

    return ok;
}

function openNew() {
    clearErrors();

    user.value = {
        is_active: true,
        is_superadmin: false
    };
    loading.value = false;
    userDialog.value = true;
}

function hideDialog() {
    clearErrors();

    userDialog.value = false;
    loading.value = false;
}

function editUser(row) {
    clearErrors();

    user.value = {
        ...row,
        is_active: row.is_active == 1 || row.is_active === true // ✅ boolean
    };
    userDialog.value = true;
}

function confirmDeleteUser(row) {
    user.value = row;
    deleteUserDialog.value = true;
}

function confirmDeleteSelected() {
    deleteUsersDialog.value = true;
}

async function saveUser() {
    if (!validate()) return;

    loading.value = true;
    //submitted.value = true;

    // Basic validation
    if (!user.value.username?.trim()) return;
    if (!user.value.id && !user.value.password?.trim()) return;

    saving.value = true;
    try {
        if (user.value.id) {
            await usersApi.update(user.value.id, {
                email: user.value.email,
                firstname: user.value.firstname,
                lastname: user.value.lastname,
                middlename: user.value.middlename,
                is_active: user.value.is_active ? 1 : 0
            });
            toast.add({ severity: 'success', summary: 'Successful', detail: 'User Updated', life: 3000 });
        } else {
            await usersApi.create({
                username: user.value.username,
                password: user.value.password,
                email: user.value.email,
                firstname: user.value.firstname,
                lastname: user.value.lastname,
                middlename: user.value.middlename
            });
            toast.add({ severity: 'success', summary: 'Successful', detail: 'User Created', life: 3000 });
        }

        userDialog.value = false;
        user.value = {};
        await load();
    } catch (err) {
        clearErrors();

        const status = err?.response?.status;
        const data = err?.response?.data || {};

        if (status === 422) {
            const fields = data?.messages?.fields || {};

            errors.username = fields.username || '';
            errors.password = fields.password || '';
            errors.email = fields.email || '';
            errors.firstname = fields.firstname || '';
            errors.lastname = fields.lastname || '';
            errors.middlename = fields.middlename || '';

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

async function deleteUser() {
    try {
        await usersApi.remove(user.value.id);
        toast.add({ severity: 'success', summary: 'Successful', detail: 'User Deleted', life: 3000 });
        deleteUserDialog.value = false;
        user.value = {};
        await load();
    } catch (e) {
        const msg = e?.response?.data?.message || e?.response?.data?.messages?.error || e?.response?.data?.messages?.message || e?.message || 'Error';
        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    }
}

async function deleteSelectedUsers() {
    const items = selectedUsers.value || [];
    try {
        for (const u of items) await usersApi.remove(u.id);
        toast.add({ severity: 'success', summary: 'Successful', detail: 'Users Deleted', life: 3000 });
    } catch {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Some deletes failed', life: 4000 });
    } finally {
        deleteUsersDialog.value = false;
        selectedUsers.value = null;
        await load();
    }
}

function exportCSV() {
    dt.value.exportCSV();
}

// ===== Roles assignment =====
async function openRolesDialog(row) {
    user.value = { ...row };
    rolesDialog.value = true;

    const res = await usersApi.roles(row.id);
    availableRoles.value = (res.roles || []).map((r) => ({ ...r, id: Number(r.id) }));
    assignedRoleIds.value = (res.assigned_role_ids || []).map(Number);
}

async function saveRoles() {
    // prevent removing your own superadmin via roles? (superadmin is a user flag; still protect roles)
    try {
        await usersApi.setRoles(user.value.id, assignedRoleIds.value.map(Number));
        toast.add({ severity: 'success', summary: 'Saved', detail: 'Roles updated', life: 3000 });
        rolesDialog.value = false;
    } catch (e) {
        const msg = e?.response?.data?.message || e?.response?.data?.messages?.error || e?.message || 'Error';
        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    }
}

// ===== Superadmin toggle =====
const isSelf = computed(() => currentUser.value?.id == user.value?.id);

async function toggleSuperadmin(row) {
    // prevent self lockout from UI too
    if (currentUser.value?.id == row.id && Number(row.is_superadmin) === 1) {
        toast.add({ severity: 'warn', summary: 'Blocked', detail: 'You cannot remove your own superadmin privileges.', life: 3000 });
        return;
    }

    try {
        await usersApi.setSuperadmin(row.id, Number(row.is_superadmin) ? 0 : 1);
        toast.add({ severity: 'success', summary: 'Updated', detail: 'Superadmin updated', life: 2500 });
        await load();
    } catch (e) {
        const msg = e?.response?.data?.message || e?.response?.data?.messages?.error || e?.message || 'Error';
        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    }
}

async function unlockUser(userRow) {
    try {
        await usersApi.unlock(userRow.id);

        toast.add({
            severity: 'success',
            summary: 'Unlocked',
            detail: `${userRow.username} has been unlocked`,
            life: 3000
        });

        await load();
    } catch (e) {
        const msg = e?.response?.data?.messages?.error || e?.response?.data?.message || e?.message || 'Failed to unlock user';

        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: msg,
            life: 4000
        });
    }
}
</script>

<template>
    <div class="card">
        <Toolbar class="mb-6">
            <template #start>
                <Button label="New" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" v-if="can('users.create')" />
                <Button label="Delete" icon="pi pi-trash" severity="secondary" @click="confirmDeleteSelected" :disabled="!selectedUsers || !selectedUsers.length" v-if="can('users.delete')" />
            </template>
            <template #end>
                <Button label="Export" icon="pi pi-upload" severity="secondary" @click="exportCSV" />
            </template>
        </Toolbar>

        <DataTable
            ref="dt"
            v-model:selection="selectedUsers"
            :value="users"
            dataKey="id"
            :paginator="true"
            :rows="10"
            :filters="filters"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[5, 10, 25]"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} users"
        >
            <template #header>
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <h4 class="m-0">Manage Users</h4>
                    <IconField>
                        <InputIcon><i class="pi pi-search" /></InputIcon>
                        <InputText v-model="filters['global'].value" placeholder="Search..." />
                    </IconField>
                </div>
            </template>

            <Column selectionMode="multiple" style="width: 3rem" :exportable="false" v-if="can('users.delete')" />
            <Column field="username" header="Username" sortable style="min-width: 12rem" />
            <Column field="email" header="Email" sortable style="min-width: 14rem" />
            <Column field="firstname" header="First Name" sortable style="min-width: 12rem" />
            <Column field="middlename" header="Middle Name" sortable style="min-width: 12rem" />
            <Column field="lastname" header="Last Name" sortable style="min-width: 12rem" />
            <Column field="is_active" header="Active" sortable style="min-width: 8rem">
                <template #body="{ data }">
                    <Tag :value="data.is_active ? 'YES' : 'NO'" :severity="data.is_active ? 'success' : 'danger'" />
                </template>
            </Column>

            <Column field="is_superadmin" header="Super Admin" style="min-width: 10rem">
                <template #body="{ data }">
                    <Button :label="data.is_superadmin ? 'Yes' : 'No'" :severity="data.is_superadmin ? 'danger' : 'secondary'" icon="pi pi-shield" text @click="toggleSuperadmin(data)" :disabled="!can('users.roles.assign')" />
                </template>
            </Column>

            <Column :exportable="false" style="min-width: 14rem" v-if="can('users.roles.assign') || can('users.update') || can('users.delete') || can('users.delete') || can('security.unlock')">
                <template #body="{ data }">
                    <Button icon="pi pi-users" outlined rounded class="mr-2" @click="openRolesDialog(data)" v-if="can('users.roles.assign')" />
                    <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editUser(data)" v-if="can('users.update')" />
                    <Button v-if="can('security.unlock') && data.locked_until" icon="pi pi-lock-open" severity="warn" outlined rounded class="mr-2" @click="unlockUser(data)" />
                    <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteUser(data)" v-if="can('users.delete')" />
                </template>
            </Column>
        </DataTable>

        <!-- Create/Edit User -->
        <Dialog v-model:visible="userDialog" :style="{ width: '530px' }" header="User Details" :modal="true">
            <div class="flex flex-col gap-4">
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">Username</label>
                        <InputText v-model.trim="user.username" :disabled="!!user.id || loading" :invalid="!!errors.username" fluid />
                        <small v-if="errors.username" class="text-red-500">
                            {{ errors.username }}
                        </small>
                    </div>

                    <div class="col-span-12 md:col-span-6" v-if="!user.id">
                        <label class="block font-bold mb-2">Password</label>
                        <Password v-model="user.password" toggleMask :feedback="false" :disabled="loading" :invalid="!!errors.password" fluid />
                        <small v-if="errors.password" class="text-red-500">
                            {{ errors.password }}
                        </small>
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-4">
                        <label class="block font-bold mb-2">First Name</label>
                        <InputText v-model.trim="user.firstname" :disabled="loading" :invalid="!!errors.firstname" fluid />
                        <small v-if="errors.firstname" class="text-red-500">
                            {{ errors.firstname }}
                        </small>
                    </div>
                    <div class="col-span-12 md:col-span-4">
                        <label class="block font-bold mb-2">Middle Name</label>
                        <InputText v-model.trim="user.middlename" :disabled="loading" :invalid="!!errors.middlename" fluid />
                        <small v-if="errors.middlename" class="text-red-500">
                            {{ errors.middlename }}
                        </small>
                    </div>
                    <div class="col-span-12 md:col-span-4">
                        <label class="block font-bold mb-2">Last Name</label>
                        <InputText v-model.trim="user.lastname" :disabled="loading" :invalid="!!errors.lastname" fluid />
                        <small v-if="errors.lastname" class="text-red-500">
                            {{ errors.lastname }}
                        </small>
                    </div>
                </div>
                <div>
                    <label class="block font-bold mb-2">Email</label>
                    <InputText v-model.trim="user.email" :disabled="loading" :invalid="!!errors.email" fluid />
                    <small v-if="errors.email" class="text-red-500">
                        {{ errors.email }}
                    </small>
                </div>

                <div class="flex items-center gap-2">
                    <Checkbox v-model="user.is_active" :disabled="loading" :binary="true" />
                    <label>Active</label>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Save" icon="pi pi-check" :loading="saving" @click="saveUser" />
            </template>
        </Dialog>

        <!-- Delete single -->
        <Dialog v-model:visible="deleteUserDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl!" />
                <span v-if="user"
                    >Are you sure you want to delete <b>{{ user.username }}</b
                    >?</span
                >
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteUserDialog = false" />
                <Button label="Yes" icon="pi pi-check" @click="deleteUser" />
            </template>
        </Dialog>

        <!-- Delete selected -->
        <Dialog v-model:visible="deleteUsersDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl!" />
                <span>Are you sure you want to delete the selected users?</span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteUsersDialog = false" />
                <Button label="Yes" icon="pi pi-check" text @click="deleteSelectedUsers" />
            </template>
        </Dialog>

        <!-- Assign roles -->
        <Dialog v-model:visible="rolesDialog" :style="{ width: '520px' }" header="Assign Roles" :modal="true">
            <div class="flex flex-col gap-3">
                <div class="font-medium">User: {{ user.username }}</div>

                <div class="grid grid-cols-12 gap-2">
                    <div v-for="r in availableRoles" :key="r.id" class="col-span-12 md:col-span-6 flex items-center gap-2">
                        <Checkbox v-model="assignedRoleIds" :value="r.id" />
                        <span
                            >{{ r.name }} <small class="text-surface-500">({{ r.slug }})</small></span
                        >
                    </div>
                </div>

                <small class="text-surface-500"> Roles control permissions. Superadmin flag is managed separately. </small>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" text @click="rolesDialog = false" />
                <Button label="Save" icon="pi pi-check" @click="saveRoles" />
            </template>
        </Dialog>
    </div>
</template>
