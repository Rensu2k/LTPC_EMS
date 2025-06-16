<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";

// Get URL parameters
const page = usePage();

// Active tab state
const activeTab = ref("all");

// Payment details modal state
const showPaymentDetails = ref(false);
const selectedPayment = ref(null);

// Sample payment data - replace with actual data from backend
const paymentRecords = ref([
    {
        id: "P001",
        trainee: {
            name: "Juan Dela Cruz",
            id: "T-2023-0124",
        },
        course: "Web Development",
        amount: 5000.0,
        receiptNo: "RN-2023-001",
        date: "5/12/2023",
        status: "paid",
    },
    {
        id: "P002",
        trainee: {
            name: "Maria Santos",
            id: "T-2023-0125",
        },
        course: "Baking & Pastry Arts",
        amount: 4500.0,
        receiptNo: "RN-2023-002",
        date: "5/15/2023",
        status: "paid",
    },
    {
        id: "P003",
        trainee: {
            name: "Pedro Reyes",
            id: "T-2023-0126",
        },
        course: "Electrical Installation",
        amount: 3000.0,
        receiptNo: "RN-2023-003",
        date: "5/20/2023",
        status: "unpaid",
    },
    {
        id: "P004",
        trainee: {
            name: "Elena Torres",
            id: "T-2023-0128",
        },
        course: "Culinary Arts",
        amount: 4000.0,
        receiptNo: "RN-2023-004",
        date: "6/15/2023",
        status: "paid",
    },
    {
        id: "P005",
        trainee: {
            name: "Roberto Aquino",
            id: "T-2023-0127",
        },
        course: "Computer Servicing",
        amount: 2500.0,
        receiptNo: "RN-2023-005",
        date: "6/12/2023",
        status: "unpaid",
    },
]);

const searchQuery = ref("");

// Summary data for Payment Summary tab
const summaryStats = ref({
    totalCollections: {
        amount: 245830,
        trainees: 156,
    },
    thisMonth: {
        amount: 45250,
        trainees: 23,
    },
    outstandingBalance: {
        amount: 32500,
        trainees: 15,
    },
});

const collectionsByCourse = ref([
    {
        course: "Web Development",
        totalTrainees: 24,
        fullyPaid: 20,
        partiallyPaid: 3,
        unpaid: 1,
        collectionAmount: 115000,
    },
    {
        course: "Culinary Arts",
        totalTrainees: 18,
        fullyPaid: 15,
        partiallyPaid: 2,
        unpaid: 1,
        collectionAmount: 72000,
    },
    {
        course: "Dressmaking",
        totalTrainees: 15,
        fullyPaid: 10,
        partiallyPaid: 3,
        unpaid: 2,
        collectionAmount: 42500,
    },
    {
        course: "Electrical Installation",
        totalTrainees: 22,
        fullyPaid: 16,
        partiallyPaid: 4,
        unpaid: 2,
        collectionAmount: 94000,
    },
    {
        course: "Baking & Pastry Arts",
        totalTrainees: 20,
        fullyPaid: 17,
        partiallyPaid: 2,
        unpaid: 1,
        collectionAmount: 83600,
    },
]);

// Computed filtered payments based on active tab and search
const filteredPayments = computed(() => {
    let filtered = paymentRecords.value;

    // Filter by tab
    if (activeTab.value === "pending") {
        filtered = filtered.filter((payment) => payment.status === "unpaid");
    }

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(
            (payment) =>
                payment.trainee.name.toLowerCase().includes(query) ||
                payment.course.toLowerCase().includes(query) ||
                payment.receiptNo.toLowerCase().includes(query) ||
                payment.trainee.id.toLowerCase().includes(query)
        );
    }

    return filtered;
});

const formatCurrency = (amount) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 2,
    }).format(amount);
};

const setActiveTab = (tab) => {
    activeTab.value = tab;
};

const exportReport = () => {
    console.log("Exporting report...");
    // Implement export functionality
};

const recordPayment = () => {
    console.log("Recording new payment...");
    // Implement record payment functionality
};

