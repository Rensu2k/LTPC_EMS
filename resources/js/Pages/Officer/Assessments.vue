<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import SearchInput from "@/Components/SearchInput.vue";
import AssessmentRegistrationModal from "@/Components/AssessmentRegistrationModal.vue";
import AssessmentDetailsModal from "@/Components/AssessmentDetailsModal.vue";
import AssessmentReassessmentModal from "@/Components/AssessmentReassessmentModal.vue";
import DeleteConfirmationModal from "@/Components/DeleteConfirmationModal.vue";
import Pagination from "@/Components/Pagination.vue";
import { useNotifications } from "@/composables/useNotifications";

const props = defineProps({
    assessments: [Object, Array], // Support both pagination object and legacy array
    programs: Array,
    trainees: Array,
    trainers: Array,
    filters: Object,
    flash: Object,
});

// Access page props for flash messages
const page = usePage();
const notifications = useNotifications();

// Watch for flash messages
watch(
    () => page.props.flash,
    (flash) => {
        notifications.handleFlash(flash);
    },
    { deep: true, immediate: true }
);

const searchQuery = ref(props.filters?.search || "");
const selectedStatus = ref(props.filters?.status || "All Statuses");
const showRegistrationModal = ref(false);
const showDetailsModal = ref(false);
const showReassessmentModal = ref(false);
const showDeleteModal = ref(false);
const selectedAssessment = ref(null);
const processing = ref(false);
const assessmentErrors = ref({});

// Helper function to get the actual assessments data (handles both paginated and non-paginated)
const getAssessmentsData = () => {
    return props.assessments?.data || props.assessments || [];
};

// Helper function to find an assessment by ID
const findAssessmentById = (id) => {
    const assessmentsData = getAssessmentsData();
    return assessmentsData.find((a) => a.id === id);
};

// Process assessments data
const assessmentsList = computed(() => {
    const assessmentsData = props.assessments?.data || props.assessments;

    if (!assessmentsData || !Array.isArray(assessmentsData)) {
        return [];
    }

    return assessmentsData.map((assessment) => ({
        id: assessment.id,
        title: assessment.title,
        description: assessment.description,
        type: assessment.type,
        status: assessment.status,
        result: assessment.result,
        result_status: assessment.result_status,
        result_color: assessment.result_color,
        can_be_reassessed: assessment.can_be_reassessed,
        requires_reenrollment: assessment.requires_reenrollment,
        program_name: assessment.program_name,
        program_id: assessment.program_id,
        applicant_name: assessment.applicant_name,
        applicant_type: assessment.applicant_type,
        trainer_name: assessment.trainer_name,
        assessment_date: new Date(
            assessment.assessment_date
        ).toLocaleDateString(),
        assessment_fee: assessment.assessment_fee,
        payment_status: assessment.payment_status,
        payment_required: assessment.payment_required,
        payment_completed: assessment.payment_completed,
        attempt_number: assessment.attempt_number,
        trainee: assessment.trainee,
        original_assessment_for_history:
            assessment.original_assessment_for_history,
    }));
});

function getGrade(percentage) {
    if (percentage >= 90) return "A";
    if (percentage >= 80) return "B";
    if (percentage >= 70) return "C";
    if (percentage >= 60) return "D";
    return "F";
}

// Check if assessment is graded (based on status)
function isGraded(assessment) {
    return assessment.status === "completed";
}

// Check if assessment payment is completed
function isPaid(assessment) {
    return assessment.payment_status === "paid";
}

// Check if assessment can be deleted
function isDeletable(assessment) {
    return assessment.is_deletable !== undefined
        ? assessment.is_deletable
        : !isGraded(assessment) && !isPaid(assessment);
}

const addAssessment = () => {
    assessmentErrors.value = {};
    showRegistrationModal.value = true;
};

const closeRegistrationModal = () => {
    assessmentErrors.value = {};
    showRegistrationModal.value = false;
};

const onAssessmentSubmitted = () => {
    assessmentErrors.value = {};
    showRegistrationModal.value = false;
    // Refresh the page to show the new assessment
    window.location.reload();
};

const onReassessmentSubmitted = () => {
    window.location.reload();
};

