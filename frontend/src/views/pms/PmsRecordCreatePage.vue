<script setup>
import { buildingsApi, divisionsApi, sectionsApi } from '@/api/admin'; // <-- adjust path if different
import { inventoryApi } from '@/api/assets';
import { pmsQuestionsApi, pmsRecordsApi, pmsSchedulesApi } from '@/api/pms';
import { useToast } from 'primevue/usetoast';
import { onMounted, reactive, ref, watch } from 'vue';

const toast = useToast();

const saving = ref(false);

const schedules = ref([]);
const inventoryItems = ref([]);
const questions = ref([]);

const sections = ref([]);
const divisions = ref([]);
const buildings = ref([]);

const answerErrors = ref([]);
const answerErrorMsg = ref('');

function clearAnswerErrors() {
    answerErrorMsg.value = '';
    answerErrors.value = questions.value.map(() => ({
        question_id: '',
        answer: '',
        remarks: ''
    }));
}

const form = ref({
    pms_schedule_id: null,
    inventory_id: null,

    conducted_date: getTodayDate(),

    section_code: null,
    division_code: null,
    bldg: null,

    end_user: '',
    current_user: '',
    remarks: '',
    findings: '',
    status: '',
    answers: []
});

const errorMsg = ref('');
const errors = reactive({
    pms_schedule_id: '',
    inventory_id: '',

    conducted_date: '',

    section_code: '',
    division_code: '',
    bldg: '',

    end_user: '',
    current_user: '',
    remarks: '',
    findings: '',
    status: '',
    answers: []
});

function getSectionByCode(code) {
    return sections.value.find((s) => s.code === code);
}

/**
 * Normalize building code because your section table uses bldg,
 * while your building table primary key is code.
 * Some APIs may return bldg, some bldg.
 */
function getSectionBuildingCode(section) {
    return section?.bldg ?? null;
}

async function loadLookups() {
    const [scheduleRes, questionsRes, sectionsRes, divisionsRes, buildingsRes] = await Promise.all([pmsSchedulesApi.dropdown(), pmsQuestionsApi.active(), sectionsApi.dropdown(), divisionsApi.dropdown(), buildingsApi.dropdown()]);

    schedules.value = (scheduleRes.schedules || [])
        .map((s) => {
            const semester = s.semester ? s.semester.charAt(0).toUpperCase() + s.semester.slice(1) : '';

            const formatDate = (date) =>
                new Date(date).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });

            return {
                ...s,
                id: s.id,
                label: `${semester} semester (${formatDate(s.schedule_start)} to ${formatDate(s.schedule_end)})`
            };
        })
        .sort((a, b) => new Date(b.schedule_start) - new Date(a.schedule_start));

    if (schedules.value.length) {
        form.value.pms_schedule_id = schedules.value[0].id;
    }

    questions.value = (questionsRes.questions || []).map((q) => ({
        ...q,
        id: q.id
    }));

    sections.value = (sectionsRes.sections || sectionsRes.items || []).map((s) => ({
        ...s,
        label: s.name
    }));

    divisions.value = (divisionsRes.divisions || divisionsRes.items || []).map((d) => ({
        ...d,
        label: d.name
    }));

    buildings.value = (buildingsRes.buildings || buildingsRes.items || []).map((b) => ({
        ...b,
        label: b.name
    }));

    form.value.answers = questions.value.map((q) => ({
        question_id: q.id,
        answer: '',
        remarks: ''
    }));

    clearAnswerErrors();
}

async function loadAvailableInventoryBySchedule(scheduleId) {
    form.value.inventory_id = null;
    form.value.section_code = null;
    form.value.division_code = null;
    form.value.bldg = null;
    form.value.end_user = '';
    form.value.current_user = '';
    inventoryItems.value = [];

    if (!scheduleId) return;

    try {
        const res = await inventoryApi.dropdownAvailableForPmsSchedule(scheduleId);

        inventoryItems.value = (res.items || res.inventory || []).map((i) => ({
            ...i,
            id: Number(i.id),
            label: i.label
        }));
    } catch (e) {
        const msg = e?.response?.data?.message || e?.response?.data?.messages?.error || e?.message || 'Failed to load available inventory';
        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    }
}

onMounted(loadLookups);

function clearErrors() {
    errors.pms_schedule_id = '';
    errors.inventory_id = '';

    errors.conducted_date = '';

    errors.section_code = '';
    errors.division_code = '';
    errors.bldg = '';

    errors.end_user = '';
    errors.current_user = '';
    errors.remarks = '';
    errors.findings = '';
    errors.status = '';
    errorMsg.value = '';
}

