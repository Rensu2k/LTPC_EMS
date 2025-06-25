<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { computed, onMounted, ref } from "vue";

const props = defineProps({
    statistics: Object,
    recent_enrollments: Array,
    recent_assessments: Array,
    assessment_summary: Object,
});

// Current time for dynamic greeting
const currentTime = ref(new Date());
const currentGreeting = computed(() => {
    const hour = currentTime.value.getHours();
    if (hour < 12) return "Good morning";
    if (hour < 17) return "Good afternoon";
    return "Good evening";
});

const currentDate = computed(() => {
    return currentTime.value.toLocaleDateString("en-US", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
    });
});

// Update time every minute
onMounted(() => {
    const timer = setInterval(() => {
        currentTime.value = new Date();
    }, 60000);
});

// Computed properties for displaying the statistics
const totalEnrollments = computed(
    () => props.statistics?.total_enrollments || { value: 0, change: 0 }
);
const activePrograms = computed(
    () => props.statistics?.active_programs || { value: 0, change: 0 }
);
const completedTrainings = computed(
    () => props.statistics?.completed_trainings || { value: 0, change: 0 }
);
const totalAssessments = computed(
    () => props.statistics?.total_assessments || { value: 0, change: 0 }
);

// Priority alerts
const priorityAlerts = computed(() => {
    const alerts = [];
    if (props.assessment_summary?.pending > 0) {
        alerts.push({
            type: "warning",
            title: "Pending Assessments",
            message: `${props.assessment_summary.pending} assessments awaiting completion or grading`,
            action: "View Assessments",
            route: "officer.assessments",
        });
    }
    return alerts;
});