const viewAssessment = (assessment) => {
    const actualAssessment = findAssessmentById(assessment.id);
    selectedAssessment.value = actualAssessment;
    showDetailsModal.value = true;
};

const editAssessment = (assessment) => {
    if (isGraded(assessment)) {
        notifications.warning(
            "Cannot edit finalized assessments. This assessment has already been graded."
        );
        return;
    }
    router.visit(`/officer/assessments/${assessment.id}/edit`);
};

const reassessment = (assessment) => {
    if (!assessment.can_be_reassessed) {
        if (assessment.requires_reenrollment) {
            notifications.warning(
                "Maximum of 3 assessment attempts reached. The applicant must re-enroll in the program to take the assessment again."
            );
        } else {
            notifications.warning(
                "This assessment cannot be re-assessed. Either the assessment result is already competent, or there is already a pending re-assessment scheduled."
            );
        }
        return;
    }
    selectedAssessment.value = assessment;
    showReassessmentModal.value = true;
};

const deleteAssessment = (assessment) => {
    if (!isDeletable(assessment)) {
        if (isGraded(assessment)) {
            notifications.warning(
                "Cannot delete finalized assessments. This assessment has already been graded."
            );
        } else if (isPaid(assessment)) {
            notifications.warning(
                "Cannot delete paid assessments. This assessment payment has already been processed."
            );
        } else {
            notifications.warning("This assessment cannot be deleted.");
        }
        return;
    }
    selectedAssessment.value = assessment;
    showDeleteModal.value = true;
};

const closeDetailsModal = () => {
    showDetailsModal.value = false;
    selectedAssessment.value = null;
};

const closeReassessmentModal = () => {
    showReassessmentModal.value = false;
    selectedAssessment.value = null;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedAssessment.value = null;
};

const confirmDelete = () => {
    if (!selectedAssessment.value) return;

    processing.value = true;
    router.delete(`/officer/assessments/${selectedAssessment.value.id}`, {
        onSuccess: () => {
            processing.value = false;
            closeDeleteModal();
            // Refresh to show updated list
            window.location.reload();
        },
        onError: () => {
            processing.value = false;
        },
    });
};

const handleEditFromDetails = (assessment) => {
    closeDetailsModal();
    editAssessment({ id: assessment.id });
};

const handleReassessmentFromDetails = (assessment) => {
    closeDetailsModal();
    reassessment(assessment);
};

const viewAssessmentHistory = (assessment) => {
    // Use the original assessment ID for history navigation
    const originalId =
        assessment.original_assessment_for_history || assessment.id;
    router.visit(`/officer/assessments/${originalId}/assessment-history`);
};

// Computed property for filtered assessments
const filteredAssessments = computed(() => {
    let filtered = assessmentsList.value;

    // Filter by status
    if (selectedStatus.value !== "All Statuses") {
        filtered = filtered.filter(
            (assessment) => assessment.status === selectedStatus.value
        );
    }

    // Filter by search query
    if (searchQuery.value) {
        filtered = filtered.filter(
            (assessment) =>
                assessment.title
                    .toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                assessment.applicant_name
                    .toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                assessment.program_name
                    .toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                assessment.trainer_name
                    .toLowerCase()
                    .includes(searchQuery.value.toLowerCase())
        );
    }

    return filtered;
});

const exportData = () => {
    // TODO: Implement export functionality
};

// Debounced search function
let searchTimeout;
const performSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.visit(route("officer.assessments"), {
            data: {
                search: searchQuery.value,
                status: selectedStatus.value !== "All Statuses" ? selectedStatus.value : "",
                per_page: props.filters?.per_page || 20,
            },
            preserveState: true,
            replace: true,
        });
    }, 300);
};

// Watch for search and filter changes
watch(searchQuery, () => {
    performSearch();
});

watch(selectedStatus, () => {
    performSearch();
});
</script>

