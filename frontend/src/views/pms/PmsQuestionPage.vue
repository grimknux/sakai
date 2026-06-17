<script setup>
import { pmsQuestionsApi } from '@/api/pms';
import { usePermissions } from '@/composables/usePermissions';
import { FilterMatchMode } from '@primevue/core/api';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { onMounted, reactive, ref } from 'vue';

const toast = useToast();
const confirm = useConfirm();
const { can } = usePermissions();

const dt = ref();
const loading = ref(false);
const saving = ref(false);

const questions = ref([]);
const selectedQuestions = ref(null);

const questionDialog = ref(false);
const question = ref({});

const errorMsg = ref('');
const errors = reactive({
    question_text: '',
    question_type: '',
    sort_order: '',
    is_active: ''
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

const questionTypeOptions = ref([{ label: 'Yes / No', value: 'yes_no' }]);

const activeOptions = ref([
    { label: 'Active', value: 1 },
    { label: 'Inactive', value: 0 }
]);

async function load() {
    const res = await pmsQuestionsApi.list();
    questions.value = (res.questions || res.items || []).map((row) => ({
        ...row,
        id: row.id,
        sort_order: row.sort_order !== null ? Number(row.sort_order) : 0,
        is_active: Number(row.is_active) === 1 ? 1 : 0
    }));
}

onMounted(async () => {
    await load();
});

function clearErrors() {
    errors.question_text = '';
    errors.question_type = '';
    errors.sort_order = '';
    errors.is_active = '';
    errorMsg.value = '';
}

function validate() {
    clearErrors();

    let ok = true;

    if (!question.value.question_text?.trim()) {
        errors.question_text = 'Question text is required.';
        ok = false;
    }

    if (!question.value.question_type) {
        errors.question_type = 'Question type is required.';
        ok = false;
    }

    if (question.value.sort_order !== null && question.value.sort_order !== undefined && question.value.sort_order !== '') {
        if (Number.isNaN(Number(question.value.sort_order))) {
            errors.sort_order = 'Sort order must be a number.';
            ok = false;
        }
    }

    return ok;
}

function openNew() {
    clearErrors();
    question.value = {
        question_text: '',
        question_type: 'yes_no',
        sort_order: 0,
        is_active: 1
    };
    questionDialog.value = true;
}

function hideDialog() {
    clearErrors();
    questionDialog.value = false;
    loading.value = false;
}

function editQuestion(row) {
    clearErrors();
    question.value = {
        ...row,
        id: row.id,
        sort_order: row.sort_order !== null ? Number(row.sort_order) : 0,
        is_active: Number(row.is_active) === 1 ? 1 : 0
    };
    questionDialog.value = true;
}

function confirmDeleteQuestion(row) {
    confirm.require({
        message: `Are you sure you want to delete this question?`,
        header: 'Confirm Delete',
        icon: 'pi pi-exclamation-triangle',
        group: 'delete',
        questionText: row.question_text,

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
                await pmsQuestionsApi.remove(row.id);
                toast.add({ severity: 'success', summary: 'Successful', detail: 'PMS question deleted', life: 3000 });
                await load();
            } catch (e) {
                const msg = e?.response?.data?.message || e?.response?.data?.messages?.message || e?.message || 'Error';
                toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
            }
        }
    });
}

function confirmDeleteSelected() {
    const items = selectedQuestions.value || [];
    if (!items.length) return;

    confirm.require({
        message: `Are you sure you want to delete the selected PMS questions?`,
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
                    await pmsQuestionsApi.remove(item.id);
                }
                toast.add({ severity: 'success', summary: 'Successful', detail: 'Selected PMS questions deleted', life: 3000 });
            } catch {
                toast.add({ severity: 'error', summary: 'Error', detail: 'Some deletes failed', life: 4000 });
            } finally {
                selectedQuestions.value = null;
                await load();
            }
        }
    });
}