const viewPayment = (paymentId) => {
    const payment = paymentRecords.value.find((p) => p.id === paymentId);
    if (payment) {
        selectedPayment.value = payment;
        showPaymentDetails.value = true;
    }
};

const closePaymentDetails = () => {
    showPaymentDetails.value = false;
    selectedPayment.value = null;
};

const markAsPaid = (paymentId) => {
    // Find and update the payment status
    const paymentIndex = paymentRecords.value.findIndex(
        (p) => p.id === paymentId
    );
    if (paymentIndex !== -1) {
        paymentRecords.value[paymentIndex].status = "paid";
        // Update the selected payment if it's currently being viewed
        if (selectedPayment.value && selectedPayment.value.id === paymentId) {
            selectedPayment.value.status = "paid";
        }

        // Here you would typically make an API call to update the backend
        console.log(`Payment ${paymentId} marked as paid`);

        // Show success message (you can implement a toast notification here)
        alert("Payment successfully marked as paid!");
    }
};

// Check for incoming payment ID from dashboard
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const viewParam = urlParams.get("view");
    if (viewParam) {
        viewPayment(viewParam);
    }
});

const generateReceipt = (paymentId) => {
    const payment = paymentRecords.value.find((p) => p.id === paymentId);
    if (payment && payment.status === "paid") {
        // Generate receipt data
        const receiptData = {
            id: payment.receiptNo,
            paymentId: payment.id,
            trainee: {
                name: payment.trainee.name,
                id: payment.trainee.id,
            },
            course: payment.course,
            amount: payment.amount,
            dateGenerated: new Date().toLocaleDateString("en-CA"), // YYYY-MM-DD format
            timeGenerated: new Date().toLocaleTimeString("en-US", {
                hour: "numeric",
                minute: "2-digit",
                hour12: true,
            }),
            status: "generated",
        };

        // Add receipt to receipts page if the function exists
        if (typeof window.addReceiptToPage === "function") {
            window.addReceiptToPage(receiptData);
        }

        // Here you would typically make an API call to save the receipt to the backend
        console.log("Receipt generated:", receiptData);

        // Show success message
        alert(
            `Receipt ${receiptData.id} has been generated and saved to Receipts page!`
        );

        // Close the modal after generating receipt
        closePaymentDetails();
    } else {
        alert("Receipt can only be generated for paid payments.");
    }
};
</script>

