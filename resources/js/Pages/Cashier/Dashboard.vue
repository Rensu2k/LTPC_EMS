<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import Pagination from "@/Components/Pagination.vue";

// Define props to receive data from backend
const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            totalCollected: 0,
            totalCollectedChange: 0,
            pendingPayments: 0,
            pendingPaymentsChange: 0,
            receiptsGenerated: 0,
            receiptsGeneratedChange: 0,
        }),
    },
    paymentStatus: {
        type: [Object, Array], // Support both pagination object and legacy array
        default: () => [],
    },
    paymentSummaries: {
        type: Array,
        default: () => [],
    },
    recentActivities: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

// Convert props to reactive refs for template usage
const stats = ref(props.stats);
const paymentStatus = ref(props.paymentStatus);
const paymentSummaries = ref(props.paymentSummaries);
const recentActivities = ref(props.recentActivities);

// Get payment status data with pagination support
const paymentStatusData = computed(() => {
    return props.paymentStatus?.data || props.paymentStatus || [];
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 0,
    }).format(amount);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString("en-PH", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const recordPayment = (paymentId) => {
    // TODO: Implement payment recording logic
};

const viewDetails = (paymentId) => {
    // Determine which tab the payment belongs to based on ID prefix
    let targetTab = "registrations"; // default
    if (paymentId.startsWith("ENR-")) {
        targetTab = "enrollments";
    } else if (paymentId.startsWith("ASS-")) {
        targetTab = "assessments";
    } else if (paymentId.startsWith("REG-")) {
        targetTab = "registrations";
    }

    // Redirect to payments page and switch to the appropriate tab
    router.visit(
        route("cashier.payments") + `?view=${paymentId}&tab=${targetTab}`,
        {
            preserveState: false,
        }
    );
};

const generateReport = (period) => {
    // TODO: Implement report generation logic
};
</script>

<template>
    <Head title="Cashier Dashboard" />
    <AuthenticatedLayout>
        <div class="py-8 px-8 bg-gray-50 min-h-screen">
            <!-- Header -->
            <div class="mb-8 animate-fade-in">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    Cashier Dashboard
                </h1>
                <p class="text-gray-600">
                    Monitor payment collections including registration fees,
                    enrollment fees, and assessment payments.
                </p>
            </div>

            <!-- Statistics Cards -->
            <div
                class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 animate-fade-in"
            >
                <!-- Total Collected -->
                <div
                    class="bg-white rounded-xl shadow-sm p-6 border border-gray-100"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm font-medium text-gray-600">
                                Total Collected
                            </p>
                            <p class="text-3xl font-bold text-gray-900">
                                {{ formatCurrency(stats.totalCollected) }}
                            </p>
                        </div>
                        <div class="p-3 bg-green-100 rounded-lg">
                            <svg
                                class="w-6 h-6 text-green-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3z"
                                ></path>
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <svg
                            class="w-4 h-4 text-green-500 mr-1"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 10l7-7m0 0l7 7m-7-7v18"
                            ></path>
                        </svg>
                        <span class="text-sm font-medium text-green-600"
                            >{{ stats.totalCollectedChange }}%</span
                        >
                        <span class="text-sm text-gray-500 ml-1"
                            >vs. last month</span
                        >
                    </div>
                </div>

                <!-- Pending Payments -->
                <div
                    class="bg-white rounded-xl shadow-sm p-6 border border-gray-100"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm font-medium text-gray-600">
                                Pending Payments
                            </p>
                            <p class="text-3xl font-bold text-gray-900">
                                {{ stats.pendingPayments }}
                            </p>
                        </div>
                        <div class="p-3 bg-orange-100 rounded-lg">
                            <svg
                                class="w-6 h-6 text-orange-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                ></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <svg
                            class="w-4 h-4 text-red-500 mr-1"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 14l-7 7m0 0l-7-7m7 7V3"
                            ></path>
                        </svg>
                        <span class="text-sm font-medium text-red-600"
                            >{{ Math.abs(stats.pendingPaymentsChange) }}%</span
                        >
                        <span class="text-sm text-gray-500 ml-1"
                            >vs. last month</span
                        >
                    </div>
                </div>

                <!-- Receipts Generated -->
                <div
                    class="bg-white rounded-xl shadow-sm p-6 border border-gray-100"
                >
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm font-medium text-gray-600">
                                Receipts Generated
                            </p>
                            <p class="text-3xl font-bold text-gray-900">
                                {{ stats.receiptsGenerated }}
                            </p>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <svg
                                class="w-6 h-6 text-blue-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                ></path>
                            </svg>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <svg
                            class="w-4 h-4 text-green-500 mr-1"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5 10l7-7m0 0l7 7m-7-7v18"
                            ></path>
                        </svg>
                        <span class="text-sm font-medium text-green-600"
                            >{{ stats.receiptsGeneratedChange }}%</span
                        >
                        <span class="text-sm text-gray-500 ml-1"
                            >vs. last month</span
                        >
                    </div>
                </div>
            </div>

            <!-- Payment Types Overview -->
            <div class="mb-8 animate-fade-in">
                <h2 class="text-xl font-bold text-gray-900 mb-6">
                    Payment Types Overview
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Registration Payments Card -->
                    <div
                        class="bg-white rounded-xl shadow-sm p-6 border border-gray-100"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">
                                    Enrollment Fees - New
                                </p>
                                <p class="text-2xl font-bold text-orange-600">
                                    {{
                                        paymentStatusData.filter((p) =>
                                            p.id.startsWith("REG-")
                                        ).length
                                    }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    New trainees awaiting enrollment fee payment
                                </p>
                            </div>
                            <div class="p-3 bg-orange-100 rounded-lg">
                                <svg
                                    class="w-6 h-6 text-orange-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                                    ></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Enrollment Payments Card -->
                    <div
                        class="bg-white rounded-xl shadow-sm p-6 border border-gray-100"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">
                                    Enrollment Fees - Enrolled
                                </p>
                                <p class="text-2xl font-bold text-blue-600">
                                    {{
                                        paymentStatusData.filter((p) =>
                                            p.id.startsWith("ENR-")
                                        ).length
                                    }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    Additional fees from enrolled trainees
                                </p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <svg
                                    class="w-6 h-6 text-blue-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C20.168 18.477 18.582 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                                    ></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Assessment Payments Card -->
                    <div
                        class="bg-white rounded-xl shadow-sm p-6 border border-gray-100"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-600">
                                    Assessment Payments
                                </p>
                                <p class="text-2xl font-bold text-purple-600">
                                    {{
                                        paymentStatusData.filter((p) =>
                                            p.id.startsWith("ASS-")
                                        ).length
                                    }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    Competency assessments
                                </p>
                            </div>
                            <div class="p-3 bg-purple-100 rounded-lg">
                                <svg
                                    class="w-6 h-6 text-purple-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                                    ></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Status Section -->
            <div class="mb-8 animate-fade-in">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <!-- task: make a pagination even if the items does not exceed the per page -->
                        <h2 class="text-xl font-bold text-gray-900">
                            Recent Payment Status
                        </h2>
                        <!-- Results Count -->
                        <div class="mt-2 text-sm text-gray-600">
                            <span
                                v-if="props.paymentStatus?.meta?.total"
                                class="font-medium text-blue-600"
                            >
                                📊 Showing
                                {{
                                    (props.paymentStatus.meta.current_page -
                                        1) *
                                        props.paymentStatus.meta.per_page +
                                    1
                                }}
                                to
                                {{
                                    Math.min(
                                        props.paymentStatus.meta.current_page *
                                            props.paymentStatus.meta.per_page,
                                        props.paymentStatus.meta.total
                                    )
                                }}
                                of {{ props.paymentStatus.meta.total }} results
                                (Page
                                {{ props.paymentStatus.meta.current_page }} of
                                {{ props.paymentStatus.meta.last_page }})
                            </span>
                            <span v-else class="text-gray-500">
                                📋 {{ paymentStatusData.length }} payments found
                                (no pagination)
                            </span>
                        </div>

                        <!-- Debug Info (remove this later) -->
                        <div class="mt-1 text-xs text-gray-400">
                            <span v-if="props.paymentStatus?.meta">
                                Debug: Per page:
                                {{ props.paymentStatus.meta.per_page }},
                                Current:
                                {{ props.paymentStatus.meta.current_page }},
                                Total: {{ props.paymentStatus.meta.total }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4 flex-wrap">
                        <div class="flex items-center">
                            <div
                                class="w-3 h-3 bg-orange-500 rounded-full mr-2"
                            ></div>
                            <span class="text-sm text-gray-600"
                                >New Enrollment</span
                            >
                        </div>
                        <div class="flex items-center">
                            <div
                                class="w-3 h-3 bg-blue-500 rounded-full mr-2"
                            ></div>
                            <span class="text-sm text-gray-600"
                                >Enrolled Batches</span
                            >
                        </div>
                        <div class="flex items-center">
                            <div
                                class="w-3 h-3 bg-purple-500 rounded-full mr-2"
                            ></div>
                            <span class="text-sm text-gray-600"
                                >Assessment</span
                            >
                        </div>
                        <div class="flex items-center">
                            <div
                                class="w-3 h-3 bg-red-500 rounded-full mr-2"
                            ></div>
                            <span class="text-sm text-gray-600">Pending</span>
                        </div>
                        <div class="flex items-center">
                            <div
                                class="w-3 h-3 bg-green-500 rounded-full mr-2"
                            ></div>
                            <span class="text-sm text-gray-600">Paid</span>
                        </div>
                    </div>
                </div>

                <!-- Pagination Status Banner -->
                <div
                    v-if="props.paymentStatus?.meta?.total"
                    class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="text-blue-600">🔢</span>
                            <span class="text-sm font-medium text-blue-800">
                                Pagination Active:
                                {{ props.paymentStatus.meta.current_page }} of
                                {{ props.paymentStatus.meta.last_page }} pages
                            </span>
                        </div>
                        <div class="text-xs text-blue-600">
                            {{ props.paymentStatus.meta.per_page }} items per
                            page
                        </div>
                    </div>
                </div>

                <div
                    v-if="paymentStatusData.length === 0"
                    class="bg-white rounded-xl shadow-sm p-12 border border-gray-100 text-center"
                >
                    <svg
                        class="mx-auto h-12 w-12 text-gray-400 mb-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3z"
                        ></path>
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        ></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                        No payment data available
                    </h3>
                    <p class="text-gray-500">
                        Payment information will appear here when data is
                        available.
                    </p>
                </div>

                <div
                    v-else
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                >
                    <div
                        v-for="payment in paymentStatusData"
                        :key="payment.id"
                        class="bg-white rounded-xl shadow-sm p-6 border border-gray-100"
                        :class="
                            payment.status === 'paid'
                                ? 'bg-green-50 border-green-200'
                                : 'bg-red-50 border-red-200'
                        "
                    >
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center">
                                <div
                                    class="w-10 h-10 rounded-full flex items-center justify-center text-white font-semibold mr-3"
                                    :class="{
                                        'bg-orange-500':
                                            payment.id.startsWith('REG-'),
                                        'bg-blue-500':
                                            payment.id.startsWith('ENR-'),
                                        'bg-purple-500':
                                            payment.id.startsWith('ASS-'),
                                    }"
                                >
                                    {{ payment.initials }}
                                </div>
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <h3 class="font-semibold text-gray-900">
                                            {{ payment.name }}
                                        </h3>
                                        <span
                                            class="px-2 py-0.5 text-xs font-medium rounded-full"
                                            :class="{
                                                'bg-orange-100 text-orange-800':
                                                    payment.id.startsWith(
                                                        'REG-'
                                                    ),
                                                'bg-blue-100 text-blue-800':
                                                    payment.id.startsWith(
                                                        'ENR-'
                                                    ),
                                                'bg-purple-100 text-purple-800':
                                                    payment.id.startsWith(
                                                        'ASS-'
                                                    ),
                                            }"
                                        >
                                            {{
                                                payment.id.startsWith("REG-")
                                                    ? "New Enrollment"
                                                    : payment.id.startsWith(
                                                          "ENR-"
                                                      )
                                                    ? "Enrolled Batch"
                                                    : "Assessment"
                                            }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600">
                                        {{ payment.program }}
                                    </p>
                                    <p
                                        v-if="payment.is_scholarship"
                                        class="text-xs text-purple-600 font-medium"
                                    >
                                        🎓 Scholar
                                    </p>
                                </div>
                            </div>
                            <span
                                class="px-2 py-1 text-xs font-medium rounded-full"
                                :class="
                                    payment.status === 'paid'
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-red-100 text-red-800'
                                "
                            >
                                {{
                                    payment.status === "paid"
                                        ? "Paid"
                                        : "Pending"
                                }}
                            </span>
                        </div>

                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Amount Due:</span>
                                <span class="font-medium">{{
                                    formatCurrency(payment.amountDue)
                                }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600"
                                    >Payment Status:</span
                                >
                                <span
                                    class="font-medium"
                                    :class="
                                        payment.status === 'paid'
                                            ? 'text-green-600'
                                            : 'text-red-600'
                                    "
                                >
                                    {{
                                        payment.status === "paid"
                                            ? "✓ Paid"
                                            : "⚠ Pending"
                                    }}
                                </span>
                            </div>
                        </div>

                        <div class="flex">
                            <button
                                @click="viewDetails(payment.id)"
                                class="w-full px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                            >
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination for Payment Status -->
            <div
                v-if="
                    paymentStatusData.length > 0 &&
                    props.paymentStatus?.meta?.last_page > 1
                "
                class="mb-8"
            >
                <Pagination :data="paymentStatus" />
            </div>

            <!-- Payment Summaries -->
            <div class="mb-8 animate-fade-in">
                <h2 class="text-xl font-bold text-gray-900 mb-2">
                    Payment Summaries
                </h2>
                <p class="text-gray-600 mb-6">
                    Monthly payment collection and status overview
                </p>

                <div
                    class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
                >
                    <div
                        v-if="paymentSummaries.length === 0"
                        class="p-12 text-center"
                    >
                        <svg
                            class="mx-auto h-12 w-12 text-gray-400 mb-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                            ></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            No payment summaries available
                        </h3>
                        <p class="text-gray-500">
                            Payment summary data will appear here when
                            available.
                        </p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Period
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Total Amount
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Paid
                                    </th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Pending
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
                                    v-for="summary in paymentSummaries"
                                    :key="summary.period"
                                    class="hover:bg-gray-50"
                                >
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                    >
                                        {{ summary.period }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{
                                            formatCurrency(summary.totalAmount)
                                        }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                        >
                                            {{ summary.paid }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
                                        >
                                            {{ summary.pending }}
                                        </span>
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium"
                                    >
                                        <button
                                            @click="
                                                generateReport(summary.period)
                                            "
                                            class="text-blue-600 hover:text-blue-900"
                                        >
                                            Generate Report
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="animate-fade-in">
                <h2 class="text-xl font-bold text-gray-900 mb-2">
                    Recent Activities
                </h2>
                <p class="text-gray-600 mb-6">Latest payment transactions</p>

                <div
                    class="bg-white rounded-xl shadow-sm border border-gray-100"
                >
                    <div
                        v-if="recentActivities.length === 0"
                        class="p-12 text-center"
                    >
                        <svg
                            class="mx-auto h-12 w-12 text-gray-400 mb-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            ></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            No recent activities
                        </h3>
                        <p class="text-gray-500">
                            Recent payment activities will appear here.
                        </p>
                    </div>

                    <div v-else class="p-6">
                        <div class="flow-root">
                            <ul class="-mb-8">
                                <li
                                    v-for="(
                                        activity, index
                                    ) in recentActivities"
                                    :key="activity.id"
                                >
                                    <div class="relative pb-8">
                                        <span
                                            v-if="
                                                index !==
                                                recentActivities.length - 1
                                            "
                                            class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                                            aria-hidden="true"
                                        ></span>
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span
                                                    class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white"
                                                >
                                                    <svg
                                                        class="h-4 w-4 text-white"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3z"
                                                        ></path>
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                        ></path>
                                                    </svg>
                                                </span>
                                            </div>
                                            <div
                                                class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4"
                                            >
                                                <div>
                                                    <p
                                                        class="text-sm text-gray-500"
                                                    >
                                                        {{
                                                            activity.description
                                                        }}
                                                    </p>
                                                </div>
                                                <div
                                                    class="text-right text-sm whitespace-nowrap text-gray-500"
                                                >
                                                    <time>{{
                                                        formatDate(
                                                            activity.timestamp
                                                        )
                                                    }}</time>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.5s ease-out;
}
</style>
