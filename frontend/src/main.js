import AppPreset from '@/theme/app-preset';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';

import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';

import '@/assets/styles.scss';
import '@/assets/tailwind.css';

const savedTheme = localStorage.getItem('app_theme');

if (savedTheme === 'dark') {
    document.documentElement.classList.add('app-dark');
} else {
    document.documentElement.classList.remove('app-dark');
}

const app = createApp(App);

app.use(router);
app.use(PrimeVue, {
    theme: {
        preset: AppPreset,
        options: {
            darkModeSelector: '.app-dark'
        }
    }
});
app.use(ToastService);
app.use(ConfirmationService);

app.mount('#app');
