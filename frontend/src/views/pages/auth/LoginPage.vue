<script setup>
import { APP_SHORT_NAME } from '@/config/app';
import { getCsrf, login, me } from '@/api/auth';
import { onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';

// If you already have this component, keep it
import FloatingConfigurator from '@/components/FloatingConfigurator.vue';

const router = useRouter();
const version = __APP_VERSION__;

const username = ref('');
const password = ref('');
const loading = ref(false);

const errorMsg = ref('');
const errors = reactive({
    username: '',
    password: ''
});

function validate() {
    errors.username = '';
    errors.password = '';
    errorMsg.value = '';

    let ok = true;

    if (!username.value.trim()) {
        errors.username = 'Username is required.';
        ok = false;
    }

    if (!password.value) {
        errors.password = 'Password is required.';
        ok = false;
    }

    return ok;
}

function clearErrors() {
    errors.username = '';
    errors.password = '';
}

async function onLogin() {
    if (!validate()) return;

    loading.value = true;
    errorMsg.value = '';

    try {
        // clear stale session before starting a new login
        localStorage.removeItem('user');
        localStorage.removeItem('permissions');
        localStorage.removeItem('roles');

        await login(username.value, password.value);

        const meRes = await me();
        const user = meRes?.user;

        const permissions = Array.isArray(meRes?.permissions) ? meRes.permissions : typeof meRes?.permissions === 'string' ? [meRes.permissions] : [];

        localStorage.setItem('user', JSON.stringify(user));
        localStorage.setItem('permissions', JSON.stringify(permissions));
        if (Array.isArray(meRes?.roles)) localStorage.setItem('roles', JSON.stringify(meRes.roles));

        console.log('LOGIN RESULT:', { user, permissions });

        return router.replace({ name: 'home' });
    } catch (err) {
        clearErrors();

        const status = err?.response?.status;
        const data = err?.response?.data || {};

        // ✅ 422 validation
        if (status === 422) {
            const fields = data?.messages?.fields || {};
            errors.username = fields.username || '';
            errors.password = fields.password || '';
            showError(data?.messages?.error || 'Validation failed.');
            return;
        }

        // ✅ locked account (403)
        if (data?.lock?.locked) {
            showError(`Account locked. Try again in ${data.lock.remaining_minutes} minute(s).`);
            return;
        }

        // ✅ throttled (429) (optional special message)
        if (status === 429) {
            showError(data?.messages?.error || 'Too many attempts. Please try again later.');
            return;
        }

        // ✅ unauthorized / forbidden (401/403)
        if (status === 401 || status === 403) {
            showError(data?.messages?.error || 'Login failed.');
            return;
        }

        // ✅ fallback
        const serverMsg = data?.message || data?.messages?.error || data?.error || err?.message;

        showError(serverMsg || 'Something went wrong.');
    } finally {
        loading.value = false;
    }
}

function showError(msg) {
    errorMsg.value = msg;

    setTimeout(() => {
        errorMsg.value = '';
    }, 3000);
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
        <div class="w-full max-w-xl px-4 sm:px-6 lg:px-8">
            <div style="border-radius: 12px; padding: 0.3rem; background: linear-gradient(180deg, var(--primary-color) 10%, rgba(33, 150, 243, 0) 30%)">
                <div class="bg-surface-0 dark:bg-surface-900 py-8 px-4 sm:px-6 lg:px-8" style="border-radius: 10px">
                    <div class="text-center mb-6">
                        <h2 class="text-xl font-semibold text-surface-900 dark:text-surface-0">Login</h2>
                        <div class="w-10 h-1 bg-primary mx-auto mt-2 rounded"></div>
                    </div>

                    <div>
                        <Message v-if="errorMsg" severity="error" class="mb-4">
                            {{ errorMsg }}
                        </Message>
                    </div>

                    <div>
                        <form @submit.prevent="onLogin">
                            <div class="mb-4">
                                <FloatLabel variant="on">
                                    <InputText id="username" type="text" class="w-full" v-model="username" :invalid="!!errors.username" :disabled="loading" autocomplete="username" />
                                    <label for="username" class="block text-surface-900 dark:text-surface-0 text-xl font-medium mb-2">Username</label>
                                </FloatLabel>
                                <small v-if="errors.username" class="block mt-1 text-red-600 dark:text-red-400 text-sm">
                                    {{ errors.username }}
                                </small>
                            </div>

                            <div class="mb-4">
                                <FloatLabel variant="on">
                                    <Password id="password" v-model="password" :toggleMask="true" fluid :feedback="false" :invalid="!!errors.password" :disabled="loading" autocomplete="current-password" class="w-full" />
                                    <label for="password" class="block text-surface-900 dark:text-surface-0 font-medium text-xl mb-2">Password</label>
                                </FloatLabel>
                                <small v-if="errors.password" class="block mt-1 text-red-600 dark:text-red-400 text-sm">
                                    {{ errors.password }}
                                </small>
                            </div>

                            <div class="flex items-center justify-end mt-2 mb-6">
                                <Button label="Forgot password?" link class="p-0" type="button" @click="$router.push({ name: 'forgot-password' })" />
                            </div>

                            <Button label="Sign In" type="submit" class="w-full" :loading="loading" :disabled="loading" />

                            <div class="text-sm text-surface-600 dark:text-surface-300 mt-4 text-center">&copy; {{ new Date().getFullYear() }} <span class="text-primary font-bold">{{ APP_SHORT_NAME }}</span> {{ version }}</div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.pi-eye {
    transform: scale(1.6);
    margin-right: 1rem;
}

.pi-eye-slash {
    transform: scale(1.6);
    margin-right: 1rem;
}
</style>