// Helper function to format percentage change
const formatChange = (change) => {
    const sign = change >= 0 ? "↑" : "↓";
    const color = change >= 0 ? "text-green-600" : "text-red-600";
    const bgColor = change >= 0 ? "bg-green-50" : "bg-red-50";
    return { sign, color, bgColor, value: Math.abs(change) };
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
        case "pending":
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

// Helper function to get assessment status badge class
const getAssessmentStatusBadgeClass = (status) => {
    const statusLower = status.toLowerCase();
    switch (statusLower) {
        case "pending":
            return "bg-yellow-100 text-yellow-800";
        case "completed":
            return "bg-blue-100 text-blue-800";
        case "graded":
            return "bg-indigo-100 text-indigo-800";
        case "pass":
            return "bg-green-100 text-green-800";
        case "fail":
            return "bg-red-100 text-red-800";
        default:
            return "bg-gray-100 text-gray-800";
    }
};

// Navigation functions
const viewTrainee = (enrollment) => {
    router.visit(route("officer.trainees"));
};

const viewEnrollmentHistory = (enrollment) => {
    router.visit(route("officer.trainees"));
};

const viewAssessments = () => {
    router.visit(route("officer.assessments"));
};

// Quick action functions
const addNewTrainee = () => {
    router.visit(route("officer.trainees"));
};

const createAssessment = () => {
    router.visit(route("officer.assessments"));
};

const viewPrograms = () => {
    router.visit(route("officer.programs"));
};

const viewTrainers = () => {
    router.visit(route("officer.trainers"));
};

// Navigate to statistics detail
const navigateToStats = (section) => {
    switch (section) {
        case "enrollments":
            router.visit(route("officer.trainees"));
            break;
        case "programs":
            router.visit(route("officer.programs"));
            break;
        case "trainings":
            router.visit(route("officer.trainees"));
            break;
        case "assessments":
            router.visit(route("officer.assessments"));
            break;
    }
};
</script>

<template>
    <Head title="Enrollment Officer Dashboard" />
    <AuthenticatedLayout>
        <div class="py-8 px-8 bg-gray-50 min-h-screen">
            <!-- Enhanced Header Section -->
            <div class="mb-8 animate-fade-in">
                <div
                    class="flex flex-col lg:flex-row lg:items-center lg:justify-between"
                >
                    <div>
                        <h1 class="text-3xl font-extrabold text-gray-900">
                            Enrollment Officer Dashboard
                        </h1>
                        <p class="text-base text-gray-500 mt-1">
                            {{ currentGreeting }}, Enrollment Officer. Welcome
                            to your dashboard.
                        </p>
                        <p class="text-sm text-gray-400 mt-1">
                            {{ currentDate }}
                        </p>
                    </div>
                    <div class="mt-4 lg:mt-0">
                        <div class="flex flex-wrap gap-3">
                            <button
                                @click="addNewTrainee"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 shadow-sm"
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
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                    />
                                </svg>
                                Add Trainee
                            </button>
                            <button
                                @click="createAssessment"
                                class="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 shadow-sm"
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
                                        d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                    />
                                </svg>
                                Create Assessment
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Priority Alerts -->
            <div v-if="priorityAlerts.length > 0" class="mb-6 animate-fade-in">
                <div
                    v-for="alert in priorityAlerts"
                    :key="alert.title"
                    class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg
                                class="w-5 h-5 text-yellow-400 mr-3"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <div>
                                <h3 class="text-sm font-medium text-yellow-800">
                                    {{ alert.title }}
                                </h3>
                                <p class="text-sm text-yellow-700">
                                    {{ alert.message }}
                                </p>
                            </div>
                        </div>
                        <button
                            @click="router.visit(route(alert.route))"
                            class="text-yellow-800 hover:text-yellow-900 text-sm font-medium underline"
                        >
                            {{ alert.action }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 animate-fade-in"
            >
                <!-- Total Enrollments -->
                <div
                    @click="navigateToStats('enrollments')"
                    class="bg-white rounded-xl border border-gray-200 p-6 flex items-center gap-4 cursor-pointer transform transition-all duration-200 hover:scale-105 hover:shadow-lg hover:border-blue-300"
                >
                    <div class="flex-1">
                        <div class="text-sm text-gray-500 font-medium">
                            Total Enrollments
                        </div>
                        <div class="text-3xl font-bold text-gray-900">
                            {{ totalEnrollments.value }}
                        </div>
                        <div
                            class="flex items-center gap-2 mt-2"
                            v-if="totalEnrollments.change !== 0"
                        >
                            <span
                                :class="`${
                                    formatChange(totalEnrollments.change)
                                        .bgColor
                                } ${
                                    formatChange(totalEnrollments.change).color
                                } px-2 py-1 rounded-full text-xs font-semibold flex items-center gap-1`"
                            >
                                {{ formatChange(totalEnrollments.change).sign }}
                                {{
                                    formatChange(totalEnrollments.change).value
                                }}%
                            </span>
                            <span class="text-xs text-gray-400"
                                >vs. last month</span
                            >
                        </div>
                    </div>
                    <div
                        class="bg-blue-50 rounded-full p-3 group-hover:bg-blue-100 transition-colors"
                    >
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

                <!-- Active Programs -->
                <div
                    @click="navigateToStats('programs')"
                    class="bg-white rounded-xl border border-gray-200 p-6 flex items-center gap-4 cursor-pointer transform transition-all duration-200 hover:scale-105 hover:shadow-lg hover:border-green-300"
                >
                    <div class="flex-1">
                        <div class="text-sm text-gray-500 font-medium">
                            Active Programs
                        </div>
                        <div class="text-3xl font-bold text-gray-900">
                            {{ activePrograms.value }}
                        </div>
                        <div
                            class="flex items-center gap-2 mt-2"
                            v-if="activePrograms.change !== 0"
                        >
                            <span
                                :class="`${
                                    formatChange(activePrograms.change).bgColor
                                } ${
                                    formatChange(activePrograms.change).color
                                } px-2 py-1 rounded-full text-xs font-semibold flex items-center gap-1`"
                            >
                                {{ formatChange(activePrograms.change).sign }}
                                {{ formatChange(activePrograms.change).value }}%
                            </span>
                            <span class="text-xs text-gray-400"
                                >vs. last month</span
                            >
                        </div>
                    </div>
                    <div
                        class="bg-green-50 rounded-full p-3 group-hover:bg-green-100 transition-colors"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6 text-green-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                            />
                        </svg>
                    </div>
                </div>

                <!-- Completed Trainings -->
                <div
                    @click="navigateToStats('trainings')"
                    class="bg-white rounded-xl border border-gray-200 p-6 flex items-center gap-4 cursor-pointer transform transition-all duration-200 hover:scale-105 hover:shadow-lg hover:border-yellow-300"
                >
                    <div class="flex-1">
                        <div class="text-sm text-gray-500 font-medium">
                            Completed Trainings
                        </div>
                        <div class="text-3xl font-bold text-gray-900">
                            {{ completedTrainings.value }}
                        </div>
                        <div
                            class="flex items-center gap-2 mt-2"
                            v-if="completedTrainings.change !== 0"
                        >
                            <span
                                :class="`${
                                    formatChange(completedTrainings.change)
                                        .bgColor
                                } ${
                                    formatChange(completedTrainings.change)
                                        .color
                                } px-2 py-1 rounded-full text-xs font-semibold flex items-center gap-1`"
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
                    <div
                        class="bg-yellow-50 rounded-full p-3 group-hover:bg-yellow-100 transition-colors"
                    >
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
                                d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"
                            />
                        </svg>
                    </div>
                </div>

                <!-- Total Assessments -->
                <div
                    @click="navigateToStats('assessments')"
                    class="bg-white rounded-xl border border-gray-200 p-6 flex items-center gap-4 cursor-pointer transform transition-all duration-200 hover:scale-105 hover:shadow-lg hover:border-purple-300"
                >
                    <div class="flex-1">
                        <div class="text-sm text-gray-500 font-medium">
                            Total Assessments
                        </div>
                        <div class="text-3xl font-bold text-gray-900">
                            {{ totalAssessments.value }}
                        </div>
                        <div
                            class="flex items-center gap-2 mt-2"
                            v-if="totalAssessments.change !== 0"
                        >
                            <span
                                :class="`${
                                    formatChange(totalAssessments.change)
                                        .bgColor
                                } ${
                                    formatChange(totalAssessments.change).color
                                } px-2 py-1 rounded-full text-xs font-semibold flex items-center gap-1`"
                            >
                                {{ formatChange(totalAssessments.change).sign }}
                                {{
                                    formatChange(totalAssessments.change).value
                                }}%
                            </span>
                            <span class="text-xs text-gray-400"
                                >vs. last month</span
                            >
                        </div>
                    </div>
                    <div
                        class="bg-purple-50 rounded-full p-3 group-hover:bg-purple-100 transition-colors"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-6 w-6 text-purple-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Assessment Summary Cards -->
            <div
                class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 animate-fade-in"
                v-if="assessment_summary"
            >
                <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-blue-700">
                                Pending Assessments
                            </p>
                            <p class="text-2xl font-bold text-blue-900">
                                {{ assessment_summary.pending }}
                            </p>
                        </div>
                        <div class="p-2 bg-blue-100 rounded-full">
                            <svg
                                class="h-5 w-5 text-blue-600"
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
                        </div>
                    </div>
                </div>
                <div
                    class="bg-indigo-50 rounded-lg p-4 border border-indigo-200"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-indigo-700">
                                Completed Assessments
                            </p>
                            <p class="text-2xl font-bold text-indigo-900">
                                {{ assessment_summary.completed }}
                            </p>
                        </div>
                        <div class="p-2 bg-indigo-100 rounded-full">
                            <svg
                                class="h-5 w-5 text-indigo-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-green-700">
                                Applicants Passed
                            </p>
                            <p class="text-2xl font-bold text-green-900">
                                {{ assessment_summary.passed }}
                            </p>
                        </div>
                        <div class="p-2 bg-green-100 rounded-full">
                            <svg
                                class="h-5 w-5 text-green-600"
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
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Enrollments -->
            <div class="mb-4 text-xl font-semibold text-gray-900">
                Recent Enrollments
            </div>

            <!-- Recent Enrollments Section -->
            <div
                class="bg-white rounded-xl border border-gray-200 p-6 animate-fade-in"
            >
                <div class="flex justify-between items-center mb-4">
                    <div class="font-semibold text-lg">Recent Enrollments</div>
                    <a
                        :href="route('officer.trainees')"
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
                                    Program
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
                                    {{ enrollment.program }}
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
                                        @click="viewTrainee(enrollment)"
                                        class="text-gray-500 hover:text-blue-600 p-1 rounded"
                                        title="View Trainee"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
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
                                        @click="
                                            viewEnrollmentHistory(enrollment)
                                        "
                                        class="text-gray-500 hover:text-green-600 p-1 rounded"
                                        title="Enrollment History"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-4 w-4"
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
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
                        />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">
                        No enrollments found
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

            <!-- Recent Assessments Section -->
            <div
                class="bg-white rounded-xl border border-gray-200 p-6 animate-fade-in"
                v-if="recent_assessments && recent_assessments.length > 0"
            >
                <div class="flex justify-between items-center mb-4">
                    <div class="font-semibold text-lg">Recent Assessments</div>
                    <button
                        @click="viewAssessments"
                        class="text-blue-600 hover:underline text-sm font-medium"
                    >
                        View All
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                                >
                                    Assessment
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                                >
                                    Applicant
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                                >
                                    Program
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                                >
                                    Score
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                                >
                                    Date
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="assessment in recent_assessments"
                                :key="assessment.id"
                                class="hover:bg-gray-50"
                            >
                                <td class="px-6 py-4">
                                    <div>
                                        <div
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{ assessment.title }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            ID: {{ assessment.id }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div>
                                        <div
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{ assessment.applicant_name }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ assessment.applicant_type }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ assessment.program_name }}
                                </td>
                                <td class="px-6 py-4">
                                    <div v-if="assessment.score !== null">
                                        <div
                                            class="text-sm font-semibold text-gray-900"
                                        >
                                            {{ assessment.score }}/{{
                                                assessment.max_score
                                            }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ assessment.percentage }}%
                                        </div>
                                    </div>
                                    <div v-else class="text-xs text-gray-400">
                                        Not graded
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="`${getAssessmentStatusBadgeClass(
                                            assessment.status
                                        )} px-3 py-1 rounded-full text-xs font-semibold`"
                                    >
                                        {{
                                            assessment.status
                                                .charAt(0)
                                                .toUpperCase() +
                                            assessment.status.slice(1)
                                        }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ assessment.assessment_date }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
