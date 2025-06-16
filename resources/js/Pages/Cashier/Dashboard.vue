<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";

// Sample data - replace with actual data from backend
const stats = ref({
    totalCollected: 86950,
    totalCollectedChange: 15,
    pendingPayments: 30,
    pendingPaymentsChange: -5,
    receiptsGenerated: 82,
    receiptsGeneratedChange: 8,
});

const paymentStatus = ref([
    {
        id: "P001",
        initials: "JD",
        name: "Juan Dela Cruz",
        course: "Web Development",
        amountDue: 5000,
        status: "paid",
    },
    {
        id: "P002",
        initials: "MS",
        name: "Maria Santos",
        course: "Baking & Pastry Arts",
        amountDue: 4500,
        status: "paid",
    },
    {
        id: "P003",
        initials: "PR",
        name: "Pedro Reyes",
        course: "Electrical Installation",
        amountDue: 3000,
        status: "pending",
    },
    {
        id: "P004",
        initials: "ET",
        name: "Elena Torres",
        course: "Culinary Arts",
        amountDue: 4000,
        status: "paid",
    },
    {
        id: "P005",
        initials: "RA",
        name: "Roberto Aquino",
        course: "Computer Servicing",
        amountDue: 2500,
        status: "pending",
    },
]);

const paymentSummaries = ref([
    {
        period: "May 2025",
        totalAmount: 25600,
        paid: 24,
        pending: 30,
    },
    {
        period: "April 2025",
        totalAmount: 32450,
        paid: 34,
        pending: 20,
    },
    {
        period: "March 2025",
        totalAmount: 28900,
        paid: 30,
        pending: 12,
    },
]);

const recentActivities = ref([
    {
        id: 1,
        description:
            "Payment received for John Smith - Welding Technology (₱1,500)",
        timestamp: "2025-05-04T06:30:00Z",
    },
    {
        id: 2,
        description:
            "Partial payment received for Jane Doe - Food Processing (₱750)",
        timestamp: "2025-05-03T13:45:00Z",
    },
    {
        id: 3,
        description: "Payment received for Miguel Lopez - Carpentry (₱1,200)",
        timestamp: "2025-05-02T10:15:00Z",
    },
    {
        id: 4,
        description:
            "Receipt generated for Sofia Garcia - Electronics Servicing",
        timestamp: "2025-05-01T14:20:00Z",
    },
]);

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
    console.log("Recording payment for ID:", paymentId);
    // Implement payment recording logic
};

const viewDetails = (paymentId) => {
    // Redirect to payments page with the specific payment ID
    router.visit(route("cashier.payments"), {
        data: {
            view: paymentId,
            tab: "all",
        },
        preserveState: false,
    });
};

const generateReport = (period) => {
    console.log("Generating report for:", period);
    // Implement report generation logic
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
                    Good evening, Cashier. Welcome to your cashier dashboard.
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

            <!-- Payment Status Section -->
            <div class="mb-8 animate-fade-in">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900">
                        Payment Status
                    </h2>
                    <div class="flex items-center space-x-4">
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

                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                >
                    <div
                        v-for="payment in paymentStatus"
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
                                    class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-semibold mr-3"
                                >
                                    {{ payment.initials }}
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900">
                                        {{ payment.name }}
                                    </h3>
                                    <p class="text-sm text-gray-600">
                                        {{ payment.course }}
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
                    <div class="overflow-x-auto">
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
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                    >
                                        <button
                                            @click="
                                                generateReport(summary.period)
                                            "
                                            class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                        >
                                            <svg
                                                class="w-4 h-4 mr-1"
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
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900">
                        Recent Activities
                    </h2>
                    <button
                        class="text-blue-600 hover:text-blue-700 text-sm font-medium"
                    >
                        View All
                    </button>
                </div>

                <div
                    class="bg-white rounded-xl shadow-sm border border-gray-100 p-6"
                >
                    <div class="space-y-4">
                        <div
                            v-for="activity in recentActivities"
                            :key="activity.id"
                            class="flex items-start space-x-3"
                        >
                            <div class="flex-shrink-0">
                                <div
                                    class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center"
                                >
                                    <svg
                                        class="w-4 h-4 text-blue-600"
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
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900">
                                    {{ activity.description }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ formatDate(activity.timestamp) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
