<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const props = defineProps({
    assessments: Array,
    programs: Array,
    trainers: Array,
    flash: Object,
});

const showResultsModal = ref(false);
const viewingAssessment = ref(null);
const searchQuery = ref("");
const selectedCourse = ref("");
const selectedStatus = ref("");
const dateFrom = ref("");
const dateTo = ref("");

const filteredAssessments = computed(() => {
    let filtered = props.assessments || [];

    if (searchQuery.value) {
        filtered = filtered.filter(
            (assessment) =>
                assessment.title
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                assessment.program_name
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                assessment.trainer_name
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                assessment.applicant_name
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase())
        );
    }

    if (selectedCourse.value) {
        filtered = filtered.filter(
            (assessment) => assessment.program_id == selectedCourse.value
        );
    }

    if (selectedStatus.value) {
        filtered = filtered.filter(
            (assessment) => assessment.status === selectedStatus.value
        );
    }

    if (dateFrom.value) {
        filtered = filtered.filter(
            (assessment) => assessment.assessment_date >= dateFrom.value
        );
    }

    if (dateTo.value) {
        filtered = filtered.filter(
            (assessment) => assessment.assessment_date <= dateTo.value
        );
    }

    return filtered;
});

const programs = computed(() => {
    return props.programs || [];
});

const hasActiveFilters = computed(() => {
    return (
        searchQuery.value ||
        selectedCourse.value ||
        selectedStatus.value ||
        dateFrom.value ||
        dateTo.value
    );
});

const openResultsModal = (assessment) => {
    viewingAssessment.value = assessment;
    showResultsModal.value = true;
};

const clearFilters = () => {
    searchQuery.value = "";
    selectedCourse.value = "";
    selectedStatus.value = "";
    dateFrom.value = "";
    dateTo.value = "";
};

