<script setup>
import { getCsrf, resetPassword } from '@/api/auth';
import { PASSWORD_HINT, checkPassword } from '@/lib/passwordRules';
import { onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import FloatingConfigurator from '@/components/FloatingConfigurator.vue';

const route = useRoute();
const router = useRouter();

const token = ref('');
const password = ref('');
const passwordConfirm = ref('');

const loading = ref(false);
const msg = ref('');
const errorMsg = ref('');

const errors = reactive({
    password: '',
    passwordConfirm: '',
    token: ''
});

function clearErrors() {
    errors.password = '';
    errors.passwordConfirm = '';
    errors.token = '';
}

function validate() {
    clearErrors();
    errorMsg.value = '';
    msg.value = '';

    if (!token.value || token.value.length < 20) {
        errors.token = 'Invalid or missing reset token.';
        return false;
    }

    if (!password.value) {
        errors.password = 'Password is required.';
        return false;
    }

    const problem = checkPassword(password.value);
    if (problem) {
        errors.password = problem;
        return false;
    }

    if (passwordConfirm.value !== password.value) {
        errors.passwordConfirm = 'Passwords do not match.';
        return false;
    }

    return true;
}

async function onSubmit() {
    if (!validate()) return;

    loading.value = true;
    try {
        const res = await resetPassword(token.value, password.value, passwordConfirm.value);
        msg.value = res?.message || 'Password reset successful. Please login.';

        // redirect to login after a moment
        setTimeout(() => {
            router.replace({ name: 'login' });
        }, 1500);
    } catch (e) {
        const status = e?.response?.status;
        const data = e?.response?.data || {};

        // backend validation
        if (status === 422) {
            const fields = data?.messages?.fields || {};
            errors.password = fields.password || '';
            errors.passwordConfirm = fields.password_confirm || '';
            errors.token = fields.token || '';
            errorMsg.value = data?.messages?.error || 'Validation failed.';
            return;
        }

        errorMsg.value = data?.messages?.error || data?.message || 'Reset failed. The link may be expired.';
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    // token comes from query string: /reset-password?token=...
    token.value = (route.query.token || '').toString();

    try {
        await getCsrf();
    } catch (_) {}
});
</script>

<template>
    <FloatingConfigurator />

    <div class="bg-surface-200 dark:bg-surface-950 flex items-center justify-center min-h-screen w-full overflow-x-hidden p-4">
        <div class="w-full max-w-xl">
            <div style="border-radius: 12px; padding: 0.3rem; background: linear-gradient(180deg, var(--primary-color) 10%, rgba(33, 150, 243, 0) 30%)">
                <div class="bg-surface-0 dark:bg-surface-900 py-8 px-6" style="border-radius: 10px">
                    <div class="text-center mb-6">
                        <h2 class="text-xl font-semibold text-surface-900 dark:text-surface-0">Set New Password</h2>
                        <div class="w-10 h-1 bg-primary mx-auto mt-2 rounded"></div>
                        <p class="mt-3 text-surface-600 dark:text-surface-300 text-sm">Please enter a new password for your account.</p>
                    </div>

                    <Message v-if="msg" severity="success" class="mb-4">{{ msg }}</Message>
                    <Message v-if="errorMsg" severity="error" class="mb-4">{{ errorMsg }}</Message>

                    <form @submit.prevent="onSubmit">
                        <!-- token (hidden but validated) -->
                        <small v-if="errors.token" class="block mb-3 text-red-600 dark:text-red-400 text-sm">
                            {{ errors.token }}
                        </small>

                        <div class="mb-4">
                            <FloatLabel variant="on">
                                <Password id="password" v-model="password" :toggleMask="true" fluid :feedback="true" :disabled="loading" :invalid="!!errors.password" autocomplete="new-password" />
                                <label for="password" class="block text-surface-900 dark:text-surface-0 font-medium text-xl mb-2"> New Password </label>
                            </FloatLabel>
                            <small v-if="errors.password" class="block mt-1 text-red-600 dark:text-red-400 text-sm">
                                {{ errors.password }}
                            </small>
                            <small v-else class="block mt-1 text-surface-500 text-sm">{{ PASSWORD_HINT }}</small>
                        </div>

                        <div class="mb-6">
                            <FloatLabel variant="on">
                                <Password id="passwordConfirm" v-model="passwordConfirm" :toggleMask="true" fluid :feedback="false" :disabled="loading" :invalid="!!errors.passwordConfirm" autocomplete="new-password" />
                                <label for="passwordConfirm" class="block text-surface-900 dark:text-surface-0 font-medium text-xl mb-2"> Confirm Password </label>
                            </FloatLabel>
                            <small v-if="errors.passwordConfirm" class="block mt-1 text-red-600 dark:text-red-400 text-sm">
                                {{ errors.passwordConfirm }}
                            </small>
                        </div>

                        <Button label="Reset Password" type="submit" class="w-full" :loading="loading" :disabled="loading || !token" />

                        <div class="mt-4 text-center">
                            <Button label="Back to login" link type="button" @click="router.replace({ name: 'login' })" />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.pi-eye,
.pi-eye-slash {
    transform: scale(1.6);
    margin-right: 1rem;
}
</style>
