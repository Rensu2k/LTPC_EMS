import "./bootstrap";
import "../css/app.css";

import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import Toast from "vue-toastification";
import "vue-toastification/dist/index.css";

const appName = import.meta.env.VITE_APP_NAME || "Laravel";

// Toast configuration
const toastOptions = {
    position: "top-right",
    timeout: 5000,
    closeOnClick: true,
    pauseOnFocusLoss: true,
    pauseOnHover: true,
    draggable: true,
    draggablePercent: 0.6,
    showCloseButtonOnHover: false,
    hideProgressBar: false,
    closeButton: "button",
    icon: true,
    rtl: false,
    maxToasts: 5,
    newestOnTop: true,
    transition: "Vue-Toastification__bounce",
    toastClassName: "custom-toast",
    bodyClassName: "custom-toast-body",
};

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        // Lazy load pages for better performance
        return resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue", { eager: false })
        );
    },
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(Toast, toastOptions)
            .mount(el);
    },
    progress: {
        color: "#4F46E5",
        includeCSS: true,
        showSpinner: true,
    },
});
