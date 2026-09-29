<script setup>
import AppConfigurator from '@/layout/AppConfigurator.vue';
import { useLayout } from '@/layout/composables/layout';

const { toggleDarkMode, isDarkTheme } = useLayout();
</script>

<template>
    <div class="fixed top-0 left-0 right-0 z-[1000] bg-surface-0/80 dark:bg-surface-900/80 backdrop-blur border-b border-surface-200 dark:border-surface-700">
        <div class="w-full flex items-center justify-between gap-4 p-4">
            <!-- Left: Name -->
            <div class="flex items-center gap-3 min-w-0">
                <!-- Logo -->
                <img src="/layout/img/dohlogo.png" alt="DOH Logo" class="w-10 h-10 object-contain flex-shrink-0" />

                <!-- Text -->
                <div class="min-w-0">
                    <!-- Small screens (default): abbreviation -->
                    <span class="inline sm:hidden font-semibold truncate text-xs">
                        <div class="text-sm">ICT Schedule System</div>
                        <small class="text-xs">DOH - Ilocos Center for Health Development</small>
                    </span>

                    <!-- sm and up: full name -->
                    <span class="hidden sm:inline font-semibold truncate">
                        ICT Schedule System<br />
                        <div class="text-xs">Department of Health - Ilocos Center for Health Development</div>
                    </span>
                </div>
            </div>

            <!-- Right: Controls -->
            <div class="flex items-center gap-4">
                <Button type="button" @click="toggleDarkMode" rounded :icon="isDarkTheme ? 'pi pi-moon' : 'pi pi-sun'" severity="secondary" />

                <div class="relative">
                    <Button
                        icon="pi pi-palette"
                        v-styleclass="{
                            selector: '@next',
                            enterFromClass: 'hidden',
                            enterActiveClass: 'animate-scalein',
                            leaveToClass: 'hidden',
                            leaveActiveClass: 'animate-fadeout',
                            hideOnOutsideClick: true
                        }"
                        type="button"
                        rounded
                    />
                    <AppConfigurator />
                </div>
            </div>
        </div>
    </div>
</template>
