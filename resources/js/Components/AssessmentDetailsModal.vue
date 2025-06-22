<script setup>
import { computed } from "vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    assessment: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(["close", "edit"]);

const formattedDate = computed(() => {
    if (!props.assessment?.assessment_date) return "N/A";
    return new Date(props.assessment.assessment_date).toLocaleDateString(
        "en-US",
        {
            year: "numeric",
            month: "long",
            day: "numeric",
        }
    );
});

const percentage = computed(() => {
    if (!props.assessment?.score || !props.assessment?.max_score) return null;
    return Math.round(
        (props.assessment.score / props.assessment.max_score) * 100
    );
});

const grade = computed(() => {
    const percent = percentage.value;
    if (percent === null) return "N/A";

    if (percent >= 90) return "A";
    if (percent >= 80) return "B";
    if (percent >= 70) return "C";
    if (percent >= 60) return "D";
    return "F";
});

const statusColor = computed(() => {
    switch (props.assessment?.status) {
        case "pending":
            return "bg-yellow-100 text-yellow-800";
        case "completed":
            return "bg-blue-100 text-blue-800";
        case "graded":
            return "bg-green-100 text-green-800";
        default:
            return "bg-gray-100 text-gray-800";
    }
});

const typeColor = computed(() => {
    switch (props.assessment?.type) {
        case "theoretical":
            return "bg-blue-100 text-blue-800";
        case "practical":
            return "bg-green-100 text-green-800";
        case "both":
            return "bg-purple-100 text-purple-800";
        default:
            return "bg-gray-100 text-gray-800";
    }
});

const close = () => {
    emit("close");
};

const editAssessment = () => {
    emit("edit", props.assessment);
};
</script>

<template>
    <Modal :show="show" @close="close" custom-width="80vw">
        <div class="p-6" v-if="assessment">
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
                            {{ assessment.title }}
                        </h2>
                        <p
                            class="text-gray-600 mt-1"
                            v-if="assessment.description"
                        >
                            {{ assessment.description }}
                        </p>
                        <div class="flex items-center gap-3 mt-3">
                            <span
                                :class="typeColor"
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            >
                                {{
                                    assessment.type?.charAt(0).toUpperCase() +
                                    assessment.type?.slice(1)
                                }}
                            </span>
                            <span
                                :class="statusColor"
                                class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                            >
                                {{
                                    assessment.status?.charAt(0).toUpperCase() +
                                    assessment.status?.slice(1)
                                }}
                            </span>
                        </div>
                    </div>
                </div>
                <button
                    @click="close"
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
                            <span class="text-sm text-gray-900 font-semibold">
                                {{ assessment.program_name || "N/A" }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-500"
                                >Assessment Date:</span
                            >
                            <span class="text-sm text-gray-900">{{
                                formattedDate
                            }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-500"
                                >Type:</span
                            >
                            <span class="text-sm text-gray-900">
                                {{
                                    assessment.type?.charAt(0).toUpperCase() +
                                    assessment.type?.slice(1)
                                }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-500"
                                >Maximum Score:</span
                            >
                            <span class="text-sm text-gray-900 font-semibold"
                                >{{ assessment.max_score }} points</span
                            >
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
                            <span class="text-sm text-gray-900 font-semibold">
                                {{ assessment.applicant_name || "N/A" }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-500"
                                >Trainer/Assessor:</span
                            >
                            <span class="text-sm text-gray-900">
                                {{ assessment.trainer_name || "N/A" }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Score Information -->
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
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                />
                            </svg>
                            Assessment Score
                        </h3>

                        <div
                            v-if="
                                assessment.score !== null &&
                                assessment.score !== undefined
                            "
                            class="grid grid-cols-1 md:grid-cols-3 gap-6"
                        >
                            <div class="text-center">
                                <div class="text-3xl font-bold text-blue-900">
                                    {{ assessment.score }}/{{
                                        assessment.max_score
                                    }}
                                </div>
                                <div class="text-sm text-blue-700 mt-1">
                                    Raw Score
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-blue-900">
                                    {{ percentage }}%
                                </div>
                                <div class="text-sm text-blue-700 mt-1">
                                    Percentage
                                </div>
                            </div>
                            <div class="text-center">
                                <div
                                    class="text-3xl font-bold"
                                    :class="{
                                        'text-green-600':
                                            grade === 'A' || grade === 'B',
                                        'text-yellow-600': grade === 'C',
                                        'text-red-600':
                                            grade === 'D' || grade === 'F',
                                    }"
                                >
                                    {{ grade }}
                                </div>
                                <div class="text-sm text-blue-700 mt-1">
                                    Grade
                                </div>
                            </div>
                        </div>

                        <div v-else class="text-center py-8">
                            <svg
                                class="mx-auto h-12 w-12 text-blue-400"
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
                            <h4 class="mt-2 text-lg font-medium text-blue-900">
                                Not Graded Yet
                            </h4>
                            <p class="mt-1 text-sm text-blue-700">
                                This assessment is still
                                {{ assessment.status }} and hasn't been graded.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t mt-6">
                <SecondaryButton @click="close"> Close </SecondaryButton>
                <PrimaryButton
                    @click="editAssessment"
                    class="bg-blue-600 hover:bg-blue-700"
                >
                    <svg
                        class="-ml-1 mr-2 h-4 w-4"
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
                    Edit Assessment
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