function resetForm() {
    form.value = {
        pms_schedule_id: schedules.value.length ? schedules.value[0].id : null,
        inventory_id: null,

        conducted_date: getTodayDate(),

        section_code: null,
        division_code: null,
        bldg: null,

        end_user: '',
        current_user: '',
        remarks: '',
        findings: '',
        status: '',
        answers: questions.value.map((q) => ({
            question_id: q.id,
            answer: '',
            remarks: ''
        }))
    };
    clearErrors();
    clearAnswerErrors();
}

function validate() {
    clearErrors();
    clearAnswerErrors();

    let ok = true;

    if (!form.value.pms_schedule_id) {
        errors.pms_schedule_id = 'PMS schedule is required.';
        ok = false;
    }

    if (!form.value.inventory_id) {
        errors.inventory_id = 'Inventory item is required.';
        ok = false;
    }

    if (!form.value.conducted_date) {
        errors.conducted_date = 'Conducted date is required.';
        ok = false;
    }

    if (!form.value.findings) {
        errors.findings = 'Findings is required.';
        ok = false;
    }

    if (!form.value.status) {
        errors.status = 'Status is required.';
        ok = false;
    }

    // optional only if these are required in your backend
    // if (!form.value.section_code) {
    //     errors.section_code = 'Section is required.';
    //     ok = false;
    // }
    //
    // if (!form.value.division_code) {
    //     errors.division_code = 'Division is required.';
    //     ok = false;
    // }
    //
    // if (!form.value.bldg) {
    //     errors.bldg = 'Building is required.';
    //     ok = false;
    // }

    form.value.answers.forEach((row, index) => {
        if (!row.question_id) {
            answerErrors.value[index].question_id = 'Question is required.';
            ok = false;
        }

        if (!row.answer) {
            answerErrors.value[index].answer = 'Answer is required.';
            ok = false;
        }

        if (row.remarks && row.remarks.length > 1000) {
            answerErrors.value[index].remarks = 'Remarks must not exceed 1000 characters.';
            ok = false;
        }
    });

    if (!ok) {
        errorMsg.value = 'Validation Failed.';
    }

    return ok;
}

function getTodayDate() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

async function saveRecord() {
    if (!validate()) return;

    saving.value = true;

    try {
        const payload = {
            pms_schedule_id: form.value.pms_schedule_id,
            inventory_id: form.value.inventory_id,

            conducted_date: form.value.conducted_date,

            section_code: form.value.section_code || null,
            division_code: form.value.division_code || null,
            bldg: form.value.bldg || null,

            end_user: form.value.end_user || '',
            current_user: form.value.current_user || '',
            remarks: form.value.remarks || '',
            findings: form.value.findings || '',
            status: form.value.status || '',
            answers: form.value.answers.map((row) => ({
                question_id: row.question_id,
                answer: row.answer,
                remarks: row.remarks || ''
            }))
        };

        await pmsRecordsApi.create(payload);

        toast.add({
            severity: 'success',
            summary: 'Successful',
            detail: 'PMS record created',
            life: 3000
        });

        resetForm();
    } catch (err) {
        clearErrors();
        clearAnswerErrors();

        const status = err?.response?.status;
        const data = err?.response?.data || {};

        if (status === 422) {
            const fields = data?.messages?.fields || {};
            errors.pms_schedule_id = fields.pms_schedule_id || '';
            errors.inventory_id = fields.inventory_id || '';

            errors.conducted_date = fields.conducted_date || '';

            errors.section_code = fields.section_code || '';
            errors.division_code = fields.division_code || '';
            errors.bldg = fields.bldg || '';

            errors.end_user = fields.end_user || '';
            errors.current_user = fields.current_user || '';
            errors.remarks = fields.remarks || '';
            errors.findings = fields.findings || '';
            errors.status = fields.status || '';
            errorMsg.value = data?.messages?.error || 'Validation failed.';

            Object.entries(fields).forEach(([key, value]) => {
                const match = key.match(/^answers\.(\d+)\.(.+)$/);
                if (match) {
                    const index = Number(match[1]);
                    const field = match[2];

                    if (!answerErrors.value[index]) {
                        answerErrors.value[index] = {
                            question_id: '',
                            answer: '',
                            remarks: ''
                        };
                    }

                    if (field in answerErrors.value[index]) {
                        answerErrors.value[index][field] = value;
                    }
                }
            });

            return;
        }

        const msg = data?.message || data?.messages?.error || err?.message || 'Error';
        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    } finally {
        saving.value = false;
    }
}

function applyYesToAll() {
    form.value.answers.forEach((row) => {
        row.answer = 'yes';
    });
}

function applyNoToAll() {
    form.value.answers.forEach((row) => {
        row.answer = 'no';
    });
}

function clearAllAnswers() {
    form.value.answers.forEach((row) => {
        row.answer = '';
    });
}

