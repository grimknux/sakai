<script setup>
import { getCsrf, requestPasswordReset } from '@/api/auth';
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

const email = ref('');
const loading = ref(false);
const msg = ref('');
const errorMsg = ref('');
const errors = reactive({ email: '' });

function validate() {
    errors.email = '';
    errorMsg.value = '';
    msg.value = '';

    if (!email.value.trim()) {
        errors.email = 'Email is required.';
        return false;
    }

    // basic email format
    if (!/^\S+@\S+\.\S+$/.test(email.value.trim())) {
        errors.email = 'Please enter a valid email.';
        return false;
    }

    return true;
}

async function submit() {
    if (!validate()) return;

    loading.value = true;
    try {
        await requestPasswordReset(email.value.trim());

        // Always show generic success (avoid user enumeration)
        msg.value = 'If that email exists, we sent a password reset link.';
        errorMsg.value = '';

        setTimeout(() => router.replace({ name: 'login' }), 1500);
    } catch (e) {
        const data = e?.response?.data || {};
        errorMsg.value = data?.messages?.error || data?.message || 'Request failed.';
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    try {
        await getCsrf();
    } catch (_) {}
});
</script>

<template>
    <FloatingConfigurator />
    <div class="bg-surface-200 dark:bg-surface-950 flex items-center justify-center min-h-screen w-full overflow-x-hidden">
        <div class="w-full max-w-xl">
            <div style="border-radius: 12px; padding: 0.3rem; background: linear-gradient(180deg, var(--primary-color) 10%, rgba(33, 150, 243, 0) 30%)">
                <div class="bg-surface-0 dark:bg-surface-900 py-8 px-6" style="border-radius: 10px">
                    <div class="text-center mb-6">
                        <h2 class="text-xl font-semibold text-surface-900 dark:text-surface-0">Reset Password</h2>
                        <div class="w-10 h-1 bg-primary mx-auto mt-2 rounded mb-3"></div>
                        <p class="mt-3 text-surface-600 dark:text-surface-300 text-sm">Enter your email and we’ll send a reset link.</p>
                    </div>

                    <Message v-if="msg" severity="success" class="mb-4">{{ msg }}</Message>
                    <Message v-if="errorMsg" severity="error" class="mb-4">{{ errorMsg }}</Message>

                    <form @submit.prevent="submit">
                        <div class="mb-4">
                            <FloatLabel variant="on">
                                <InputText id="email" type="email" class="w-full" v-model="email" :invalid="!!errors.email" :disabled="loading" autocomplete="email" />
                                <label for="email" class="block text-surface-900 dark:text-surface-0 text-xl font-medium mb-2"> Email </label>
                            </FloatLabel>
                            <small v-if="errors.email" class="block mt-1 text-red-600 dark:text-red-400 text-sm">{{ errors.email }}</small>
                        </div>

                        <Button label="Send reset link" type="submit" class="w-full" :loading="loading" :disabled="loading" />

                        <div class="mt-4 text-center">
                            <Button label="Back to login" link type="button" @click="router.replace({ name: 'login' })" />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
