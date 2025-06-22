<template>
    <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <h4 class="font-medium text-gray-900">{{ training.title }}</h4>
                <p class="text-sm text-gray-500">{{ training.program }}</p>
            </div>
            <div class="flex items-center space-x-2">
                <span
                    :class="
                        training.status === 'complete'
                            ? 'bg-green-100 text-green-800'
                            : 'bg-red-100 text-red-800'
                    "
                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                >
                    {{
                        training.status === "complete"
                            ? "Complete"
                            : "Incomplete"
                    }}
                </span>
                <span class="text-xs text-gray-400">
                    Handled by: {{ training.officer }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    training: {
        type: Object,
        required: true,
    },
});
</script>
