<template>
  <div class="flex flex-col items-center justify-center w-full">
    <label for="payment_id">Payment ID</label>
    <select
      id="payment_id"
      name="payment_id"
      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
      v-model="modelValue"
    >
      <option
        v-for="payment in payments"
        :key="payment.id"
        :value="payment.id"
      >
        {{ payment.id }}
      </option>
    </select>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: [String, Number],
  payments: Array
});
const emit = defineEmits(['update:modelValue']);
</script>