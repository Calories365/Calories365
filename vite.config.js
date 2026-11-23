import {defineConfig, loadEnv} from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default ({mode}) => {
    process.env = {...process.env, ...loadEnv(mode, process.cwd())};

    return defineConfig({
        server: {
            host: '0.0.0.0',
            hmr: {
                clientPort: 5174,
                host: process.env.VITE_APP_URL,
                protocol: 'ws'
            },
            port: 5174,
            watch: {
                // Polling is what a Docker bind mount needs; on the host it keeps a CPU
                // core busy while idle, so it is on only with VITE_USE_POLLING=true.
                usePolling: process.env.VITE_USE_POLLING === 'true'
            }
        },
        plugins: [
            vue(),
            laravel({
                input: [
                    'resources/css/app.css',
                    // 'resources/css/admin.css',
                    'resources/js/app.js',
                    // 'resources/js/admin.js'
                ],
                refresh: true,
            }),
        ],
        resolve: {
            alias: {
                '@': path.resolve(__dirname, 'resources/js/src'),
            },
        },
        css: {
            preprocessorOptions: {
                scss: {
                    additionalData: `@import "@/variables/variables.scss";`
                }
            }
        },
    });
};
