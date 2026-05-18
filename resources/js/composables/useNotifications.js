/*
 * LTPC Enrollment Management System (LTPC_EMS)
 * Copyright (c) 2025-2026 Clarence Buenaflor & Jester Pastor. All rights reserved.
 * Unauthorized copying or distribution is strictly prohibited.
 */
import { useToast } from "vue-toastification";

export function useNotifications() {
    const toast = useToast();

    const success = (message, options = {}) => {
        toast.success(message, {
            icon: "✓",
            ...options,
        });
    };

    const error = (message, options = {}) => {
        toast.error(message, {
            icon: "✗",
            timeout: 8000, // Longer timeout for errors
            ...options,
        });
    };

    const warning = (message, options = {}) => {
        toast.warning(message, {
            icon: "⚠",
            timeout: 6000,
            ...options,
        });
    };

    const info = (message, options = {}) => {
        toast.info(message, {
            icon: "ℹ",
            ...options,
        });
    };

    const loading = (message = "Processing...", options = {}) => {
        return toast.info(message, {
            icon: "⏳",
            timeout: false, // Don't auto-dismiss
            closeOnClick: false,
            draggable: false,
            ...options,
        });
    };

    const updateToSuccess = (toastId, message, options = {}) => {
        toast.update(toastId, {
            content: message,
            options: {
                type: "success",
                icon: "✓",
                timeout: 5000,
                closeOnClick: true,
                draggable: true,
                ...options,
            },
        });
    };

    const updateToError = (toastId, message, options = {}) => {
        toast.update(toastId, {
            content: message,
            options: {
                type: "error",
                icon: "✗",
                timeout: 8000,
                closeOnClick: true,
                draggable: true,
                ...options,
            },
        });
    };

    const dismiss = (toastId) => {
        toast.dismiss(toastId);
    };

    const clear = () => {
        toast.clear();
    };

    const handleValidationErrors = (
        errors,
        title = "Please fix the following errors:"
    ) => {
        if (typeof errors === "object" && errors !== null) {
            const errorMessages = [];
            for (const [field, messages] of Object.entries(errors)) {
                if (Array.isArray(messages)) {
                    errorMessages.push(...messages);
                } else {
                    errorMessages.push(messages);
                }
            }
            error(`${title}\n• ${errorMessages.join("\n• ")}`);
        } else {
            error(errors || "An error occurred. Please try again.");
        }
    };

    const handleFlash = (flash) => {
        if (!flash) return;

        if (flash.success) {
            success(flash.success);
        }
        if (flash.error) {
            error(flash.error);
        }
        if (flash.warning) {
            warning(flash.warning);
        }
        if (flash.info) {
            info(flash.info);
        }
    };

    return {
        success,
        error,
        warning,
        info,
        loading,
        updateToSuccess,
        updateToError,
        dismiss,
        clear,
        handleValidationErrors,
        handleFlash,
    };
}
