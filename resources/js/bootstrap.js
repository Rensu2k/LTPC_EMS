/*
 * LTPC Enrollment Management System (LTPC_EMS)
 * Copyright (c) 2025-2026 Clarence Buenaflor & Jester Pastor. All rights reserved.
 * Unauthorized copying or distribution is strictly prohibited.
 */
import axios from "axios";
window.axios = axios;

window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

// Force HTTPS for all requests when using ngrok
if (window.location.protocol === "https:") {
    // Ensure all relative URLs are treated as HTTPS
    window.axios.defaults.baseURL = window.location.origin;

    // Intercept requests to force HTTPS
    window.axios.interceptors.request.use(function (config) {
        // If the URL is relative, ensure it uses HTTPS
        if (config.url && config.url.startsWith("/")) {
            config.url = window.location.origin + config.url;
        }
        return config;
    });
}
