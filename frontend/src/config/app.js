// Application identity, read from frontend/.env (VITE_* variables) at build time.
// Defaults keep the app working when a variable is not set.
const env = import.meta.env;

export const APP_NAME = env.VITE_APP_NAME || 'ICT Schedule System';
export const APP_SHORT_NAME = env.VITE_APP_SHORT_NAME || 'ISCHED';
export const ORG_NAME = env.VITE_ORG_NAME || 'Department of Health - Ilocos Center for Health Development';
export const ORG_SHORT_NAME = env.VITE_ORG_SHORT_NAME || 'DOH-ICHD';
export const APP_LOGO = env.VITE_APP_LOGO || '/layout/img/dohlogo.png';
