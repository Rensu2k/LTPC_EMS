<template>
  <div class="flex flex-col items-center justify-center w-full">
    <label for="trainer_id">Trainer ID</label>
    <select
      id="trainer_id"
      name="trainer_id"
      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
      v-model="modelValue"
    >
      <option
        v-for="trainer in trainers"
        :key="trainer.id"
        :value="trainer.id"
      >
        {{ trainer.id }}
      </option>
    </select>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: [String, Number],
  trainers: Array
});
const emit = defineEmits(['update:modelValue']);
</script>
