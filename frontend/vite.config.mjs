import fs from 'node:fs';
import { fileURLToPath, URL } from 'node:url';

import { PrimeVueResolver } from '@primevue/auto-import-resolver';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import Components from 'unplugin-vue-components/vite';
import { defineConfig, loadEnv } from 'vite';
import { execSync } from 'child_process';

function getGitVersion() {
    try {
        return execSync('git describe --tags --abbrev=0').toString().trim();
    } catch {
        return 'dev';
    }
}

export default defineConfig({
    base: '/',

    server: {
        host: 'sakai.dev.local',
        port: 4747,
        https: {
            key: fs.readFileSync('C:/laragon/etc/ssl/laragon.key'),
            cert: fs.readFileSync('C:/laragon/etc/ssl/laragon.crt')
        },
        proxy: {
            '/api': {
                target: 'https://sakai.dev.local',
                changeOrigin: false,
                secure: false
            },
            '/viewer': {
                target: 'https://sakai.dev.local',
                changeOrigin: false,
                secure: false
            },
            '/download': {
                target: 'https://sakai.dev.local',
                changeOrigin: false,
                secure: false
            }
        }
    },

    build: {
        outDir: '../public',
        emptyOutDir: false
    },

    plugins: [
        {
            // Fill %APP_NAME% in index.html from VITE_APP_NAME (frontend/.env)
            name: 'html-app-name',
            transformIndexHtml: {
                order: 'pre',
                handler(html, ctx) {
                    const env = loadEnv(ctx.server?.config.mode ?? 'production', process.cwd(), 'VITE_');
                    return html.replace(/%APP_NAME%/g, env.VITE_APP_NAME || 'ICT Schedule System');
                }
            }
        },
        vue(),
        tailwindcss(),
        Components({
            resolvers: [PrimeVueResolver()]
        })
    ],

    define: {
        __APP_VERSION__: JSON.stringify(getGitVersion())
    },

    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./src', import.meta.url))
        }
    }
});
