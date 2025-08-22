<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";

const props = defineProps({
    assessment: Object,
    history: Array,
    applicant: Object,
});

const assessment = computed(() => props.assessment || {});
const applicant = computed(() => props.applicant || {});
const assessmentHistory = computed(() => props.history || []);

const goBack = () => {
    router.visit("/admin/assessments");
};

const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const getStatusColor = (status) => {
    const colors = {
        pending: "bg-yellow-100 text-yellow-800",
        completed: "bg-blue-100 text-blue-800",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};

const getResultColor = (result) => {
    const colors = {
        competent: "bg-green-100 text-green-800",
        not_yet_competent: "bg-red-100 text-red-800",
        absent: "bg-gray-100 text-gray-800",
    };
    return colors[result] || "bg-gray-100 text-gray-800";
};

const getAssessmentIcon = (type) => {
    const icons = {
        written: "📝",
        practical: "🔧",
        oral: "🗣️",
        project: "📋",
    };
    return icons[type] || "📊";
};
</script>

<template>
    <Head :title="`${applicant.name || 'Unknown'} - Assessment History`" />
    <AuthenticatedLayout>
        <div class="py-8 px-8 bg-gray-50 min-h-screen">
            <div
                class="bg-white rounded-xl shadow-sm overflow-hidden animate-fade-in border border-gray-100"
            >
                <!-- Header Section -->
                <div
                    class="p-6 border-b border-gray-200 bg-gradient-to-r from-green-50 to-emerald-50 relative"
                >
                    <div
                        class="absolute bottom-0 left-0 w-full h-0.5 bg-gradient-to-r from-green-500 to-emerald-500"
                    ></div>
                    <div
                        class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                    >
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <button
                                    @click="goBack"
                                    class="inline-flex items-center rounded-md border border-gray-300 bg-gray-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25"
                                >
                                    ← Back to Assessment Results
                                </button>
                            </div>
                            <h3
                                class="text-lg font-semibold text-green-900 relative pb-2 after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-20 after:h-0.5 after:bg-gradient-to-r after:rounded"
                            >
                                Assessment History
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">
                                All assessment attempts for
                                {{ applicant.name || "Unknown Applicant" }}
                            </p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-green-600">
                                {{ assessmentHistory.length }}
                            </div>
                            <div class="text-sm text-gray-500">
                                Total Attempts
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assessment Info Card -->
                <div class="p-6 border-b border-gray-200 bg-blue-50">
                    <div
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4"
                    >
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">
                                Assessment Type
                            </h4>
                            <div class="flex items-center gap-2">
                                <span class="text-lg">{{
                                    getAssessmentIcon(assessment.type)
                                }}</span>
                                <span
                                    class="text-sm text-gray-600 capitalize"
                                    >{{ assessment.type || "Unknown" }}</span
                                >
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">
                                Program
                            </h4>
                            <p class="text-sm text-gray-600">
                                {{
                                    assessment.program_name || "Unknown Program"
                                }}
                            </p>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">
                                Applicant
                            </h4>
                            <p class="text-sm text-gray-600">
                                {{ applicant.name || "Unknown" }}
                            </p>
                            <p class="text-xs text-gray-500">
                                {{ applicant.email || "" }}
                            </p>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">
                                Latest Result
                            </h4>
                            <span
                                v-if="assessmentHistory.length > 0"
                                :class="
                                    getResultColor(assessmentHistory[0].result)
                                "
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            >
                                {{
                                    assessmentHistory[0].result_status || "N/A"
                                }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Assessment Attempts Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Attempt #
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Program
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Date & Time
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Result
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Assessor
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="(attempt, index) in assessmentHistory"
                                :key="attempt.id"
                                class="hover:bg-gray-50 transition-colors duration-200"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        Attempt
                                        {{ assessmentHistory.length - index }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ attempt.program_name || "N/A" }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{
                                            formatDate(attempt.assessment_date)
                                        }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="getStatusColor(attempt.status)"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{
                                            attempt.status === "pending"
                                                ? "Pending"
                                                : "Completed"
                                        }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        v-if="attempt.result"
                                        :class="getResultColor(attempt.result)"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{
                                            attempt.result_status ||
                                            attempt.result.toUpperCase()
                                        }}
                                    </span>
                                    <span v-else class="text-sm text-gray-500">
                                        N/A
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{
                                            attempt.trainer_name ||
                                            attempt.assessor_name ||
                                            "N/A"
                                        }}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div
                    v-if="assessmentHistory.length === 0"
                    class="text-center py-12"
                >
                    <svg
                        class="mx-auto h-12 w-12 text-gray-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">
                        No assessment history found
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        This applicant hasn't taken any assessments yet.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.6s ease-out;
}
</style>
