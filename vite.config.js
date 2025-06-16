import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    plugins: [
        laravel({
            input: "resources/js/app.js",
            refresh: true,
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
        hmr: {
            overlay: false,
        },
    },
});
