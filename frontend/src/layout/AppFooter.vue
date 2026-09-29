<script setup>
import { APP_NAME } from '@/config/app';

const version = __APP_VERSION__;
</script>

<template>
    <div class="layout-footer">
        {{ APP_NAME }} {{ version }} &copy; 2026. Built with
        <a href="https://sakai.primevue.org/" target="_blank" rel="noopener noreferrer" class="text-primary font-bold hover:underline">SAKAI by PrimeVue</a>
    </div>
</template>