const getStatusColor = (status) => {
    const colors = {
        pending: "bg-yellow-100 text-yellow-800",
        completed: "bg-blue-100 text-blue-800",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};

const getTypeColor = (type) => {
    const colors = {
        written: "bg-purple-100 text-purple-800",
        practical: "bg-orange-100 text-orange-800",
        oral: "bg-green-100 text-green-800",
        project: "bg-blue-100 text-blue-800",
    };
    return colors[type] || "bg-gray-100 text-gray-800";
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

const formatDuration = (minutes) => {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    if (hours > 0) {
        return `${hours}h ${mins}m`;
    }
    return `${mins}m`;
};

const exportAssessmentResults = () => {
    // TODO: Implement export functionality
};
</script>

<template>
    <Head title="Assessment Results Management" />
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
                            <h3
                                class="text-lg font-semibold text-green-900 relative pb-2 after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-16 after:h-0.5 after:bg-gradient-to-r after:rounded"
                            >
                                Assessment Results Monitoring
                            </h3>
                        </div>
                        <div class="flex space-x-3">
                            <SecondaryButton
                                @click="exportAssessmentResults"
                                class="bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200 hover:border-gray-400 transition-all duration-300"
                            >
                                📄 Export PDF Report
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Filters Section -->
                <div
                    class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 border-b border-gray-200"
                >
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div class="relative">
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                            >
                                Search Assessments
                            </label>
                            <input
                                id="search"
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by title, program, trainer, or applicant"
                                class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300 px-3 py-2"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                            >
                                Filter by Program
                            </label>
                            <select
                                id="program-filter"
                                v-model="selectedCourse"
                                class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300 px-3 py-2"
                            >
                                <option value="">All Programs</option>
                                <option
                                    v-for="program in programs"
                                    :key="program.program_id"
                                    :value="program.program_id"
                                >
                                    {{ program.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                            >
                                Filter by Status
                            </label>
                            <select
                                id="status-filter"
                                v-model="selectedStatus"
                                class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300 px-3 py-2"
                            >
                                <option value="">All Statuses</option>
                                <option value="pending">Pending</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                            >
                                Date From
                            </label>
                            <input
                                id="date-from"
                                v-model="dateFrom"
                                type="date"
                                class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300 px-3 py-2"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-700 mb-1"
                            >
                                Date To
                            </label>
                            <input
                                id="date-to"
                                v-model="dateTo"
                                type="date"
                                class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300 px-3 py-2"
                            />
                        </div>
                    </div>

                    <!-- Clear Filters Button -->
                    <div class="mt-4 flex justify-end">
                        <button
                            v-if="hasActiveFilters"
                            @click="clearFilters"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-300"
                        >
                            <svg
                                class="w-4 h-4 mr-2"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                ></path>
                            </svg>
                            Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="p-6 border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div
                            class="bg-green-50 p-4 rounded-lg border border-green-200"
                        >
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center"
                                    >
                                        <span class="text-white text-xs"
                                            >✓</span
                                        >
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p
                                        class="text-xs font-medium text-green-600"
                                    >
                                        Competent
                                    </p>
                                    <p
                                        class="text-lg font-semibold text-green-900"
                                    >
                                        {{
                                            filteredAssessments.filter(
                                                (a) => a.result === "competent"
                                            ).length
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-red-50 p-4 rounded-lg border border-red-200"
                        >
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center"
                                    >
                                        <span class="text-white text-xs"
                                            >✗</span
                                        >
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p class="text-xs font-medium text-red-600">
                                        Not Yet Competent
                                    </p>
                                    <p
                                        class="text-lg font-semibold text-red-900"
                                    >
                                        {{
                                            filteredAssessments.filter(
                                                (a) =>
                                                    a.result ===
                                                    "not_yet_competent"
                                            ).length
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-gray-50 p-4 rounded-lg border border-gray-200"
                        >
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-8 h-8 bg-gray-500 rounded-full flex items-center justify-center"
                                    >
                                        <span class="text-white text-xs"
                                            >—</span
                                        >
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p
                                        class="text-xs font-medium text-gray-600"
                                    >
                                        Absent
                                    </p>
                                    <p
                                        class="text-lg font-semibold text-gray-900"
                                    >
                                        {{
                                            filteredAssessments.filter(
                                                (a) => a.result === "absent"
                                            ).length
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-blue-50 p-4 rounded-lg border border-blue-200"
                        >
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center"
                                    >
                                        <span class="text-white text-xs"
                                            >📊</span
                                        >
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p
                                        class="text-xs font-medium text-blue-600"
                                    >
                                        Total Assessments
                                    </p>
                                    <p
                                        class="text-lg font-semibold text-blue-900"
                                    >
                                        {{ filteredAssessments.length }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table Section -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Assessment Details
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Program/Trainer
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Applicant
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Result
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="assessment in filteredAssessments"
                                :key="assessment.id"
                                class="hover:bg-gray-50 transition-colors duration-200 group"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div
                                                class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-medium relative overflow-hidden"
                                            >
                                                <span class="relative z-10">
                                                    {{
                                                        getAssessmentIcon(
                                                            assessment.type
                                                        )
                                                    }}
                                                </span>
                                                <div
                                                    class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-500"
                                                ></div>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div
                                                class="text-sm font-medium text-gray-900 group-hover:text-green-600 transition-colors duration-200"
                                            >
                                                {{ assessment.title }}
                                            </div>
                                            <div
                                                class="text-sm text-gray-500 group-hover:text-green-500 transition-colors duration-200"
                                            >
                                                {{ assessment.assessment_date }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ assessment.program_name || "N/A" }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ assessment.trainer_name || "N/A" }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ assessment.applicant_name || "N/A" }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{
                                            assessment.applicant_type ===
                                            "enrolled_trainee"
                                                ? "Enrolled Trainee"
                                                : "External Applicant"
                                        }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        v-if="assessment.result"
                                        :class="assessment.result_color"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full border"
                                    >
                                        {{ assessment.result_status }}
                                    </span>
                                    <span
                                        v-else
                                        :class="
                                            getStatusColor(assessment.status)
                                        "
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full border"
                                    >
                                        {{
                                            assessment.status === "pending"
                                                ? "Pending"
                                                : "Completed"
                                        }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="
                                            getStatusColor(assessment.status)
                                        "
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full border"
                                    >
                                        {{
                                            assessment.status === "pending"
                                                ? "Pending"
                                                : "Completed"
                                        }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                                >
                                    <div class="flex justify-end space-x-2">
                                        <button
                                            @click="
                                                openResultsModal(assessment)
                                            "
                                            class="px-3 py-1 text-blue-600 hover:text-white hover:bg-blue-600 border border-blue-600 rounded transition-all duration-300 font-medium"
                                        >
                                            Results
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div
                        v-if="filteredAssessments.length === 0"
                        class="p-8 text-center bg-gradient-to-br from-white to-green-50"
                    >
                        <div class="text-gray-500">
                            <svg
                                class="mx-auto h-12 w-12 text-gray-400 animate-bounce"
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
                                No assessments found
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{
                                    searchQuery
                                        ? "Try adjusting your filters."
                                        : "No assessments have been created yet."
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Modal -->
        <Modal
            :show="showResultsModal"
            @close="showResultsModal = false"
            custom-width="80vw"
        >
            <div class="p-6" v-if="viewingAssessment">
                <!-- Header -->
                <div class="flex items-start justify-between mb-6">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <svg
                                class="h-8 w-8 text-blue-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">
                                {{ viewingAssessment.title }}
                            </h2>
                            <p
                                class="text-gray-600 mt-1"
                                v-if="viewingAssessment.description"
                            >
                                {{ viewingAssessment.description }}
                            </p>
                            <div class="flex items-center gap-3 mt-3">
                                <span
                                    :class="
                                        getTypeColor(viewingAssessment.type)
                                    "
                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                >
                                    {{
                                        viewingAssessment.type
                                            ?.charAt(0)
                                            .toUpperCase() +
                                        viewingAssessment.type?.slice(1)
                                    }}
                                </span>
                                <span
                                    :class="
                                        getStatusColor(viewingAssessment.status)
                                    "
                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                >
                                    {{
                                        viewingAssessment.status
                                            ?.charAt(0)
                                            .toUpperCase() +
                                        viewingAssessment.status?.slice(1)
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <button
                        @click="showResultsModal = false"
                        class="text-gray-400 hover:text-gray-600 p-1 rounded-full hover:bg-gray-100"
                    >
                        <svg
                            class="h-6 w-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Assessment Information -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3
                            class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2"
                        >
                            <svg
                                class="w-5 h-5 text-blue-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            Assessment Details
                        </h3>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-500"
                                    >Program:</span
                                >
                                <span
                                    class="text-sm text-gray-900 font-semibold"
                                >
                                    {{
                                        viewingAssessment.program_name || "N/A"
                                    }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-500"
                                    >Assessment Date:</span
                                >
                                <span class="text-sm text-gray-900">{{
                                    new Date(
                                        viewingAssessment.assessment_date
                                    ).toLocaleDateString("en-US", {
                                        year: "numeric",
                                        month: "long",
                                        day: "numeric",
                                    })
                                }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-500"
                                    >Type:</span
                                >
                                <span class="text-sm text-gray-900">
                                    {{
                                        viewingAssessment.type
                                            ?.charAt(0)
                                            .toUpperCase() +
                                        viewingAssessment.type?.slice(1)
                                    }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-500"
                                    >Status:</span
                                >
                                <span
                                    :class="
                                        getStatusColor(viewingAssessment.status)
                                    "
                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                >
                                    {{
                                        viewingAssessment.status
                                            ?.charAt(0)
                                            .toUpperCase() +
                                        viewingAssessment.status?.slice(1)
                                    }}
                                </span>
                            </div>
                            <div
                                class="flex justify-between items-center"
                                v-if="viewingAssessment.result"
                            >
                                <span class="text-sm font-medium text-gray-500"
                                    >Result:</span
                                >
                                <span
                                    :class="viewingAssessment.result_color"
                                    class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                >
                                    {{ viewingAssessment.result.toUpperCase() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Participants Information -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3
                            class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2"
                        >
                            <svg
                                class="w-5 h-5 text-green-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0z"
                                />
                            </svg>
                            Participants
                        </h3>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-500"
                                    >Applicant:</span
                                >
                                <span
                                    class="text-sm text-gray-900 font-semibold"
                                >
                                    {{
                                        viewingAssessment.applicant_name ||
                                        "N/A"
                                    }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-medium text-gray-500"
                                    >Trainer/Assessor:</span
                                >
                                <span class="text-sm text-gray-900">
                                    {{
                                        viewingAssessment.trainer_name || "N/A"
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Assessment Status Information -->
                    <div class="lg:col-span-2">
                        <div class="bg-blue-50 rounded-lg p-6">
                            <h3
                                class="text-lg font-semibold text-blue-900 mb-4 flex items-center gap-2"
                            >
                                <svg
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                Assessment Status
                            </h3>

                            <div class="flex justify-center">
                                <div
                                    class="text-center bg-white rounded-lg p-6 border-2"
                                >
                                    <div
                                        class="flex items-center justify-center gap-3 mb-2"
                                    >
                                        <span
                                            :class="
                                                viewingAssessment.result_color ||
                                                getStatusColor(
                                                    viewingAssessment.status
                                                )
                                            "
                                            class="inline-flex px-3 py-1 text-lg font-semibold rounded-full"
                                        >
                                            {{
                                                viewingAssessment.result_status ||
                                                viewingAssessment.status
                                                    ?.charAt(0)
                                                    .toUpperCase() +
                                                    viewingAssessment.status?.slice(
                                                        1
                                                    )
                                            }}
                                        </span>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        Assessment status based on current
                                        progress
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div
                    class="flex items-center justify-end gap-3 pt-6 border-t mt-6"
                >
                    <SecondaryButton @click="showResultsModal = false">
                        Close
                    </SecondaryButton>
                </div>
            </div>
        </Modal>
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