/**
 * 1) Inventory watcher
 * When inventory changes:
 * - fill section from inventory.section_code
 * - fill division and building
 *
 * Priority:
 * - use inventory.division_code / inventory.bldg if present
 * - otherwise derive division/building from the selected section
 */
watch(
    () => form.value.inventory_id,
    (inventoryId) => {
        const selected = inventoryItems.value.find((i) => i.id === inventoryId);

        if (!selected) {
            form.value.end_user = '';
            form.value.current_user = '';
            form.value.section_code = null;
            form.value.division_code = null;
            form.value.bldg = null;
            return;
        }

        form.value.end_user = selected.end_user || '';
        form.value.current_user = selected.current_user || '';

        const inventorySectionCode = selected.section_code ?? null;
        const matchedSection = getSectionByCode(inventorySectionCode);

        form.value.section_code = inventorySectionCode;
        form.value.division_code = selected.division_code ?? matchedSection?.division_code ?? null;
        form.value.bldg = selected.bldg ?? getSectionBuildingCode(matchedSection) ?? null;
    }
);

function onSectionChange(sectionCode) {
    const selectedSection = getSectionByCode(sectionCode);

    if (!selectedSection) {
        form.value.division_code = null;
        form.value.bldg = null;
        return;
    }

    form.value.division_code = selectedSection.division_code ?? null;
    form.value.bldg = getSectionBuildingCode(selectedSection);
}

watch(
    () => form.value.pms_schedule_id,
    async (scheduleId) => {
        await loadAvailableInventoryBySchedule(scheduleId);
    }
);
</script>

