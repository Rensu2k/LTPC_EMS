<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    statistics: Object,
    recent_enrollments: Array,
});

// Computed properties for displaying the statistics
const totalEnrollments = computed(
    () => props.statistics?.total_enrollments || { value: 0, change: 0 }
);
const activeCourses = computed(
    () => props.statistics?.active_courses || { value: 0, change: 0 }
);
const completedTrainings = computed(
    () => props.statistics?.completed_trainings || { value: 0, change: 0 }
);

// Helper function to format percentage change
const formatChange = (change) => {
    const sign = change >= 0 ? "↑" : "↓";
    const color = change >= 0 ? "text-green-600" : "text-red-600";
    return { sign, color, value: Math.abs(change) };
};

// Helper function to get status badge class
const getStatusBadgeClass = (status) => {
    const statusLower = status.toLowerCase();
    switch (statusLower) {
        case "active":
            return "bg-green-50 text-green-700";
        case "completed":
            return "bg-blue-50 text-blue-700";
        case "dropped":
        case "suspended":
            return "bg-gray-50 text-gray-700";
        default:
            return "bg-gray-50 text-gray-700";
    }
};

// Helper function to get payment badge class
const getPaymentBadgeClass = (payment) => {
    const paymentLower = payment.toLowerCase();
    switch (paymentLower) {
        case "paid":
            return "bg-green-50 text-green-700";
        case "partial":
            return "bg-yellow-50 text-yellow-700";
        case "unpaid":
            return "bg-red-50 text-red-700";
        default:
            return "bg-gray-50 text-gray-700";
    }
};
</script>

