<template>
  <div class="flex flex-col items-center justify-center w-full">
    <label for="course_id">Course ID</label>
    <select
      id="course_id"
      name="course_id"
      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
      v-model="modelValue"
    >
      <option
        v-for="course in courses"
        :key="course.id"
        :value="course.id"
      >
        {{ course.id }}
      </option>
    </select>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: [String, Number],
  courses: Array
});
const emit = defineEmits(['update:modelValue']);
</script>