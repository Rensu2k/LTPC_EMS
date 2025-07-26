<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button
                        @click="$inertia.visit(route(backRoute))"
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
                            Enrollment History: {{ trainee.first_name }}
                            {{ trainee.last_name }}
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            Complete enrollment history across all programs
                        </p>
                    </div>
                </div>
                <button
                    v-if="isOfficer"
                    @click="showEnrollModal = true"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
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
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                        />
                    </svg>
                    Enroll in New Program
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Trainee Summary Card -->
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
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h3
                                        class="text-lg font-semibold text-gray-900"
                                    >
                                        {{ trainee.first_name }}
                                        {{ trainee.middle_name }}
                                        {{ trainee.last_name }}
                                        {{ trainee.extension }}
                                    </h3>
                                    <p class="text-sm text-gray-600">
                                        ULI: {{ trainee.uli_number || "N/A" }}
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        Email:
                                        {{ trainee.email_facebook || "N/A" }}
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        Contact:
                                        {{ trainee.contact_number || "N/A" }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="grid grid-cols-3 gap-4 text-center">
                                    <div>
                                        <div
                                            class="text-2xl font-bold text-blue-600"
                                        >
                                            {{ enrollments.length }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Total Enrollments
                                        </div>
                                    </div>
                                    <div>
                                        <div
                                            class="text-2xl font-bold text-green-600"
                                        >
                                            {{ completedCount }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Completed
                                        </div>
                                    </div>
                                    <div>
                                        <div
                                            class="text-2xl font-bold text-orange-600"
                                        >
                                            {{ activeCount }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Active
                                        </div>
                                    </div>
                                </div>
                                <div
                                    v-if="trainee.scholarship_package"
                                    class="mt-3"
                                >
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                                    >
                                        ⭐
                                        {{ trainee.scholarship_package }}
                                        Scholar
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enrollment Timeline -->
                <div class="bg-white overflow-hidden shadow-lg rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Enrollment Timeline
                        </h3>

                        <div
                            v-if="enrollments.length === 0"
                            class="text-center py-8"
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
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">
                                No enrollments found
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                This trainee hasn't been enrolled in any
                                programs yet.
                            </p>
                            <div v-if="isOfficer" class="mt-6">
                                <button
                                    @click="showEnrollModal = true"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg"
                                >
                                    Enroll in First Program
                                </button>
                            </div>
                        </div>

                        <div v-else class="space-y-6">
                            <div
                                v-for="(enrollment, index) in enrollments"
                                :key="enrollment.id"
                                class="relative"
                            >
                                <!-- Timeline line -->
                                <div
                                    v-if="index < enrollments.length - 1"
                                    class="absolute left-4 top-16 bottom-0 w-0.5 bg-gray-200"
                                ></div>

                                <div class="flex items-start gap-4">
                                    <!-- Timeline dot -->
                                    <div
                                        class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center"
                                        :class="{
                                            'bg-green-100 border-2 border-green-500':
                                                enrollment.status ===
                                                'completed',
                                            'bg-blue-100 border-2 border-blue-500':
                                                enrollment.status === 'active',
                                            'bg-red-100 border-2 border-red-500':
                                                enrollment.status === 'dropped',
                                            'bg-yellow-100 border-2 border-yellow-500':
                                                enrollment.status === 'pending',
                                        }"
                                    >
                                        <svg
                                            class="w-4 h-4"
                                            :class="{
                                                'text-green-600':
                                                    enrollment.status ===
                                                    'completed',
                                                'text-blue-600':
                                                    enrollment.status ===
                                                    'active',
                                                'text-red-600':
                                                    enrollment.status ===
                                                    'dropped',
                                                'text-yellow-600':
                                                    enrollment.status ===
                                                    'pending',
                                            }"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                v-if="
                                                    enrollment.status ===
                                                    'completed'
                                                "
                                                fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd"
                                            />
                                            <path
                                                v-else-if="
                                                    enrollment.status ===
                                                    'active'
                                                "
                                                fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd"
                                            />
                                            <path
                                                v-else-if="
                                                    enrollment.status ===
                                                    'dropped'
                                                "
                                                fill-rule="evenodd"
                                                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                clip-rule="evenodd"
                                            />
                                            <path
                                                v-else
                                                fill-rule="evenodd"
                                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </div>

                                    <!-- Enrollment card -->
                                    <div
                                        class="flex-1 bg-gray-50 rounded-lg p-4 border border-gray-200"
                                    >
                                        <div
                                            class="flex items-start justify-between"
                                        >
                                            <div class="flex-1">
                                                <div
                                                    class="flex items-center gap-3 mb-2"
                                                >
                                                    <h4
                                                        class="text-lg font-semibold text-gray-900"
                                                    >
                                                        {{
                                                            enrollment.program
                                                                .name
                                                        }}
                                                    </h4>
                                                    <span
                                                        class="px-2 py-1 rounded-full text-xs font-medium"
                                                        :class="{
                                                            'bg-green-100 text-green-800':
                                                                enrollment.status ===
                                                                'completed',
                                                            'bg-blue-100 text-blue-800':
                                                                enrollment.status ===
                                                                'active',
                                                            'bg-red-100 text-red-800':
                                                                enrollment.status ===
                                                                'dropped',
                                                            'bg-yellow-100 text-yellow-800':
                                                                enrollment.status ===
                                                                'pending',
                                                        }"
                                                    >
                                                        {{
                                                            enrollment.status
                                                                .charAt(0)
                                                                .toUpperCase() +
                                                            enrollment.status.slice(
                                                                1
                                                            )
                                                        }}
                                                    </span>
                                                </div>

                                                <div
                                                    class="grid grid-cols-2 md:grid-cols-6 gap-4 text-sm"
                                                >
                                                    <div>
                                                        <span
                                                            class="text-gray-500"
                                                            >Batch:</span
                                                        >
                                                        <div
                                                            class="font-medium"
                                                        >
                                                            {{
                                                                enrollment.batch
                                                            }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <span
                                                            class="text-gray-500"
                                                            >Enrolled:</span
                                                        >
                                                        <div
                                                            class="font-medium"
                                                        >
                                                            {{
                                                                formatDate(
                                                                    enrollment.enrollment_date
                                                                )
                                                            }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <span
                                                            class="text-gray-500"
                                                            >Start Date:</span
                                                        >
                                                        <div
                                                            class="font-medium"
                                                        >
                                                            {{
                                                                formatDate(
                                                                    enrollment.date_started
                                                                )
                                                            }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <span
                                                            class="text-gray-500"
                                                            >End Date:</span
                                                        >
                                                        <div
                                                            class="font-medium"
                                                            :class="{
                                                                'text-red-600':
                                                                    enrollment.status ===
                                                                    'dropped',
                                                            }"
                                                        >
                                                            {{
                                                                formatDateEnded(
                                                                    enrollment
                                                                )
                                                            }}
                                                        </div>
                                                    </div>
                                                    <div
                                                        v-if="
                                                            enrollment.completion_date
                                                        "
                                                    >
                                                        <span
                                                            class="text-gray-500"
                                                            >Completed:</span
                                                        >
                                                        <div
                                                            class="font-medium"
                                                        >
                                                            {{
                                                                formatDate(
                                                                    enrollment.completion_date
                                                                )
                                                            }}
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <span
                                                            class="text-gray-500"
                                                            >Fee:</span
                                                        >
                                                        <div
                                                            class="font-medium"
                                                        >
                                                            ₱{{
                                                                parseFloat(
                                                                    enrollment.enrollment_fee
                                                                ).toFixed(2)
                                                            }}
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Payment info -->
                                                <div
                                                    class="mt-3 flex items-center gap-4"
                                                >
                                                    <span
                                                        class="px-2 py-1 rounded-full text-xs font-medium"
                                                        :class="{
                                                            'bg-green-100 text-green-800':
                                                                enrollment.payment_status ===
                                                                'paid',
                                                            'bg-red-100 text-red-800':
                                                                enrollment.payment_status ===
                                                                'unpaid',
                                                            'bg-yellow-100 text-yellow-800':
                                                                enrollment.payment_status ===
                                                                'partial',
                                                        }"
                                                    >
                                                        Payment:
                                                        {{
                                                            enrollment.payment_status
                                                                .charAt(0)
                                                                .toUpperCase() +
                                                            enrollment.payment_status.slice(
                                                                1
                                                            )
                                                        }}
                                                    </span>
                                                    <span
                                                        v-if="
                                                            enrollment.payment_method
                                                        "
                                                        class="text-xs text-gray-600"
                                                    >
                                                        via
                                                        {{
                                                            enrollment.payment_method
                                                                .replace(
                                                                    "_",
                                                                    " "
                                                                )
                                                                .replace(
                                                                    /\b\w/g,
                                                                    (l) =>
                                                                        l.toUpperCase()
                                                                )
                                                        }}
                                                    </span>
                                                </div>

                                                <!-- Notes -->
                                                <div
                                                    v-if="enrollment.notes"
                                                    class="mt-3"
                                                >
                                                    <span
                                                        class="text-gray-500 text-xs"
                                                        >Notes:</span
                                                    >
                                                    <div
                                                        class="text-sm text-gray-700 mt-1"
                                                    >
                                                        {{ enrollment.notes }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enrollment Modal -->
        <TraineeEnrollmentModal
            :show="showEnrollModal"
            :trainee="trainee"
            :available-programs="availablePrograms"
            :enrollment-history="enrollments"
            @close="showEnrollModal = false"
            @submitted="handleEnrollmentSubmitted"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import TraineeEnrollmentModal from "@/Components/TraineeEnrollmentModal.vue";

const props = defineProps({
    trainee: Object,
    enrollments: Array,
    availablePrograms: {
        type: Array,
        default: () => [],
    },
});

const showEnrollModal = ref(false);

// Role detection
const user = computed(() => usePage().props.auth.user);
const isOfficer = computed(() => user.value?.role === "officer");

// Dynamic back route based on user role
const backRoute = computed(() => {
    return isOfficer.value ? "officer.trainees" : "admin.trainees";
});

// Computed properties for stats
const completedCount = computed(
    () => props.enrollments.filter((e) => e.status === "completed").length
);

const activeCount = computed(
    () => props.enrollments.filter((e) => e.status === "active").length
);

// Format date helper
const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    return new Date(dateString).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

// Format date ended with special logic
const formatDateEnded = (enrollment) => {
    // If trainee dropped, show "Dropped"
    if (enrollment.status === "dropped") {
        return "Dropped";
    }

    // If there's an actual end date, format it
    if (enrollment.date_ended) {
        return formatDate(enrollment.date_ended);
    }

    // If training hasn't ended yet, show "-"
    return "-";
};

// Handle enrollment submission
const handleEnrollmentSubmitted = () => {
    showEnrollModal.value = false;
    // Refresh the page to show updated data
    router.reload();
};
</script>
