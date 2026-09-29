<script setup>
import { changePassword } from '@/api/auth';
import { PASSWORD_HINT, checkPassword } from '@/lib/passwordRules';
import { useToast } from 'primevue/usetoast';
import { reactive, ref } from 'vue';

const toast = useToast();

const loading = ref(false);
const saving = ref(false);

const form = ref({
    current_password: '',
    new_password: '',
    confirm_password: ''
});

const errorMsg = ref('');
const errors = reactive({
    current_password: '',
    new_password: '',
    confirm_password: ''
});

function clearErrors() {
    errors.current_password = '';
    errors.new_password = '';
    errors.confirm_password = '';
    errorMsg.value = '';
}

function validate() {
    clearErrors();

    let ok = true;

    if (!form.value.current_password?.trim()) {
        errors.current_password = 'Current password is required.';
        ok = false;
    }

    if (!form.value.new_password?.trim()) {
        errors.new_password = 'New password is required.';
        ok = false;
    } else {
        const problem = checkPassword(form.value.new_password);
        if (problem) {
            errors.new_password = problem;
            ok = false;
        }
    }

    if (!form.value.confirm_password?.trim()) {
        errors.confirm_password = 'Confirm password is required.';
        ok = false;
    }

    if (form.value.new_password && form.value.confirm_password && form.value.new_password !== form.value.confirm_password) {
        errors.confirm_password = 'Password confirmation does not match.';
        ok = false;
    }

    return ok;
}

function resetForm() {
    form.value = {
        current_password: '',
        new_password: '',
        confirm_password: ''
    };
    clearErrors();
}

async function changePass() {
    if (!validate()) return;

    loading.value = true;
    saving.value = true;

    try {
        await changePassword({
            current_password: form.value.current_password,
            new_password: form.value.new_password,
            confirm_password: form.value.confirm_password
        });

        toast.add({
            severity: 'success',
            summary: 'Successful',
            detail: 'Password changed successfully',
            life: 3000
        });

        resetForm();
    } catch (err) {
        clearErrors();

        const status = err?.response?.status;
        const data = err?.response?.data || {};

        if (status === 422) {
            const fields = data?.messages?.fields || {};

            errors.current_password = fields.current_password || '';
            errors.new_password = fields.new_password || '';
            errors.confirm_password = fields.confirm_password || '';

            errorMsg.value = data?.messages?.error || 'Validation failed.';
            return;
        }

        const msg = data?.message || data?.messages?.error || err?.message || 'Error';

        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: msg,
            life: 4000
        });
    } finally {
        loading.value = false;
        saving.value = false;
    }
}
</script>

<template>
    <div class="card">
        <div class="max-w-2xl">
            <div class="flex flex-col gap-6">
                <div>
                    <h4 class="m-0 mb-2">Change Password</h4>
                    <p class="text-surface-500 m-0">Update your account password below.</p>
                </div>

                <Message v-if="errorMsg" severity="error">
                    {{ errorMsg }}
                </Message>

                <div class="flex flex-col gap-4">
                    <div>
                        <label class="block font-bold mb-2">Current Password</label>
                        <Password v-model="form.current_password" :disabled="loading" :feedback="false" toggleMask fluid :invalid="!!errors.current_password" />
                        <small v-if="errors.current_password" class="text-red-500">
                            {{ errors.current_password }}
                        </small>
                    </div>

                    <div>
                        <label class="block font-bold mb-2">New Password</label>
                        <Password v-model="form.new_password" :disabled="loading" toggleMask fluid :invalid="!!errors.new_password" />
                        <small v-if="errors.new_password" class="text-red-500">
                            {{ errors.new_password }}
                        </small>
                        <small v-else class="text-surface-500">{{ PASSWORD_HINT }}</small>
                    </div>

                    <div>
                        <label class="block font-bold mb-2">Confirm New Password</label>
                        <Password v-model="form.confirm_password" :disabled="loading" :feedback="false" toggleMask fluid :invalid="!!errors.confirm_password" />
                        <small v-if="errors.confirm_password" class="text-red-500">
                            {{ errors.confirm_password }}
                        </small>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <Button label="Reset" icon="pi pi-refresh" severity="secondary" text :disabled="loading" @click="resetForm" />
                    <Button label="Change Password" icon="pi pi-check" :loading="saving" @click="changePass" />
                </div>
            </div>
        </div>
    </div>
</template>
