<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import Pagination from "@/Components/Pagination.vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    assessments: Object, // Changed from Array to Object to support pagination
    comprehensive_assessments: Array, // All assessments for statistics (including all attempts)
    programs: Array,
    trainers: Array,
    flash: Object,
    filters: Object, // Added filters prop
});

const showResultsModal = ref(false);
const viewingAssessment = ref(null);
const searchQuery = ref(props.filters?.search || "");
const selectedProgram = ref(props.filters?.program || "");
const selectedResult = ref(props.filters?.result || "");
const dateFrom = ref(props.filters?.date_from || "");
const dateTo = ref(props.filters?.date_to || "");
const perPage = ref(props.filters?.per_page || 10);
const showFilters = ref(false);

// Add search functionality
const performSearch = () => {
        router.get(
        route("admin.assessments"),
        {
            search: searchQuery.value,
            program: selectedProgram.value,
            result: selectedResult.value || "",
            date_from: dateFrom.value,
            date_to: dateTo.value,
            per_page: perPage.value,
            page: 1, // Reset to first page when searching
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

// Add change per page functionality
const changePerPage = () => {
    performSearch();
};

// Watch for filter changes and trigger search automatically
let searchTimeout = null;
watch(
    [selectedProgram, selectedResult, dateFrom, dateTo, searchQuery],
    () => {
        // Clear previous timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }

        // Debounce search to avoid too many requests while user is typing
        searchTimeout = setTimeout(() => {
            performSearch();
        }, 500); // 500ms delay
    },
    { deep: true }
);

// Use backend-filtered data directly (no frontend filtering needed)
const filteredAssessments = computed(() => {
    return props.assessments?.data || [];
});

const programs = computed(() => {
    return props.programs || [];
});

const hasActiveFilters = computed(() => {
    return (
        searchQuery.value ||
        selectedProgram.value ||
        selectedResult.value ||
        dateFrom.value ||
        dateTo.value
    );
});

// Percentage calculations for comprehensive results (UNIQUE applicants only)
const assessmentPercentages = computed(() => {
    const assessments = props.comprehensive_assessments || [];

    // Group assessments by unique applicants and get their latest attempt
    const uniqueApplicants = new Map();

    assessments.forEach((assessment) => {
        // Create unique key based on applicant type and identifier
        let applicantKey;
        if (assessment.applicant_type === "enrolled_trainee") {
            applicantKey = `trainee_${assessment.trainee_id}`;
        } else {
            applicantKey = `external_${assessment.external_applicant_email}`;
        }

        // Keep only the latest assessment for each unique applicant
        if (
            !uniqueApplicants.has(applicantKey) ||
            new Date(assessment.assessment_date) >
                new Date(uniqueApplicants.get(applicantKey).assessment_date)
        ) {
            uniqueApplicants.set(applicantKey, assessment);
        }
    });

    // Convert to array of unique applicant assessments
    const uniqueAssessments = Array.from(uniqueApplicants.values());
    const total = uniqueAssessments.length;

    if (total === 0) {
        return {
            completedVsAssessed: 0,
            competentVsNotCompetent: 0,
            completedCount: 0,
            assessedCount: 0,
            competentCount: 0,
            notCompetentCount: 0,
        };
    }

    // Completed applicants vs total assessed (exclude absent applicants)
    // Ensure we only count completed assessments that are not absent
    const completedCount = uniqueAssessments.filter(
        (a) => a.status === "completed" && a.result !== "absent"
    ).length;
    const assessedCount = uniqueAssessments.filter(
        (a) => a.result !== "absent"
    ).length; // Only count applicants who actually took the assessment

    // Competent vs not competent among applicants with actual results (exclude absent and pending)
    const competentCount = uniqueAssessments.filter(
        (a) => a.result === "competent"
    ).length;
    const notCompetentCount = uniqueAssessments.filter(
        (a) => a.result === "not_yet_competent"
    ).length;
    // Only include assessments that have actual results (competent or not_yet_competent)
    const totalWithResults = uniqueAssessments.filter(
        (a) => a.result === "competent" || a.result === "not_yet_competent"
    ).length;

    return {
        completedVsAssessed:
            assessedCount > 0
                ? Math.min(
                      Math.round((completedCount / assessedCount) * 100),
                      100
                  )
                : 0,
        competentVsNotCompetent:
            totalWithResults > 0
                ? Math.min(
                      Math.round((competentCount / totalWithResults) * 100),
                      100
                  )
                : 0,
        completedCount,
        assessedCount,
        competentCount,
        notCompetentCount,
        totalWithResults,
    };
});

const openResultsModal = (assessment) => {
    viewingAssessment.value = assessment;
    showResultsModal.value = true;
};

const viewAssessmentHistory = (assessment) => {
    // Navigate to assessment history page
    router.visit(`/admin/assessments/${assessment.id}/assessment-history`);
};

const clearFilters = () => {
    searchQuery.value = "";
    selectedProgram.value = "";
    selectedResult.value = "";
    dateFrom.value = "";
    dateTo.value = "";
    performSearch();
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

const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
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
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                        <div class="flex items-end">
                            <button
                                @click="showFilters = !showFilters"
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
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"
                                    />
                                </svg>
                                {{ showFilters ? "Hide" : "Show" }} Filters
                            </button>
                        </div>
                        <!-- Advanced Filters -->
                        <div
                            v-if="showFilters"
                            class="col-span-full grid grid-cols-1 md:grid-cols-4 gap-4 pt-4 border-t border-gray-200"
                        >
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-1"
                                >
                                    Filter by Program
                                </label>
                                <select
                                    id="program-filter"
                                    v-model="selectedProgram"
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
                                    Filter by Result
                                </label>
                                <select
                                    id="result-filter"
                                    v-model="selectedResult"
                                    class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300 px-3 py-2"
                                >
                                    <option value="">All Results</option>
                                    <option value="competent">Competent</option>
                                    <option value="not_yet_competent">
                                        Not Yet Competent
                                    </option>
                                    <option value="absent">Absent</option>
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
                    </div>

                    <!-- Clear Filters Button -->
                    <div v-if="hasActiveFilters" class="mt-4 flex justify-end">
                        <button
                            @click="clearFilters"
                            class="inline-flex items-center gap-2 px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm font-medium rounded-lg transition-colors duration-200"
                        >
                            <svg
                                class="w-4 h-4"
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
                            Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="px-4 py-3 border-b border-gray-200">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div
                            class="text-center p-2 bg-green-50 rounded border border-green-200"
                        >
                            <p class="text-xs font-medium text-green-600 mb-1">
                                Competent
                            </p>
                            <p class="text-sm font-semibold text-green-900">
                                {{
                                    (
                                        props.comprehensive_assessments || []
                                    ).filter((a) => a.result === "competent")
                                        .length
                                }}
                                <span
                                    v-if="hasActiveFilters"
                                    class="text-xs text-green-700 ml-1"
                                >
                                    ({{
                                        Math.round(
                                            ((
                                                props.comprehensive_assessments ||
                                                []
                                            ).filter(
                                                (a) => a.result === "competent"
                                            ).length /
                                                (
                                                    props.comprehensive_assessments ||
                                                    []
                                                ).length) *
                                                100
                                        )
                                    }}%)
                                </span>
                            </p>
                        </div>

                        <div
                            class="text-center p-2 bg-red-50 rounded border border-red-200"
                        >
                            <p class="text-xs font-medium text-red-600 mb-1">
                                Not Yet Competent
                            </p>
                            <p class="text-sm font-semibold text-red-900">
                                {{
                                    (
                                        props.comprehensive_assessments || []
                                    ).filter(
                                        (a) => a.result === "not_yet_competent"
                                    ).length
                                }}
                                <span
                                    v-if="hasActiveFilters"
                                    class="text-xs text-red-700 ml-1"
                                >
                                    ({{
                                        Math.round(
                                            ((
                                                props.comprehensive_assessments ||
                                                []
                                            ).filter(
                                                (a) =>
                                                    a.result ===
                                                    "not_yet_competent"
                                            ).length /
                                                (
                                                    props.comprehensive_assessments ||
                                                    []
                                                ).length) *
                                                100
                                        )
                                    }}%)
                                </span>
                            </p>
                        </div>

                        <div
                            class="text-center p-2 bg-gray-50 rounded border border-gray-200"
                        >
                            <p class="text-xs font-medium text-gray-600 mb-1">
                                Absent
                            </p>
                            <p class="text-sm font-semibold text-gray-900">
                                {{
                                    (
                                        props.comprehensive_assessments || []
                                    ).filter((a) => a.result === "absent")
                                        .length
                                }}
                                <span
                                    v-if="hasActiveFilters"
                                    class="text-xs text-gray-700 ml-1"
                                >
                                    ({{
                                        Math.round(
                                            ((
                                                props.comprehensive_assessments ||
                                                []
                                            ).filter(
                                                (a) => a.result === "absent"
                                            ).length /
                                                (
                                                    props.comprehensive_assessments ||
                                                    []
                                                ).length) *
                                                100
                                        )
                                    }}%)
                                </span>
                            </p>
                        </div>

                        <div
                            class="text-center p-2 bg-blue-50 rounded border border-blue-200"
                        >
                            <p class="text-xs font-medium text-blue-600 mb-1">
                                Total Assessments
                            </p>
                            <p class="text-sm font-semibold text-blue-900">
                                {{
                                    (props.comprehensive_assessments || [])
                                        .length
                                }}
                                <span
                                    v-if="hasActiveFilters"
                                    class="text-xs text-blue-700 ml-1"
                                    >(100%)</span
                                >
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Filtered Results Analysis -->
                <div
                    v-if="hasActiveFilters"
                    class="px-4 py-3 border-b border-gray-200 bg-blue-50"
                >
                    <h4 class="text-sm font-medium text-blue-900 mb-3">
                        Filtered Results Analysis
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            class="bg-white p-3 rounded border border-blue-200"
                        >
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs text-gray-600"
                                    >Completion Rate</span
                                >
                                <span class="text-lg font-bold text-green-600">
                                    {{
                                        assessmentPercentages.completedVsAssessed
                                    }}%
                                </span>
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ assessmentPercentages.completedCount }}/{{
                                    assessmentPercentages.assessedCount
                                }}
                                completed
                            </div>
                        </div>

                        <div
                            class="bg-white p-3 rounded border border-blue-200"
                        >
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs text-gray-600"
                                    >Competency Rate</span
                                >
                                <span class="text-lg font-bold text-blue-600">
                                    {{
                                        assessmentPercentages.competentVsNotCompetent
                                    }}%
                                </span>
                            </div>
                            <div class="text-xs text-gray-500">
                                {{ assessmentPercentages.competentCount }}/{{
                                    assessmentPercentages.totalWithResults
                                }}
                                competent
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Results Summary -->
                <div
                    v-if="
                        assessments &&
                        assessments.data &&
                        assessments.data.length > 0
                    "
                    class="px-6 py-3 bg-white border-b border-gray-200"
                >
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing
                            <span class="font-medium">{{
                                assessments.from || 0
                            }}</span>
                            to
                            <span class="font-medium">{{
                                assessments.to || 0
                            }}</span>
                            of
                            <span class="font-medium">{{
                                assessments.total || 0
                            }}</span>
                            results
                        </div>
                        <div class="text-sm text-gray-500">
                            Page {{ assessments.current_page || 1 }} of
                            {{ assessments.last_page || 1 }}
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
                                                {{
                                                    formatDate(
                                                        assessment.assessment_date
                                                    )
                                                }}
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
                                        <button
                                            @click="
                                                viewAssessmentHistory(
                                                    assessment
                                                )
                                            "
                                            class="px-3 py-1 text-green-600 hover:text-white hover:bg-green-600 border border-green-600 rounded transition-all duration-300 font-medium"
                                        >
                                            History
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <Pagination
                        v-if="
                            assessments &&
                            assessments.data &&
                            assessments.data.length > 0
                        "
                        :data="assessments"
                    />

                    <div
                        v-if="
                            !assessments ||
                            !assessments.data ||
                            assessments.data.length === 0
                        "
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
