<template>
  <div class="p-4 bg-white border border-gray-200 rounded-lg shadow-sm">
    <div class="flex items-center justify-between">
      <div>
        <h4 class="font-medium text-gray-900">{{ assessment.title }}</h4>
        <p class="text-sm text-gray-500">{{ assessment.course }} - {{ assessment.trainee }}</p>
      </div>
      <div class="flex items-center space-x-2">
        <span :class="getStatusColor(assessment.status)" 
              class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
          {{ getStatusText(assessment.status) }}
        </span>
        <span class="text-xs text-gray-400">
          {{ assessment.date }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  assessment: {
    type: Object,
    required: true
  }
});

const getStatusColor = (status) => {
  const colors = {
    competent: 'bg-green-100 text-green-800',
    not_yet_competent: 'bg-yellow-100 text-yellow-800',
    no_assessment: 'bg-gray-100 text-gray-800'
  };
  return colors[status] || 'bg-gray-100 text-gray-800';
};

const getStatusText = (status) => {
  const texts = {
    competent: 'Competent',
    not_yet_competent: 'Not Yet Competent',
    no_assessment: 'No Assessment'
  };
  return texts[status] || 'Unknown';
};
</script>
