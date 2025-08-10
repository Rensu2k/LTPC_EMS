import { defineConfig, loadEnv } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), "");

    return {
        plugins: [
            laravel({
                input: "resources/js/app.js",
                // Allow disabling auto-refresh during remote HTTPS testing
                refresh: env.VITE_LARAVEL_REFRESH !== "false",
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
        build: {
            // Optimize chunk size
            chunkSizeWarningLimit: 1000,
            // Enable minification
            minify: "esbuild",
        },
        optimizeDeps: {
            include: ["vue", "@inertiajs/vue3"],
        },
        server: {
            // Force IPv4 for Windows setups to avoid http://[::1]:5173
            host: env.VITE_DEV_SERVER_HOST || "127.0.0.1",
            port: 5173,
            strictPort: true,
            cors: true,
            // Explicit origin so @vite scripts point to 127.0.0.1 instead of [::1]
            origin: env.VITE_DEV_SERVER_URL || "http://127.0.0.1:5173",
            hmr: {
                overlay: false,
                host: env.VITE_HMR_HOST || "127.0.0.1",
                protocol: env.VITE_HMR_PROTOCOL || "ws",
                clientPort: env.VITE_HMR_CLIENT_PORT
                    ? Number(env.VITE_HMR_CLIENT_PORT)
                    : 5173,
            },
        },
    };
});
