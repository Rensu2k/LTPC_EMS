/*
 * LTPC Enrollment Management System (LTPC_EMS)
 * Copyright (c) 2025-2026 Clarence Buenaflor & Jester Pastor. All rights reserved.
 * Unauthorized copying or distribution is strictly prohibited.
 */
import axios from "axios";
window.axios = axios;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

if (window.location.protocol === "https:") {
    window.axios.defaults.baseURL = window.location.origin;

    window.axios.interceptors.request.use(function (config) {
        if (config.url && config.url.startsWith("/")) {
            config.url = window.location.origin + config.url;
        }
        return config;
    });
}
