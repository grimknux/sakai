<script setup>
import { APP_SHORT_NAME } from '@/config/app';
import { useRouter } from 'vue-router';

const router = useRouter();

function goHome() {
    router.replace({ name: 'home' });
}

function goBack() {
    router.back();
}
</script>

<template>
    <div class="bg-surface-200 dark:bg-surface-950 min-h-screen flex items-center justify-center p-6">
        <div class="relative w-full max-w-2xl">
            <!-- Big background number -->
            <div class="absolute inset-0 flex items-center justify-center text-[9rem] md:text-[13rem] font-bold text-primary opacity-10 pointer-events-none select-none">404</div>

            <!-- Card -->
            <div style="border-radius: 56px; padding: 0.3rem; background: linear-gradient(180deg, var(--primary-color) 10%, rgba(33, 150, 243, 0) 30%)">
                <div class="bg-surface-0 dark:bg-surface-900 p-10 rounded-[53px] shadow relative z-10">
                    <div class="flex flex-col items-center text-center">
                        <!-- Icon -->
                        <div class="w-20 h-20 rounded-full flex items-center justify-center bg-surface-100 dark:bg-surface-800 border border-surface-200 dark:border-surface-700 mb-4">
                            <i class="pi pi-exclamation-circle text-4xl text-primary"></i>
                        </div>

                        <!-- Title -->
                        <h1 class="text-3xl font-semibold text-surface-900 dark:text-surface-0">Page Not Found</h1>

                        <!-- Description -->
                        <p class="mt-2 text-surface-600 dark:text-surface-300 max-w-lg">The page you are looking for doesn't exist or may have been moved. Please check the URL or return to the home page.</p>

                        <!-- Buttons -->
                        <div class="flex flex-col sm:flex-row gap-3 mt-6">
                            <Button label="Go to Home" icon="pi pi-home" @click="goHome" />

                            <Button label="Go Back" icon="pi pi-arrow-left" severity="secondary" outlined @click="goBack" />
                        </div>

                        <!-- Footer -->
                        <div class="mt-6 text-xs text-surface-500 dark:text-surface-400">Error Code: <span class="font-mono">{{ APP_SHORT_NAME }}-404</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
