<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

function getUser() {
    try {
        return JSON.parse(localStorage.getItem('user') || 'null');
    } catch {
        return null;
    }
}

const user = computed(() => getUser());

function goHome() {
    router.replace({ name: 'home' });
}

function goBack() {
    router.back();
}
</script>

<template>
    <div class="bg-surface-200 dark:bg-surface-950 min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-2xl">
            <div style="border-radius: 56px; padding: 0.3rem; background: linear-gradient(180deg, var(--primary-color) 10%, rgba(33, 150, 243, 0) 30%)">
                <div class="bg-surface-0 dark:bg-surface-900 p-8 sm:p-10 shadow rounded-[53px]">
                    <div class="flex flex-col items-center text-center">
                        <div class="mb-4">
                            <div class="w-20 h-20 rounded-full flex items-center justify-center bg-surface-100 dark:bg-surface-800 border border-surface-200 dark:border-surface-700">
                                <i class="pi pi-shield text-4xl text-primary"></i>
                            </div>
                        </div>

                        <div class="text-3xl font-semibold text-surface-900 dark:text-surface-0">403 - Forbidden</div>
                        <p class="mt-2 text-surface-600 dark:text-surface-300 max-w-xl">You don’t have permission to access this page. If you believe this is a mistake, contact the system administrator.</p>

                        <div class="mt-6 w-full">
                            <div class="p-4 rounded-lg border border-surface-200 dark:border-surface-700 bg-surface-50 dark:bg-surface-800 text-left">
                                <div class="text-sm text-surface-600 dark:text-surface-300">
                                    Signed in as:
                                    <span class="font-medium text-surface-900 dark:text-surface-0">
                                        {{ user?.username || 'Unknown' }}
                                    </span>
                                </div>
                                <div class="text-xs mt-1 text-surface-500 dark:text-surface-400">Tip: Ask your admin to grant the correct role/permission.</div>
                            </div>
                        </div>

                        <div class="mt-6 flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                            <Button label="Go to Home" icon="pi pi-home" class="w-full sm:w-auto" @click="goHome" />
                            <Button label="Go Back" icon="pi pi-arrow-left" severity="secondary" outlined class="w-full sm:w-auto" @click="goBack" />
                        </div>

                        <div class="mt-6 text-xs text-surface-500 dark:text-surface-400">Error Code: <span class="font-mono">OPMSS-403</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* optional: keep it minimal */
</style>