<template>
    <Head title="Payments" />
    <AuthenticatedLayout>
        <div class="py-8 px-8 bg-gray-50 min-h-screen">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8 animate-fade-in">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        Payments Management
                    </h1>
                </div>
                <div class="flex items-center space-x-3">
                    <button
                        @click="exportReport"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
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
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            ></path>
                        </svg>
                        Export Report
                    </button>
                    <button
                        @click="recordPayment"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
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
                            ></path>
                        </svg>
                        Record Payment
                    </button>
                </div>
            </div>

            <!-- Tabs -->
            <div class="mb-6 animate-fade-in">
                <nav class="flex space-x-8">
                    <button
                        @click="setActiveTab('all')"
                        :class="[
                            'py-2 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'all'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                        ]"
                    >
                        All Payments
                    </button>
                    <button
                        @click="setActiveTab('pending')"
                        :class="[
                            'py-2 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'pending'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                        ]"
                    >
                        Pending Payments
                    </button>
                    <button
                        @click="setActiveTab('summary')"
                        :class="[
                            'py-2 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'summary'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                        ]"
                    >
                        Payment Summary
                    </button>
                </nav>
            </div>

            <!-- Payment Summary Content -->
            <div
                v-if="activeTab === 'summary'"
                class="space-y-6 animate-fade-in"
            >
                <!-- Summary Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Total Collections -->
                    <div
                        class="bg-white rounded-xl shadow-sm p-6 border border-gray-100"
                    >
                        <div class="mb-4">
                            <h3 class="text-sm font-medium text-gray-600">
                                Total Collections
                            </h3>
                            <p class="text-3xl font-bold text-blue-600">
                                {{
                                    formatCurrency(
                                        summaryStats.totalCollections.amount
                                    )
                                }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">
                                From
                                {{ summaryStats.totalCollections.trainees }}
                                trainees
                            </p>
                        </div>
                    </div>

                    <!-- This Month -->
                    <div
                        class="bg-white rounded-xl shadow-sm p-6 border border-gray-100"
                    >
                        <div class="mb-4">
                            <h3 class="text-sm font-medium text-gray-600">
                                This Month
                            </h3>
                            <p class="text-3xl font-bold text-blue-600">
                                {{
                                    formatCurrency(
                                        summaryStats.thisMonth.amount
                                    )
                                }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">
                                From
                                {{ summaryStats.thisMonth.trainees }} trainees
                            </p>
                        </div>
                    </div>

                    <!-- Outstanding Balance -->
                    <div
                        class="bg-white rounded-xl shadow-sm p-6 border border-gray-100"
                    >
                        <div class="mb-4">
                            <h3 class="text-sm font-medium text-gray-600">
                                Outstanding Balance
                            </h3>
                            <p class="text-3xl font-bold text-red-600">
                                {{
                                    formatCurrency(
                                        summaryStats.outstandingBalance.amount
                                    )
                                }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">
                                From
                                {{ summaryStats.outstandingBalance.trainees }}
                                trainees
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Collections by Course Table -->
                <div
                    class="bg-white rounded-xl shadow-sm border border-gray-100"
                >
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Collections by Course
                        </h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Course
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Total Trainees
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Fully Paid
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Partially Paid
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Unpaid
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Collection Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="course in collectionsByCourse"
                                    :key="course.course"
                                    class="hover:bg-gray-50"
                                >
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                    >
                                        {{ course.course }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ course.totalTrainees }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ course.fullyPaid }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ course.partiallyPaid }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ course.unpaid }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                    >
                                        {{
                                            formatCurrency(
                                                course.collectionAmount
                                            )
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Payment Records Section -->
            <div
                v-else
                class="bg-white rounded-xl shadow-sm border border-gray-100 animate-fade-in"
            >
                <!-- Section Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Payment Records
                        </h2>
                        <div class="flex items-center space-x-3">
                            <!-- Search -->
                            <div class="relative">
                                <div
                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                                >
                                    <svg
                                        class="h-5 w-5 text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                        ></path>
                                    </svg>
                                </div>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search payments..."
                                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                />
                            </div>
                            <!-- Filter -->
                            <button
                                class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
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
                                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                                    ></path>
                                </svg>
                                Filter
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    ID
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Trainee
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Course
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Amount
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Receipt No.
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Date
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="payment in filteredPayments"
                                :key="payment.id"
                                class="hover:bg-gray-50"
                            >
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                >
                                    {{ payment.id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div
                                                class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center"
                                            >
                                                <span
                                                    class="text-sm font-medium text-gray-700"
                                                >
                                                    {{
                                                        payment.trainee.name
                                                            .split(" ")
                                                            .map((n) => n[0])
                                                            .join("")
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div
                                                class="text-sm font-medium text-gray-900"
                                            >
                                                {{ payment.trainee.name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ payment.trainee.id }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    {{ payment.course }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                >
                                    {{ formatCurrency(payment.amount) }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    {{ payment.receiptNo }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    {{ payment.date }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                            payment.status === 'paid'
                                                ? 'bg-green-100 text-green-800'
                                                : 'bg-red-100 text-red-800',
                                        ]"
                                    >
                                        {{
                                            payment.status === "paid"
                                                ? "Paid"
                                                : "Unpaid"
                                        }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium"
                                >
                                    <div class="flex items-center space-x-2">
                                        <button
                                            @click="viewPayment(payment.id)"
                                            class="text-blue-600 hover:text-blue-900"
                                            title="View Details"
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
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                ></path>
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                                ></path>
                                            </svg>
                                        </button>
                                        <button
                                            @click="generateReceipt(payment.id)"
                                            class="text-green-600 hover:text-green-900"
                                            title="Generate Receipt"
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
                                                ></path>
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
                    v-if="filteredPayments.length === 0"
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
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        ></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">
                        No payments found
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{
                            searchQuery
                                ? "Try adjusting your search terms."
                                : "Get started by recording a new payment."
                        }}
                    </p>
                </div>
            </div>

            <!-- Payment Details Modal -->
            <div
                v-if="showPaymentDetails"
                class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
                @click="closePaymentDetails"
            >
                <div
                    class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white"
                    @click.stop
                >
                    <div class="mt-3">
                        <!-- Modal Header -->
                        <div
                            class="flex items-center justify-between pb-4 border-b border-gray-200"
                        >
                            <h3 class="text-lg font-semibold text-gray-900">
                                Payment Details
                            </h3>
                            <button
                                @click="closePaymentDetails"
                                class="text-gray-400 hover:text-gray-600"
                            >
                                <svg
                                    class="w-6 h-6"
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
                            </button>
                        </div>

                        <!-- Modal Content -->
                        <div v-if="selectedPayment" class="mt-6 space-y-6">
                            <!-- Payment Information -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <h4
                                        class="text-md font-semibold text-gray-900"
                                    >
                                        Payment Information
                                    </h4>
                                    <div class="space-y-3">
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600"
                                                >Payment ID:</span
                                            >
                                            <span
                                                class="text-sm font-medium text-gray-900"
                                                >{{ selectedPayment.id }}</span
                                            >
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600"
                                                >Amount:</span
                                            >
                                            <span
                                                class="text-sm font-medium text-gray-900"
                                                >{{
                                                    formatCurrency(
                                                        selectedPayment.amount
                                                    )
                                                }}</span
                                            >
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600"
                                                >Receipt No.:</span
                                            >
                                            <span
                                                class="text-sm font-medium text-gray-900"
                                                >{{
                                                    selectedPayment.receiptNo
                                                }}</span
                                            >
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600"
                                                >Date:</span
                                            >
                                            <span
                                                class="text-sm font-medium text-gray-900"
                                                >{{
                                                    selectedPayment.date
                                                }}</span
                                            >
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600"
                                                >Status:</span
                                            >
                                            <span
                                                :class="[
                                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                    selectedPayment.status ===
                                                    'paid'
                                                        ? 'bg-green-100 text-green-800'
                                                        : 'bg-red-100 text-red-800',
                                                ]"
                                            >
                                                {{
                                                    selectedPayment.status ===
                                                    "paid"
                                                        ? "Paid"
                                                        : "Unpaid"
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <h4
                                        class="text-md font-semibold text-gray-900"
                                    >
                                        Trainee Information
                                    </h4>
                                    <div class="space-y-3">
                                        <div
                                            class="flex items-center space-x-3"
                                        >
                                            <div
                                                class="h-12 w-12 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold"
                                            >
                                                {{
                                                    selectedPayment.trainee.name
                                                        .split(" ")
                                                        .map((n) => n[0])
                                                        .join("")
                                                }}
                                            </div>
                                            <div>
                                                <div
                                                    class="text-sm font-medium text-gray-900"
                                                >
                                                    {{
                                                        selectedPayment.trainee
                                                            .name
                                                    }}
                                                </div>
                                                <div
                                                    class="text-sm text-gray-500"
                                                >
                                                    {{
                                                        selectedPayment.trainee
                                                            .id
                                                    }}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-sm text-gray-600"
                                                >Course:</span
                                            >
                                            <span
                                                class="text-sm font-medium text-gray-900"
                                                >{{
                                                    selectedPayment.course
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div
                                class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200"
                            >
                                <button
                                    @click="closePaymentDetails"
                                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    Close
                                </button>
                                <button
                                    v-if="selectedPayment.status === 'unpaid'"
                                    @click="markAsPaid(selectedPayment.id)"
                                    class="px-4 py-2 bg-blue-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    <svg
                                        class="w-4 h-4 mr-2 inline"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M5 13l4 4L19 7"
                                        ></path>
                                    </svg>
                                    Mark as Paid
                                </button>
                                <button
                                    @click="generateReceipt(selectedPayment.id)"
                                    class="px-4 py-2 bg-green-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                >
                                    <svg
                                        class="w-4 h-4 mr-2 inline"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                        ></path>
                                    </svg>
                                    Generate Receipt
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