async function saveQuestion() {
    if (!validate()) return;

    loading.value = true;
    saving.value = true;

    try {
        const payload = {
            question_text: question.value.question_text,
            question_type: question.value.question_type,
            sort_order: question.value.sort_order ?? 0,
            is_active: question.value.is_active
        };

        if (question.value.id != null && question.value.id !== '') {
            await pmsQuestionsApi.update(question.value.id, payload);
            toast.add({ severity: 'success', summary: 'Successful', detail: 'PMS question updated', life: 3000 });
        } else {
            await pmsQuestionsApi.create(payload);
            toast.add({ severity: 'success', summary: 'Successful', detail: 'PMS question created', life: 3000 });
        }

        questionDialog.value = false;
        question.value = {};
        await load();
    } catch (err) {
        clearErrors();

        const status = err?.response?.status;
        const data = err?.response?.data || {};

        if (status === 422) {
            const fields = data?.messages?.fields || {};

            errors.question_text = fields.question_text || '';
            errors.question_type = fields.question_type || '';
            errors.sort_order = fields.sort_order || '';
            errors.is_active = fields.is_active || '';
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
</script>

<template>
    <div class="card">
        <ConfirmDialog group="delete">
            <template #message="{ message }">
                <div>
                    <p class="mb-2">{{ message.message }}</p>
                    <p v-if="message.questionText" class="text-sm text-surface-500">
                        {{ message.questionText }}
                    </p>
                </div>
            </template>
        </ConfirmDialog>

        <Toolbar class="mb-6">
            <template #start>
                <Button label="New" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" v-if="can('pms.question.create')" />
                <Button label="Delete" icon="pi pi-trash" severity="secondary" @click="confirmDeleteSelected" :disabled="!selectedQuestions || !selectedQuestions.length" v-if="can('pms.question.delete')" />
            </template>

            <template #end>
                <Button label="Export" icon="pi pi-upload" severity="secondary" @click="exportCSV" />
            </template>
        </Toolbar>

        <DataTable
            ref="dt"
            v-model:selection="selectedQuestions"
            :value="questions"
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
                    <h4 class="m-0">Manage PMS Questions</h4>
                    <IconField>
                        <InputIcon><i class="pi pi-search" /></InputIcon>
                        <InputText v-model="filters['global'].value" placeholder="Search..." />
                    </IconField>
                </div>
            </template>

            <Column selectionMode="multiple" style="width: 3rem" :exportable="false" v-if="can('pms.question.delete')" />

            <Column :exportable="false" header="Action" style="min-width: 10rem" v-if="can('pms.question.update') || can('pms.question.delete')">
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editQuestion(data)" v-if="can('pms.question.update')" />
                    <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteQuestion(data)" v-if="can('pms.question.delete')" />
                </template>
            </Column>

            <Column field="question_text" header="Question" sortable style="min-width: 24rem" />
            <Column field="question_type" header="Type" sortable style="min-width: 10rem" />
            <Column field="sort_order" header="Order" sortable style="min-width: 8rem" />
            <Column header="Active" sortable style="min-width: 8rem">
                <template #body="{ data }">
                    <Tag :value="Number(data.is_active) === 1 ? 'Active' : 'Inactive'" :severity="Number(data.is_active) === 1 ? 'success' : 'secondary'" />
                </template>
            </Column>
            <Column field="created_at" header="Created At" sortable style="min-width: 12rem" />
        </DataTable>

        <Dialog v-model:visible="questionDialog" :style="{ width: '700px' }" header="PMS Question Details" :modal="true">
            <div class="flex flex-col gap-4">
                <div v-if="errorMsg" class="text-red-500 text-sm">
                    {{ errorMsg }}
                </div>

                <div>
                    <label class="block font-bold mb-2">Question</label>
                    <Textarea v-model="question.question_text" rows="4" :disabled="loading" :invalid="!!errors.question_text" fluid />
                    <small v-if="errors.question_text" class="text-red-500">{{ errors.question_text }}</small>
                </div>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-4">
                        <label class="block font-bold mb-2">Question Type</label>
                        <Select v-model="question.question_type" :options="questionTypeOptions" optionLabel="label" optionValue="value" placeholder="Select type" :disabled="loading" :invalid="!!errors.question_type" fluid />
                        <small v-if="errors.question_type" class="text-red-500">{{ errors.question_type }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <label class="block font-bold mb-2">Sort Order</label>
                        <InputNumber v-model="question.sort_order" :useGrouping="false" :disabled="loading" :invalid="!!errors.sort_order" fluid />
                        <small v-if="errors.sort_order" class="text-red-500">{{ errors.sort_order }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <label class="block font-bold mb-2">Status</label>
                        <Select v-model="question.is_active" :options="activeOptions" optionLabel="label" optionValue="value" placeholder="Select status" :disabled="loading" :invalid="!!errors.is_active" fluid />
                        <small v-if="errors.is_active" class="text-red-500">{{ errors.is_active }}</small>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Save" icon="pi pi-check" :loading="saving" @click="saveQuestion" />
            </template>
        </Dialog>
    </div>
</template>