<template>
    <Head title="Assessments" />

    <AuthenticatedLayout>
        <div class="p-8">
            <!-- Header Section -->
            <div class="flex justify-between items-center mb-8 animate-fade-in">
                <h1 class="text-3xl font-bold text-gray-900">
                    Assessments Management
                </h1>
                <div class="flex gap-3">
                    <button
                        @click="exportData"
                        class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"
                            />
                        </svg>
                        Export
                    </button>
                    <button
                        @click="addAssessment"
                        class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                            />
                        </svg>
                        Create Assessment
                    </button>
                </div>
            </div>

            <!-- Filters and Search Section -->
            <div
                class="bg-white rounded-lg shadow-sm border p-6 mb-6 animate-fade-in"
            >
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">
                        All Assessments ({{
                            assessments?.meta?.total || assessmentsList.length
                        }})
                    </h2>
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search assessments..."
                            class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-80"
                        />
                        <svg
                            class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-medium text-gray-700"
                            >Filter by Status:</span
                        >
                        <select
                            v-model="selectedStatus"
                            class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="All Statuses">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                            <option value="graded">Graded</option>
                            <option value="competent">Competent</option>
                            <option value="not_yet_competent">
                                Not Yet Competent
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Assessments Table -->
            <div
                class="bg-white rounded-lg shadow-sm border overflow-hidden animate-fade-in"
            >
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Applicant
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Program
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Type
                                </th>

                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Result
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Payment
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Date
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="assessment in filteredAssessments"
                                :key="assessment.id"
                                class="hover:bg-gray-50"
                            >
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    <div>
                                        <div
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{ assessment.applicant_name }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{
                                                assessment.applicant_type ===
                                                "enrolled_trainee"
                                                    ? "Enrolled Applicant"
                                                    : "External Applicant"
                                            }}
                                        </div>
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    {{ assessment.program_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="{
                                            'bg-blue-100 text-blue-800':
                                                assessment.type ===
                                                'theoretical',
                                            'bg-green-100 text-green-800':
                                                assessment.type === 'practical',
                                            'bg-purple-100 text-purple-800':
                                                assessment.type === 'both',
                                        }"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{
                                            assessment.type
                                                .charAt(0)
                                                .toUpperCase() +
                                            assessment.type.slice(1)
                                        }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="{
                                            'bg-yellow-100 text-yellow-800':
                                                assessment.status === 'pending',
                                            'bg-blue-100 text-blue-800':
                                                assessment.status ===
                                                'completed',
                                        }"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{
                                            assessment.status
                                                .charAt(0)
                                                .toUpperCase() +
                                            assessment.status.slice(1)
                                        }}
                                    </span>
                                    <span
                                        v-if="isGraded(assessment)"
                                        class="ml-2 text-xs text-gray-500"
                                        title="Assessment is finalized"
                                    >
                                        🔒
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div
                                        v-if="assessment.result"
                                        class="flex flex-col gap-1"
                                    >
                                        <span
                                            :class="assessment.result_color"
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full w-fit"
                                        >
                                            {{ assessment.result_status }}
                                        </span>
                                        <span
                                            v-if="
                                                assessment.requires_reenrollment
                                            "
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800 w-fit"
                                            title="Maximum attempts reached - re-enrollment required"
                                        >
                                            Re-enrollment Required
                                        </span>
                                    </div>
                                    <div v-else class="text-gray-400 text-sm">
                                        Not evaluated
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div
                                            class="text-sm font-semibold text-gray-900"
                                        >
                                            ₱{{
                                                parseFloat(
                                                    assessment.assessment_fee ||
                                                        0
                                                ).toFixed(2)
                                            }}
                                        </div>
                                        <span
                                            :class="{
                                                'bg-yellow-100 text-yellow-800':
                                                    assessment.payment_status ===
                                                    'pending',
                                                'bg-green-100 text-green-800':
                                                    assessment.payment_status ===
                                                    'paid',
                                                'bg-red-100 text-red-800':
                                                    assessment.payment_status ===
                                                    'refunded',
                                            }"
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        >
                                            {{
                                                assessment.payment_status
                                                    ?.charAt(0)
                                                    .toUpperCase() +
                                                assessment.payment_status?.slice(
                                                    1
                                                )
                                            }}
                                        </span>
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    {{ assessment.assessment_date }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium"
                                >
                                    <div class="flex items-center gap-3">
                                        <button
                                            @click="viewAssessment(assessment)"
                                            class="text-green-600 hover:text-green-900 p-2 rounded"
                                            title="View"
                                        >
                                            <svg
                                                class="h-6 w-6 md:h-7 md:w-7"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                />
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                                />
                                            </svg>
                                        </button>
                                        <button
                                            v-if="
                                                assessment.status !==
                                                'completed'
                                            "
                                            @click="editAssessment(assessment)"
                                            :class="{
                                                'text-blue-600 hover:text-blue-900 cursor-pointer':
                                                    !isGraded(assessment),
                                                'text-gray-400 cursor-not-allowed':
                                                    isGraded(assessment),
                                            }"
                                            :disabled="isGraded(assessment)"
                                            :title="
                                                isGraded(assessment)
                                                    ? 'Cannot edit finalized assessment'
                                                    : 'Edit'
                                            "
                                            class="p-2 rounded"
                                        >
                                            <svg
                                                class="h-6 w-6 md:h-7 md:w-7"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                />
                                            </svg>
                                        </button>
                                        <button
                                            v-if="assessment.can_be_reassessed"
                                            @click="reassessment(assessment)"
                                            class="text-orange-600 hover:text-orange-900 p-2 rounded"
                                            title="Schedule Re-assessment"
                                        >
                                            <svg
                                                class="h-6 w-6 md:h-7 md:w-7"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                                />
                                            </svg>
                                        </button>
                                        <button
                                            @click="
                                                viewAssessmentHistory(
                                                    assessment
                                                )
                                            "
                                            class="text-purple-600 hover:text-purple-900 p-2 rounded"
                                            title="View Assessment History"
                                        >
                                            <svg
                                                class="h-6 w-6 md:h-7 md:w-7"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                        </button>
                                        <button
                                            v-if="
                                                assessment.status !==
                                                'completed'
                                            "
                                            @click="
                                                deleteAssessment(assessment)
                                            "
                                            :class="{
                                                'text-red-600 hover:text-red-900 cursor-pointer':
                                                    isDeletable(assessment),
                                                'text-gray-400 cursor-not-allowed':
                                                    !isDeletable(assessment),
                                            }"
                                            :disabled="!isDeletable(assessment)"
                                            :title="
                                                !isDeletable(assessment)
                                                    ? isGraded(assessment)
                                                        ? 'Cannot delete finalized assessment'
                                                        : isPaid(assessment)
                                                        ? 'Cannot delete paid assessment'
                                                        : 'Cannot delete this assessment'
                                                    : 'Delete'
                                            "
                                            class="p-2 rounded"
                                        >
                                            <svg
                                                class="h-6 w-6 md:h-7 md:w-7"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div
                    v-if="filteredAssessments.length === 0"
                    class="text-center py-12"
                >
                    <svg
                        class="mx-auto h-12 w-12 text-gray-400"
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
                    <h3 class="mt-2 text-sm font-medium text-gray-900">
                        No assessments found
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{
                            searchQuery
                                ? "Try adjusting your search terms."
                                : "Get started by creating a new assessment."
                        }}
                    </p>
                    <div v-if="!searchQuery" class="mt-6">
                        <button
                            @click="addAssessment"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                        >
                            <svg
                                class="-ml-1 mr-2 h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                />
                            </svg>
                            Create Assessment
                        </button>
                    </div>
                </div>

                <!-- Pagination -->
                <Pagination
                    v-if="assessmentsList.length > 0"
                    :data="assessments"
                />
            </div>
        </div>

        <!-- Assessment Registration Modal -->
        <AssessmentRegistrationModal
            :show="showRegistrationModal"
            :programs="programs"
            :trainees="trainees"
            :trainers="trainers"
            @close="closeRegistrationModal"
            @submitted="onAssessmentSubmitted"
        />

        <!-- Assessment Details Modal -->
        <AssessmentDetailsModal
            :show="showDetailsModal"
            :assessment="selectedAssessment"
            @close="closeDetailsModal"
            @edit="handleEditFromDetails"
            @reassessment="handleReassessmentFromDetails"
        />

        <!-- Assessment Re-assessment Modal -->
        <AssessmentReassessmentModal
            :show="showReassessmentModal"
            :assessment="selectedAssessment"
            :programs="programs"
            :trainers="trainers"
            @close="closeReassessmentModal"
            @submitted="onReassessmentSubmitted"
        />

        <!-- Delete Confirmation Modal -->
        <DeleteConfirmationModal
            :show="showDeleteModal"
            :item="selectedAssessment"
            itemType="assessment"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