<template>
    <div class="card">
        <div class="flex items-center justify-between mb-6">
            <h4 class="m-0">Add PMS Record</h4>
        </div>

        <div class="flex flex-col gap-4 max-w-4xl">
            <div v-if="errorMsg" class="text-red-500 text-sm">
                {{ errorMsg }}
            </div>
            <form @submit.prevent="saveRecord">
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12">
                        <label class="block font-bold mb-2">Conducted Date</label>
                        <InputText v-model="form.conducted_date" type="date" :invalid="!!errors.conducted_date" fluid />
                        <small v-if="errors.conducted_date" class="text-red-500">{{ errors.conducted_date }}</small>
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">PMS Schedule</label>
                        <Select v-model="form.pms_schedule_id" :options="schedules" optionLabel="label" optionValue="id" placeholder="Select PMS schedule" :invalid="!!errors.pms_schedule_id" fluid />
                        <small v-if="errors.pms_schedule_id" class="text-red-500">{{ errors.pms_schedule_id }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">Inventory Item</label>
                        <Select
                            v-model="form.inventory_id"
                            :options="inventoryItems"
                            optionLabel="label"
                            optionValue="id"
                            placeholder="Select inventory item"
                            :invalid="!!errors.inventory_id"
                            filter
                            fluid
                            class="w-full"
                            :pt="{
                                root: { class: 'w-full' },
                                label: { class: 'truncate block w-full' },
                                item: { class: 'truncate' }
                            }"
                        />
                        <small v-if="errors.inventory_id" class="text-red-500">{{ errors.inventory_id }}</small>
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">End User</label>
                        <InputText v-model="form.end_user" type="text" fluid />
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">Current User</label>
                        <InputText v-model="form.current_user" type="text" fluid />
                    </div>

                    <!-- SECTION -->
                    <div class="col-span-12 md:col-span-5">
                        <label class="block font-bold mb-2">Section</label>
                        <Select v-model="form.section_code" :options="sections" optionLabel="label" optionValue="code" placeholder="Select section" :invalid="!!errors.section_code" filter fluid @change="onSectionChange($event.value)" />
                        <small v-if="errors.section_code" class="text-red-500">{{ errors.section_code }}</small>
                    </div>

                    <!-- DIVISION -->
                    <div class="col-span-12 md:col-span-5">
                        <label class="block font-bold mb-2">Division</label>
                        <Select v-model="form.division_code" :options="divisions" optionLabel="label" optionValue="code" placeholder="Select division" :invalid="!!errors.division_code" filter fluid />
                        <small v-if="errors.division_code" class="text-red-500">{{ errors.division_code }}</small>
                    </div>

                    <!-- BUILDING -->
                    <div class="col-span-12 md:col-span-2">
                        <label class="block font-bold mb-2">Building</label>
                        <Select v-model="form.bldg" :options="buildings" optionLabel="label" optionValue="code" placeholder="Select building" :invalid="!!errors.bldg" filter fluid />
                        <small v-if="errors.bldg" class="text-red-500">{{ errors.bldg }}</small>
                    </div>

                    <div class="mt-6 mb-6 col-span-12 md:col-span-12">
                        <h5 class="mb-3">PMS Questions</h5>
                        <div v-if="answerErrorMsg" class="text-red-500 text-sm mb-3">
                            {{ answerErrorMsg }}
                        </div>

                        <div class="flex gap-2 mb-2">
                            <Button label="Yes" icon="pi pi-check" severity="success" size="small" @click="applyYesToAll" />
                            <Button label="No" icon="pi pi-times" severity="danger" size="small" @click="applyNoToAll" />
                            <Button label="Clear" icon="pi pi-eraser" severity="secondary" size="small" @click="clearAllAnswers" />
                        </div>

                        <div class="hidden md:block">
                            <DataTable :value="questions" responsiveLayout="scroll" class="p-datatable-sm">
                                <Column header="" style="min-width: 3rem">
                                    <template #body="{ data }">
                                        <div class="flex flex-col gap-1">
                                            <div>{{ data.cnt }}</div>
                                        </div>
                                    </template>
                                </Column>

                                <Column header="Question" style="min-width: 20rem">
                                    <template #body="{ data, index }">
                                        <div class="flex flex-col gap-1">
                                            <div>{{ data.question_text }}</div>
                                            <small v-if="answerErrors[index]?.question_id" class="text-red-500">
                                                {{ answerErrors[index].question_id }}
                                            </small>
                                        </div>
                                    </template>
                                </Column>

                                <Column header="Yes / No" style="min-width: 12rem">
                                    <template #body="{ index }">
                                        <div class="flex flex-col gap-1">
                                            <div class="flex items-center gap-4">
                                                <div class="flex items-center gap-2">
                                                    <RadioButton v-model="form.answers[index].answer" :inputId="`answer_yes_${index}`" :name="`answer_${index}`" value="yes" />
                                                    <label :for="`answer_yes_${index}`">Yes</label>
                                                </div>

                                                <div class="flex items-center gap-2">
                                                    <RadioButton v-model="form.answers[index].answer" :inputId="`answer_no_${index}`" :name="`answer_${index}`" value="no" />
                                                    <label :for="`answer_no_${index}`">No</label>
                                                </div>
                                            </div>

                                            <small v-if="answerErrors[index]?.answer" class="text-red-500">
                                                {{ answerErrors[index].answer }}
                                            </small>
                                        </div>
                                    </template>
                                </Column>

                                <Column header="Remarks" style="min-width: 20rem">
                                    <template #body="{ index }">
                                        <div class="flex flex-col gap-1">
                                            <Textarea v-model="form.answers[index].remarks" rows="1" :invalid="!!answerErrors[index]?.remarks" fluid />
                                            <small v-if="answerErrors[index]?.remarks" class="text-red-500">
                                                {{ answerErrors[index].remarks }}
                                            </small>
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </div>
                        <div class="block md:hidden">
                            <div v-for="(question, index) in questions" :key="question.id" class="border rounded-lg p-4 mb-4">
                                <div class="font-semibold mb-2">{{ question.cnt }}. {{ question.question_text }}</div>

                                <div class="flex gap-4 mb-3">
                                    <div class="flex items-center gap-2">
                                        <RadioButton v-model="form.answers[index].answer" :inputId="`m_answer_yes_${index}`" :name="`answer_${index}`" value="yes" />
                                        <label :for="`m_answer_yes_${index}`">Yes</label>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <RadioButton v-model="form.answers[index].answer" :inputId="`m_answer_no_${index}`" :name="`answer_${index}`" value="no" />
                                        <label :for="`m_answer_no_${index}`">No</label>
                                    </div>
                                </div>

                                <small v-if="answerErrors[index]?.answer" class="text-red-500 block mb-2">
                                    {{ answerErrors[index].answer }}
                                </small>

                                <label class="block font-medium mb-2">Remarks</label>
                                <Textarea v-model="form.answers[index].remarks" rows="1" :invalid="!!answerErrors[index]?.remarks" fluid />
                                <small v-if="answerErrors[index]?.remarks" class="text-red-500 block mt-1">
                                    {{ answerErrors[index].remarks }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">Findings</label>
                        <Textarea v-model="form.findings" :invalid="!!errors.findings" rows="2" fluid />
                        <small v-if="errors.findings" class="text-red-500">{{ errors.findings }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">Status</label>
                        <Textarea v-model="form.status" :invalid="!!errors.status" rows="2" fluid />
                        <small v-if="errors.status" class="text-red-500">{{ errors.status }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-12">
                        <label class="block font-bold mb-2">Remarks</label>
                        <Textarea v-model="form.remarks" rows="2" fluid />
                    </div>

                    <div class="col-span-12 md:col-span-12">
                        <div class="flex justify-end gap-2">
                            <Button label="Clear" icon="pi pi-refresh" severity="secondary" outlined @click="resetForm" />
                            <Button label="Save" type="submit" icon="pi pi-check" :loading="saving" />
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>
