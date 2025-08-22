<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button
                        @click="$inertia.visit(route('officer.assessments'))"
                        class="p-2 rounded-lg hover:bg-gray-100 transition-colors"
                    >
                        <svg
                            class="w-5 h-5 text-gray-600"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 19l-7-7 7-7"
                            />
                        </svg>
                    </button>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">
                            Assessment History:
                            {{ originalAssessment.title }}
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            Complete assessment history for
                            {{ originalAssessment.applicant_name }}
                        </p>
                    </div>
                </div>
                <div
                    v-if="latestAssessment.requires_reenrollment"
                    class="bg-orange-100 border border-orange-300 text-orange-800 px-4 py-2 rounded-lg flex items-center gap-2"
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
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"
                        />
                    </svg>
                    <span class="font-medium">Re-enrollment Required</span>
                    <span class="text-sm">Maximum of 3 attempts reached</span>
                </div>
                <button
                    v-else-if="latestAssessment.can_be_reassessed"
                    @click="showReassessmentModal = true"
                    class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
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
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        />
                    </svg>
                    Schedule New Re-assessment
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Original Assessment Summary Card -->
                <div class="bg-white overflow-hidden shadow-lg rounded-lg mb-6">
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center"
                                >
                                    <svg
                                        class="w-8 h-8 text-blue-600"
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
                                    <h3 class="text-xl font-bold text-gray-900">
                                        {{ originalAssessment.title }}
                                    </h3>
                                    <p class="text-gray-600">
                                        {{ originalAssessment.applicant_name }}
                                        - {{ originalAssessment.program_name }}
                                    </p>
                                    <div class="flex items-center gap-4 mt-2">
                                        <span
                                            class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                            :class="
                                                originalAssessment.result_color
                                            "
                                        >
                                            {{
                                                originalAssessment.result_status ||
                                                "Pending"
                                            }}
                                        </span>
                                        <span class="text-sm text-gray-500">
                                            Attempt #{{
                                                originalAssessment.attempt_number
                                            }}
                                        </span>
                                        <span class="text-sm text-gray-500">
                                            {{
                                                formatDate(
                                                    originalAssessment.assessment_date
                                                )
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assessment History -->
                <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Assessment History
                            </h3>
                            <span class="text-sm text-gray-500">
                                {{ reassessments.length }} re-assessment{{
                                    reassessments.length !== 1 ? "s" : ""
                                }}
                            </span>
                        </div>

                        <div
                            v-if="reassessments.length === 0"
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
                            <h3 class="text-lg font-medium text-gray-900 mb-2">
                                No Re-assessments Yet
                            </h3>
                            <p class="text-gray-500 mb-4">
                                This assessment has not been re-assessed yet.
                            </p>
                            <button
                                v-if="originalAssessment.can_be_reassessed"
                                @click="showReassessmentModal = true"
                                class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg inline-flex items-center gap-2 transition-colors"
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
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                    />
                                </svg>
                                Schedule First Re-assessment
                            </button>
                        </div>

                        <div v-else class="space-y-6">
                            <div
                                v-for="reassessment in reassessments"
                                :key="reassessment.id"
                                class="bg-gray-50 rounded-lg p-6 border-l-4"
                                :class="{
                                    'border-green-500':
                                        reassessment.result === 'competent',
                                    'border-red-500':
                                        reassessment.result ===
                                        'not_yet_competent',
                                    'border-orange-500':
                                        reassessment.result === 'absent',
                                    'border-yellow-500': !reassessment.result,
                                }"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div
                                            class="flex items-center gap-3 mb-3"
                                        >
                                            <h4
                                                class="text-lg font-semibold text-gray-900"
                                            >
                                                Attempt #{{
                                                    reassessment.attempt_number
                                                }}
                                            </h4>
                                            <span
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                                :class="
                                                    reassessment.result_color
                                                "
                                            >
                                                {{
                                                    reassessment.result_status ||
                                                    "Pending"
                                                }}
                                            </span>
                                            <span
                                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                                :class="{
                                                    'bg-green-100 text-green-800':
                                                        reassessment.payment_status ===
                                                        'paid',
                                                    'bg-yellow-100 text-yellow-800':
                                                        reassessment.payment_status ===
                                                        'pending',
                                                    'bg-red-100 text-red-800':
                                                        reassessment.payment_status ===
                                                        'refunded',
                                                }"
                                            >
                                                Payment
                                                {{
                                                    reassessment.payment_status
                                                        .charAt(0)
                                                        .toUpperCase() +
                                                    reassessment.payment_status.slice(
                                                        1
                                                    )
                                                }}
                                            </span>
                                        </div>

                                        <div
                                            class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm"
                                        >
                                            <div>
                                                <span
                                                    class="font-medium text-gray-500"
                                                    >Assessment Date:</span
                                                >
                                                <div class="text-gray-900">
                                                    {{
                                                        formatDate(
                                                            reassessment.assessment_date
                                                        )
                                                    }}
                                                </div>
                                            </div>
                                            <div>
                                                <span
                                                    class="font-medium text-gray-500"
                                                    >Trainer:</span
                                                >
                                                <div class="text-gray-900">
                                                    {{
                                                        reassessment.trainer_name
                                                    }}
                                                </div>
                                            </div>
                                            <div>
                                                <span
                                                    class="font-medium text-gray-500"
                                                    >Assessment Fee:</span
                                                >
                                                <div class="text-gray-900">
                                                    ₱{{
                                                        (
                                                            parseFloat(
                                                                reassessment.assessment_fee
                                                            ) || 0
                                                        ).toFixed(2)
                                                    }}
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            v-if="reassessment.description"
                                            class="mt-3"
                                        >
                                            <span
                                                class="font-medium text-gray-500"
                                                >Description:</span
                                            >
                                            <p class="text-gray-900">
                                                {{ reassessment.description }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex gap-2 ml-4">
                                        <button
                                            v-if="
                                                reassessment.status !==
                                                'completed'
                                            "
                                            @click="
                                                editReassessment(reassessment)
                                            "
                                            class="text-blue-600 hover:text-blue-900 p-2 rounded"
                                            title="Edit Re-assessment"
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
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Re-assessment Modal -->
        <AssessmentReassessmentModal
            :show="showReassessmentModal"
            :assessment="latestAssessment"
            :programs="programs"
            :trainers="trainers"
            @close="closeReassessmentModal"
            @submitted="onReassessmentSubmitted"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import AssessmentReassessmentModal from "@/Components/AssessmentReassessmentModal.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    originalAssessment: Object,
    latestAssessment: Object,
    reassessments: Array,
    trainers: Array,
    programs: Array,
});

const showReassessmentModal = ref(false);

const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    return new Date(dateString).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
};

const editReassessment = (reassessment) => {
    router.visit(`/officer/assessments/${reassessment.id}/edit`);
};

const closeReassessmentModal = () => {
    showReassessmentModal.value = false;
};

const onReassessmentSubmitted = () => {
    showReassessmentModal.value = false;
    // Refresh the page to show the new re-assessment
    window.location.reload();
};
</script>
