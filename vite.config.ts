import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const isSail = process.env.LARAVEL_SAIL === '1';
    const appUrl = (env.APP_URL ?? '').replace(/\/$/, '');
    const vitePort = env.VITE_PORT ?? '5173';

    let sailOrigin: string | undefined;
    let sailHmrHost: string | undefined;
    if (isSail && appUrl !== '') {
        try {
            const parsed = new URL(appUrl);
            // Vite ascolta su VITE_PORT; l'URL dell'app (APP_URL) puo' avere gia' una porta (es. :8080 se la 80 e' occupata da Apache).
            sailOrigin = `${parsed.protocol}//${parsed.hostname}:${vitePort}`;
            sailHmrHost = parsed.hostname;
        } catch {
            sailOrigin = undefined;
            sailHmrHost = undefined;
        }
    }

    return {
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.ts'],
                refresh: true,
            }),
            inertia(),
            tailwindcss(),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
            wayfinder({
                formVariants: true,
            }),
        ],
        server: {
            ...(isSail
                ? {
                      host: '0.0.0.0',
                      port: parseInt(vitePort, 10),
                      strictPort: true,
                  }
                : {}),
            ...(sailOrigin ? { origin: sailOrigin } : {}),
            ...(sailHmrHost ? { hmr: { host: sailHmrHost } } : {}),
        },
    };
});