<template>
    <Head title="Enrollment Officer Dashboard" />
    <AuthenticatedLayout>
        <div class="py-8 px-8 bg-gray-50 min-h-screen">
            <h1 class="text-3xl font-extrabold text-gray-900">
                Enrollment Officer Dashboard
            </h1>
            <p class="text-base text-gray-500 mt-1 mb-6">
                Good evening, Enrollment Officer. Welcome to your enrollment
                officer dashboard.
            </p>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Total Enrollments -->
                <div
                    class="bg-white rounded-xl border border-gray-200 p-6 flex items-center gap-4"
                >
                    <div class="flex-1">
                        <div class="text-sm text-gray-500 font-medium">
                            Total Enrollments
                        </div>
                        <div class="text-3xl font-bold text-gray-900">
                            {{ totalEnrollments.value }}
                        </div>
                        <div
                            class="flex items-center gap-1 mt-1"
                            v-if="totalEnrollments.change !== 0"
                        >
                            <span
                                :class="`${
                                    formatChange(totalEnrollments.change).color
                                } text-xs font-semibold`"
                            >
                                {{
                                    formatChange(totalEnrollments.change).sign
                                }}
                                {{
                                    formatChange(totalEnrollments.change).value
                                }}%
                            </span>
                            <span class="text-xs text-gray-400"
                                >vs. last month</span
                            >
                        </div>
                    </div>
                    <div class="bg-blue-50 rounded-full p-3">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6 text-blue-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0z"
                            />
                        </svg>
                    </div>
                </div>

                <!-- Active Courses -->
                <div
                    class="bg-white rounded-xl border border-gray-200 p-6 flex items-center gap-4"
                >
                    <div class="flex-1">
                        <div class="text-sm text-gray-500 font-medium">
                            Active Courses
                        </div>
                        <div class="text-3xl font-bold text-gray-900">
                            {{ activeCourses.value }}
                        </div>
                        <div
                            class="flex items-center gap-1 mt-1"
                            v-if="activeCourses.change !== 0"
                        >
                            <span
                                :class="`${
                                    formatChange(activeCourses.change).color
                                } text-xs font-semibold`"
                            >
                                {{ formatChange(activeCourses.change).sign }}
                                {{ formatChange(activeCourses.change).value }}%
                            </span>
                            <span class="text-xs text-gray-400"
                                >vs. last month</span
                            >
                        </div>
                    </div>
                    <div class="bg-green-50 rounded-full p-3">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6 text-green-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <rect
                                width="18"
                                height="12"
                                x="3"
                                y="4"
                                rx="2"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 20h9"
                            />
                        </svg>
                    </div>
                </div>

                <!-- Completed Trainings -->
                <div
                    class="bg-white rounded-xl border border-gray-200 p-6 flex items-center gap-4"
                >
                    <div class="flex-1">
                        <div class="text-sm text-gray-500 font-medium">
                            Completed Trainings
                        </div>
                        <div class="text-3xl font-bold text-gray-900">
                            {{ completedTrainings.value }}
                        </div>
                        <div
                            class="flex items-center gap-1 mt-1"
                            v-if="completedTrainings.change !== 0"
                        >
                            <span
                                :class="`${
                                    formatChange(completedTrainings.change)
                                        .color
                                } text-xs font-semibold`"
                            >
                                {{
                                    formatChange(completedTrainings.change).sign
                                }}
                                {{
                                    formatChange(completedTrainings.change)
                                        .value
                                }}%
                            </span>
                            <span class="text-xs text-gray-400"
                                >vs. last month</span
                            >
                        </div>
                    </div>
                    <div class="bg-yellow-50 rounded-full p-3">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6 text-yellow-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 14l9-5-9-5-9 5 9 5zm0 0v6m0 0H6m6 0h6"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Recent Enrollments -->
            <div class="mb-4 text-xl font-semibold text-gray-900">
                Recent Enrollments
            </div>
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex justify-between items-center mb-4">
                    <div class="font-semibold text-lg">Recent Enrollments</div>
                    <a
                        href="#"
                        class="text-blue-600 hover:underline text-sm font-medium"
                        >View All</a
                    >
                </div>

                <div
                    v-if="recent_enrollments && recent_enrollments.length > 0"
                    class="overflow-x-auto"
                >
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                                >
                                    ID
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                                >
                                    Trainee Name
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                                >
                                    Course
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                                >
                                    Trainer
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                                >
                                    Payment
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="enrollment in recent_enrollments"
                                :key="enrollment.id"
                            >
                                <td
                                    class="px-6 py-4 font-semibold text-gray-700"
                                >
                                    {{ enrollment.id }}
                                </td>
                                <td class="px-6 py-4 flex items-center gap-3">
                                    <span
                                        class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-gray-100 text-xs font-bold text-gray-400 mr-2 border border-gray-200"
                                    >
                                        {{ enrollment.avatar }}
                                    </span>
                                    <span class="font-medium text-gray-900">{{
                                        enrollment.name
                                    }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    {{ enrollment.course }}
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    {{ enrollment.trainer }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="`${getStatusBadgeClass(
                                            enrollment.status
                                        )} px-3 py-1 rounded-full text-xs font-semibold`"
                                    >
                                        {{ enrollment.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="`${getPaymentBadgeClass(
                                            enrollment.payment
                                        )} px-3 py-1 rounded-full text-xs font-semibold`"
                                    >
                                        {{ enrollment.payment }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 flex gap-2">
                                    <button
                                        class="text-gray-500 hover:text-blue-600"
                                        title="View"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        class="text-gray-500 hover:text-blue-600"
                                        title="Edit"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M11 5h2m-1 0v14m-7-7h14"
                                            />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-12">
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
                            d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0z"
                        />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">
                        No enrollments yet
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Get started by adding your first trainee.
                    </p>
                </div>

                <!-- Pagination Info -->
                <div
                    v-if="recent_enrollments && recent_enrollments.length > 0"
                    class="flex justify-between items-center mt-4"
                >
                    <div class="text-sm text-gray-500">
                        Showing {{ recent_enrollments.length }} recent
                        enrollments
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            class="px-3 py-1 rounded border border-gray-300 text-gray-500 bg-white font-semibold"
                            disabled
                        >
                            Previous
                        </button>
                        <span
                            class="px-3 py-1 rounded bg-blue-600 text-white font-semibold"
                            >1</span
                        >
                        <button
                            class="px-3 py-1 rounded border border-gray-300 text-gray-500 bg-white font-semibold"
                            disabled
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
