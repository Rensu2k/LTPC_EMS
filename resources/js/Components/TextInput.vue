<script setup>
import { onMounted, ref } from "vue";

const model = defineModel({
    type: String,
    required: true,
    get: (value) => {
        // Convert any non-string values to strings
        return typeof value === "string" ? value : String(value || "");
    },
    set: (value) => {
        // Ensure we always store strings
        return typeof value === "string" ? value : String(value || "");
    },
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute("autofocus")) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        v-model="model"
        ref="input"
    />
</template>
