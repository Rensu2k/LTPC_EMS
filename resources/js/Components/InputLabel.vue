<!--
  LTPC Enrollment Management System (LTPC_EMS)

  Copyright (c) 2025-2026 Clarence Buenaflor & Jester Pastor. All rights reserved.
  Unauthorized copying or distribution is strictly prohibited.
-->
<script setup>
defineProps({
    value: {
        type: String,
    },
});
</script>

<template>
    <label class="block text-sm font-medium text-gray-700">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
