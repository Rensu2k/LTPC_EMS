<template>
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 translate-y-1"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-1"
    >
        <div
            v-if="show"
            :class="[
                'rounded-lg p-4 shadow-sm border-l-4 relative',
                alertClasses,
            ]"
            role="alert"
        >
            <div class="flex items-start">
                <!-- Icon -->
                <div class="flex-shrink-0">
                    <component :is="iconComponent" :class="iconClasses" />
                </div>

                <!-- Content -->
                <div class="ml-3 flex-1">
                    <h3
                        v-if="title"
                        :class="titleClasses"
                        class="text-sm font-medium"
                    >
                        {{ title }}
                    </h3>
                    <div :class="messageClasses" class="text-sm">
                        <slot v-if="$slots.default" />
                        <p v-else>{{ message }}</p>
                    </div>

                    <!-- Action buttons -->
                    <div v-if="$slots.actions || actionText" class="mt-4">
                        <div class="flex space-x-3">
                            <slot name="actions" />
                            <button
                                v-if="actionText"
                                @click="$emit('action')"
                                :class="actionButtonClasses"
                                class="text-sm font-medium underline hover:no-underline focus:outline-none focus:ring-2 focus:ring-offset-2 rounded"
                            >
                                {{ actionText }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Close button -->
                <div v-if="dismissible" class="ml-auto pl-3">
                    <button
                        @click="dismiss"
                        :class="closeButtonClasses"
                        class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2 hover:opacity-75"
                    >
                        <span class="sr-only">Dismiss</span>
                        <XMarkIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { computed, defineEmits } from "vue";
import {
    CheckCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    XCircleIcon,
    XMarkIcon,
} from "@heroicons/vue/24/outline";

const props = defineProps({
    type: {
        type: String,
        default: "info",
        validator: (value) =>
            ["success", "error", "warning", "info"].includes(value),
    },
    title: {
        type: String,
        default: "",
    },
    message: {
        type: String,
        default: "",
    },
    show: {
        type: Boolean,
        default: true,
    },
    dismissible: {
        type: Boolean,
        default: true,
    },
    actionText: {
        type: String,
        default: "",
    },
});

const emit = defineEmits(["dismiss", "action"]);

const alertClasses = computed(() => {
    const classes = {
        success: "bg-green-50 border-green-400",
        error: "bg-red-50 border-red-400",
        warning: "bg-yellow-50 border-yellow-400",
        info: "bg-blue-50 border-blue-400",
    };
    return classes[props.type];
});

const iconComponent = computed(() => {
    const icons = {
        success: CheckCircleIcon,
        error: XCircleIcon,
        warning: ExclamationTriangleIcon,
        info: InformationCircleIcon,
    };
    return icons[props.type];
});

const iconClasses = computed(() => {
    const classes = {
        success: "text-green-400 h-5 w-5",
        error: "text-red-400 h-5 w-5",
        warning: "text-yellow-400 h-5 w-5",
        info: "text-blue-400 h-5 w-5",
    };
    return classes[props.type];
});

const titleClasses = computed(() => {
    const classes = {
        success: "text-green-800",
        error: "text-red-800",
        warning: "text-yellow-800",
        info: "text-blue-800",
    };
    return classes[props.type];
});

const messageClasses = computed(() => {
    const classes = {
        success: "text-green-700",
        error: "text-red-700",
        warning: "text-yellow-700",
        info: "text-blue-700",
    };
    return `${classes[props.type]} ${props.title ? "mt-1" : ""}`;
});

const actionButtonClasses = computed(() => {
    const classes = {
        success: "text-green-800 hover:text-green-900 focus:ring-green-500",
        error: "text-red-800 hover:text-red-900 focus:ring-red-500",
        warning: "text-yellow-800 hover:text-yellow-900 focus:ring-yellow-500",
        info: "text-blue-800 hover:text-blue-900 focus:ring-blue-500",
    };
    return classes[props.type];
});

const closeButtonClasses = computed(() => {
    const classes = {
        success: "text-green-400 focus:ring-green-500",
        error: "text-red-400 focus:ring-red-500",
        warning: "text-yellow-400 focus:ring-yellow-500",
        info: "text-blue-400 focus:ring-blue-500",
    };
    return classes[props.type];
});

const dismiss = () => {
    emit("dismiss");
};
</script>
