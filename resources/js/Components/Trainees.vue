<template>
  <div class="flex flex-col items-center justify-center w-full">
    <label for="trainee_id">Trainee ID</label>
    <select
      id="trainee_id"
      name="trainee_id"
      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
      v-model="modelValue"
    >
      <option
        v-for="trainee in trainees"
        :key="trainee.id"
        :value="trainee.id"
      >
        {{ trainee.id }}
      </option>
    </select>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: [String, Number],
  trainees: Array
});
const emit = defineEmits(['update:modelValue']);
</script>