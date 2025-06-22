<template>
    <div class="flex flex-col items-center justify-center w-full">
        <label for="program_id">Program ID</label>
        <select
            id="program_id"
            name="program_id"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            v-model="modelValue"
        >
            <option
                v-for="program in programs"
                :key="program.program_id"
                :value="program.program_id"
            >
                {{ program.program_id }}
            </option>
        </select>
    </div>
</template>

<script setup>
const props = defineProps({
    modelValue: [String, Number],
    programs: Array,
});
const emit = defineEmits(["update:modelValue"]);
</script>
