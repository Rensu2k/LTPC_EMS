import { useToast } from "vue-toastification";

export function useNotifications() {
    const toast = useToast();

    // Success notifications
    const success = (message, options = {}) => {
        toast.success(message, {
            icon: "✓",
            ...options,
        });
    };

    // Error notifications
    const error = (message, options = {}) => {
        toast.error(message, {
            icon: "✗",
            timeout: 8000, // Longer timeout for errors
            ...options,
        });
    };

    // Warning notifications
    const warning = (message, options = {}) => {
        toast.warning(message, {
            icon: "⚠",
            timeout: 6000,
            ...options,
        });
    };

    // Info notifications
    const info = (message, options = {}) => {
        toast.info(message, {
            icon: "ℹ",
            ...options,
        });
    };

    // Loading notification (returns toast ID for updating/dismissing)
    const loading = (message = "Processing...", options = {}) => {
        return toast.info(message, {
            icon: "⏳",
            timeout: false, // Don't auto-dismiss
            closeOnClick: false,
            draggable: false,
            ...options,
        });
    };

    // Update a loading notification to success
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

    // Update a loading notification to error
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

    // Dismiss a specific toast
    const dismiss = (toastId) => {
        toast.dismiss(toastId);
    };

    // Clear all toasts
    const clear = () => {
        toast.clear();
    };

    // Handle Laravel validation errors
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

    // Handle Laravel flash messages
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
