<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, usePage, router } from "@inertiajs/vue3";
import { ref, computed, onMounted, watch } from "vue";
import { useNotifications } from "@/composables/useNotifications";
import Pagination from "@/Components/Pagination.vue";

// Get URL parameters
const page = usePage();
const notifications = useNotifications();

// Payment type from props
const paymentType = computed(
    () => props.paymentType || props.filters?.type || "registration"
);

// Payment details modal state
const showPaymentDetails = ref(false);
const selectedPayment = ref(null);

// Define props to receive data from backend
const props = defineProps({
    enrollmentPayments: {
        type: [Object, Array], // Support both pagination object and legacy array
        default: () => [],
    },
    assessmentPayments: {
        type: Array,
        default: () => [],
    },
    summaryStats: {
        type: Object,
        default: () => ({
            totalCollections: { amount: 0, trainees: 0 },
            thisMonth: { amount: 0, trainees: 0 },
            outstandingBalance: { amount: 0, trainees: 0 },
        }),
    },
    collectionsByProgram: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    paymentCounts: {
        type: Object,
        default: () => ({
            registration: 0,
            enrollment: 0,
            assessment: 0,
        }),
    },
    paymentType: {
        type: String,
        default: "registration",
    },
});

// Convert props to reactive refs and separate registration vs enrollment payments
const allPayments = computed(() => {
    // Handle pagination object
    if (
        props.enrollmentPayments &&
        typeof props.enrollmentPayments === "object"
    ) {
        // Check if it's a pagination object with data property
        if (props.enrollmentPayments.data !== undefined) {
            // Laravel pagination object - data is an array
            if (Array.isArray(props.enrollmentPayments.data)) {
                return props.enrollmentPayments.data;
            }
            // If data exists but is not an array, it might be empty or malformed
            return [];
        }
        // Check if it's already an array (legacy support)
        if (Array.isArray(props.enrollmentPayments)) {
            return props.enrollmentPayments;
        }
    }
    // Fallback to empty array
    return [];
});

// Since we're now doing server-side filtering, we just display what's received
const currentPayments = computed(() => {
    return allPayments.value;
});

// Server already filters, so filteredPayments is just currentPayments
const filteredPayments = computed(() => {
    return currentPayments.value;
});

// Keep these for backward compatibility with code that might reference them
const registrationPayments = computed(() => {
    return allPayments.value.filter((p) => p.type === "registration");
});

const enrollmentPaymentsList = computed(() => {
    return allPayments.value.filter((p) => p.type === "enrollment");
});

const assessmentPayments = ref(props.assessmentPayments);
const summaryStats = ref(props.summaryStats);
const collectionsByProgram = ref(props.collectionsByProgram);

const searchQuery = ref(props.filters?.search || "");

const formatCurrency = (amount) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
        minimumFractionDigits: 2,
    }).format(amount);
};

// Computed properties for page title and description
const pageTitle = computed(() => {
    if (paymentType.value === "registration") {
        return "Enrollment Fees - New Trainees";
    } else if (paymentType.value === "enrollment") {
        return "Additional Fees - Enrolled Trainees";
    } else if (paymentType.value === "assessment") {
        return "Assessment Fees";
    }
    return "Payments";
});

const pageDescription = computed(() => {
    if (paymentType.value === "registration") {
        return "Enrollment fees from newly registered trainees awaiting payment to officially enroll in programs.";
    } else if (paymentType.value === "enrollment") {
        return "Additional fees for enrolled trainees including Trainee ID, Certification, and other supplementary charges.";
    } else if (paymentType.value === "assessment") {
        return "Assessment fees for trainees and external applicants taking program assessments.";
    }
    return "";
});

const exportReport = () => {
    // TODO: Implement export functionality
};

const recordPayment = () => {
    // TODO: Implement payment recording
};

const viewPayment = (paymentId) => {
    // Search in current payments (already filtered by server)
    const payment = currentPayments.value.find((p) => p.id === paymentId);

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
    // Search in current payments (already filtered by server)
    const payment = currentPayments.value.find((p) => p.id === paymentId);

    if (!payment) return;

    const paymentType = payment.type;

    // For registration payments (New Trainees), mark as paid first and then automatically show receipt modal
    if (paymentType === "registration") {
        // Prepare payment data for registration
        const paymentData = {
            payment_method: "cash",
            payment_reference: `RN-${Date.now()}`,
            payment_notes: "Payment processed via cashier interface",
            trainee_id: payment.trainee_id,
            skip_enrollment: true, // Flag to indicate we'll enroll after receipt generation
        };

        // Call the backend to process the payment
        router.post(route("cashier.payments.process"), paymentData, {
            onSuccess: () => {
                // Close payment details modal
                closePaymentDetails();

                // Update the payment status locally
                payment.status = "paid";

                // Automatically show receipt modal for registration payments
                generateReceiptForPaidPayment(payment);
            },
            onError: (errors) => {
                console.error("Payment processing failed:", errors);
                notifications.error(
                    "Failed to process payment. Please try again."
                );
            },
        });
    } else {
        // For other payment types (enrollment/assessment), use existing flow
        const paymentData = {
            payment_method: "cash",
            payment_reference: `RN-${Date.now()}`,
            payment_notes: "Payment processed via cashier interface",
        };

        // Add the appropriate ID based on payment type
        if (paymentType === "enrollment") {
            paymentData.enrollment_id = payment.enrollment_id;
        } else if (paymentType === "assessment") {
            paymentData.assessment_id = payment.assessment_id;
        }

        // Call the backend to process the payment
        router.post(route("cashier.payments.process"), paymentData, {
            onSuccess: () => {
                // Close payment details modal
                closePaymentDetails();

                // Update the payment status locally
                payment.status = "paid";

                // Automatically show receipt modal for enrollment payments too
                generateReceiptForPaidPayment(payment);
            },
            onError: (errors) => {
                console.error("Payment processing failed:", errors);
                notifications.error(
                    "Failed to process payment. Please try again."
                );
            },
        });
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

// Receipt modal state
const showReceiptModal = ref(false);
const selectedReceiptData = ref(null);
const editableReceiptData = ref({
    receiptNo: "",
    fundType: "General Fund", // Default to General Fund
    trainee: {
        name: "",
        uli_number: "",
    },
    dateGenerated: "",
    fees: [
        {
            natureOfCollection: "",
            program: "",
            accountCode: "EDU-001",
            amount: "",
        },
    ],
});

// Receipt modal validation errors
const receiptModalErrors = ref({
    amount: false,
});
// task: make another type of Receipt "regular/trust" fund
const generateReceipt = (paymentId) => {
    // Search in all payment arrays
    let payment = registrationPayments.value.find((p) => p.id === paymentId);
    if (!payment) {
        payment = enrollmentPaymentsList.value.find((p) => p.id === paymentId);
    }
    if (!payment) {
        payment = assessmentPayments.value.find((p) => p.id === paymentId);
    }

    if (payment && payment.status === "paid") {
        generateReceiptForPaidPayment(payment);
    } else {
        notifications.warning(
            "Receipt can only be generated for paid payments."
        );
    }
};

const generateReceiptForPaidPayment = (payment) => {
    // Prepare editable receipt data
    editableReceiptData.value = {
        receiptNo: "",
        fundType: "General Fund", // Default to General Fund
        trainee: {
            name: payment.trainee.name,
            uli_number: payment.trainee.uli_number || "Not assigned",
            id: payment.trainee.trainee_id || payment.trainee.id,
        },
        dateGenerated: new Date().toLocaleDateString("en-CA"),
        fees: [
            {
                natureOfCollection:
                    payment.type === "enrollment"
                        ? "Enrollment Fee"
                        : payment.type === "assessment"
                        ? "Assessment Fee"
                        : "Enrollment Fee",
                program: payment.program,
                accountCode: "EDU-001",
                amount: "", // Empty by default - user must manually enter amount
            },
        ],
    };

    selectedReceiptData.value = payment;
    showReceiptModal.value = true;
    closePaymentDetails();
};

const closeReceiptModal = (isSuccessfulSave = false) => {
    // Only show cancel warning if it's NOT a successful save and receipt number assigned
    if (!isSuccessfulSave && editableReceiptData.value.receiptNo) {
        const confirmCancel = confirm(
            "A receipt number has been assigned to this transaction. " +
                "If you cancel now, this receipt will be marked as 'CANCELLED' for audit purposes " +
                "and a new receipt number will be generated if you try again.\n\n" +
                "Are you sure you want to cancel this receipt?"
        );

        if (!confirmCancel) {
            return; // Don't close modal, let user continue
        }

        // Save the cancelled receipt for audit trail
        saveCancelledReceipt();
    }

    showReceiptModal.value = false;
    selectedReceiptData.value = null;
    editableReceiptData.value = {};
};

// Function to simply close the modal without any confirmation (for X button)
const closeReceiptModalDirectly = () => {
    showReceiptModal.value = false;
    selectedReceiptData.value = null;
    editableReceiptData.value = {};
};

const saveCancelledReceipt = () => {
    // Extract trainee ID from the correct location based on payment type
    let traineeId = selectedReceiptData.value.trainee_id;

    // If trainee_id is not available at top level, try other locations
    if (!traineeId) {
        if (selectedReceiptData.value.type === "enrollment") {
            traineeId = selectedReceiptData.value.trainee.trainee_id;
        } else if (selectedReceiptData.value.type === "assessment") {
            traineeId =
                selectedReceiptData.value.trainee.database_id ||
                selectedReceiptData.value.trainee.trainee_id;
        }
    }

    // Prepare cancelled receipt data
    const cancelledReceiptData = {
        receiptNo: editableReceiptData.value.receiptNo,
        paymentId: selectedReceiptData.value.id,
        type: selectedReceiptData.value.type,
        fund_type: editableReceiptData.value.fundType,
        trainee: {
            name: editableReceiptData.value.trainee.name,
            id: String(traineeId),
            uli_number: editableReceiptData.value.trainee.uli_number,
        },
        fees: editableReceiptData.value.fees,
        dateGenerated: editableReceiptData.value.dateGenerated,
        enrollment_id: selectedReceiptData.value.enrollment_id,
        assessment_id: selectedReceiptData.value.assessment_id,
        trainee_id: selectedReceiptData.value.trainee_id,
        status: "cancelled",
        cancellation_reason: "Cancelled by cashier",
    };

    // Save the cancelled receipt
    router.post(route("cashier.receipts.save"), cancelledReceiptData, {
        onSuccess: () => {
            notifications.success(
                `Receipt ${cancelledReceiptData.receiptNo} has been cancelled and saved for audit purposes.`
            );
        },
        onError: (errors) => {
            notifications.handleValidationErrors(
                errors,
                "Error saving cancelled receipt:"
            );
        },
    });
};

const addFee = () => {
    editableReceiptData.value.fees.push({
        natureOfCollection: "",
        program: "",
        accountCode: "EDU-001",
        amount: 0,
    });
};

const removeFee = (index) => {
    if (editableReceiptData.value.fees.length > 1) {
        editableReceiptData.value.fees.splice(index, 1);
    }
};

const getTotalAmount = () => {
    return editableReceiptData.value.fees.reduce((total, fee) => {
        return total + (parseFloat(fee.amount) || 0);
    }, 0);
};

const saveReceipt = () => {
    const totalAmount = getTotalAmount();

    // Validate required fields
    let hasErrors = false;
    receiptModalErrors.value.amount = false;

    // Check if all fees have amounts
    editableReceiptData.value.fees.forEach((fee, index) => {
        if (
            !fee.amount ||
            fee.amount === "" ||
            fee.amount === "0" ||
            fee.amount === 0
        ) {
            receiptModalErrors.value.amount = true;
            hasErrors = true;
        }
    });

    // If there are validation errors, show error and return
    if (hasErrors) {
        notifications.error(
            "Please fill in all required fields, including the Amount for each fee."
        );
        return;
    }

    // Check if this is a registration payment that needs enrollment after receipt generation
    const isRegistrationPayment =
        selectedReceiptData.value.type === "registration";

    // Prepare the data to send to the backend
    const receiptData = {
        receiptNo: editableReceiptData.value.receiptNo,
        paymentId: selectedReceiptData.value.id,
        type: selectedReceiptData.value.type,
        fund_type: editableReceiptData.value.fundType,
        trainee: {
            name: editableReceiptData.value.trainee.name,
            id: selectedReceiptData.value.trainee.id,
            uli_number: editableReceiptData.value.trainee.uli_number,
        },
        fees: editableReceiptData.value.fees,
        dateGenerated: editableReceiptData.value.dateGenerated,
        enrollment_id: selectedReceiptData.value.enrollment_id,
        assessment_id: selectedReceiptData.value.assessment_id,
        trainee_id: selectedReceiptData.value.trainee_id,
        complete_enrollment: isRegistrationPayment, // Flag to trigger enrollment after receipt generation
    };

    // Use Inertia router for proper CSRF handling
    router.post(route("cashier.receipts.save"), receiptData, {
        onSuccess: (page) => {
            // Show appropriate success message
            if (isRegistrationPayment) {
                notifications.success(
                    `Receipt ${receiptData.receiptNo} has been saved successfully! The trainee has been officially enrolled and will now appear in the Additional Fees - Enrolled Trainees tab.`
                );
            } else {
                notifications.success(
                    `Receipt ${receiptData.receiptNo} has been saved successfully and is now available in the Receipts page!`
                );
            }

            // Close the modal after successful save
            closeReceiptModal(true);

            // Refresh the page to show updated data
            window.location.reload();
        },
        onError: (errors) => {
            console.error("Error saving receipt:", errors);
            notifications.handleValidationErrors(
                errors,
                "Error saving receipt:"
            );
        },
    });
};

const formatAmount = (amount) => {
    const num = parseFloat(amount);
    return isNaN(num) ? "0.00" : num.toFixed(2);
};

const convertNumberToWords = (num) => {
    const ones = [
        "",
        "one",
        "two",
        "three",
        "four",
        "five",
        "six",
        "seven",
        "eight",
        "nine",
    ];
    const teens = [
        "ten",
        "eleven",
        "twelve",
        "thirteen",
        "fourteen",
        "fifteen",
        "sixteen",
        "seventeen",
        "eighteen",
        "nineteen",
    ];
    const tens = [
        "",
        "",
        "twenty",
        "thirty",
        "forty",
        "fifty",
        "sixty",
        "seventy",
        "eighty",
        "ninety",
    ];
    const thousands = ["", "thousand", "million", "billion"];

    if (num === 0) return "zero";

    function convertGroup(n) {
        let result = "";
        if (n >= 100) {
            result += ones[Math.floor(n / 100)] + " hundred ";
            n %= 100;
        }
        if (n >= 20) {
            result += tens[Math.floor(n / 10)] + " ";
            n %= 10;
        } else if (n >= 10) {
            result += teens[n - 10] + " ";
            return result;
        }
        if (n > 0) {
            result += ones[n] + " ";
        }
        return result;
    }

    let result = "";
    let groupIndex = 0;

    while (num > 0) {
        const group = num % 1000;
        if (group !== 0) {
            result = convertGroup(group) + thousands[groupIndex] + " " + result;
        }
        num = Math.floor(num / 1000);
        groupIndex++;
    }

    // Handle decimal part
    const parts = num.toString().split(".");
    if (parts[1]) {
        result += "and " + parts[1] + "/100";
    }

    return result.trim() + " pesos only";
};

// Debounced search function
let searchTimeout;
const performSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        // Determine the correct route based on payment type
        let routeName = "cashier.payments.enrollment";
        if (paymentType.value === "enrollment") {
            routeName = "cashier.payments.additional";
        } else if (paymentType.value === "assessment") {
            routeName = "cashier.payments.assessment";
        }

        router.visit(route(routeName), {
            data: {
                search: searchQuery.value,
                per_page: props.filters?.per_page || 20,
            },
            preserveState: true,
            replace: true,
        });
    }, 300);
};

// Watch for search changes
watch(searchQuery, () => {
    performSearch();
});
</script>

<template>
    <Head title="Payments" />
    <AuthenticatedLayout>
        <div class="py-8 px-8 bg-gray-50 min-h-screen">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8 animate-fade-in">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        {{ pageTitle }}
                    </h1>
                    <p v-if="pageDescription" class="text-gray-600 mt-1">
                        {{ pageDescription }}
                    </p>
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
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="mb-6 animate-fade-in">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-1">
                        <label for="search" class="sr-only"
                            >Search payments</label
                        >
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
                                    />
                                </svg>
                            </div>
                            <input
                                id="search"
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by trainee name, ULI number, or payment ID..."
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                            />
                        </div>
                    </div>
                </div>

                <!-- Results Count -->
                <div class="mt-4 text-sm text-gray-600">
                    <span v-if="props.enrollmentPayments?.total">
                        Showing
                        {{
                            (props.enrollmentPayments.current_page - 1) *
                                props.enrollmentPayments.per_page +
                            1
                        }}
                        to
                        {{
                            Math.min(
                                props.enrollmentPayments.current_page *
                                    props.enrollmentPayments.per_page,
                                props.enrollmentPayments.total
                            )
                        }}
                        of {{ props.enrollmentPayments.total }} results
                    </span>
                    <span v-else>
                        {{ allPayments.length }} payments found
                    </span>
                </div>
            </div>

            <!-- Payment Summary Content -->
            <div v-if="false" class="space-y-6 animate-fade-in">
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
                        </div>
                    </div>
                </div>

                <!-- Collections by Program Table -->
                <div
                    class="bg-white rounded-xl shadow-sm border border-gray-100"
                >
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Collections by Program
                        </h2>
                    </div>
                    <div
                        v-if="collectionsByProgram.length === 0"
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
                            No program data available
                        </h3>
                        <p class="text-gray-500">
                            Program collection information will appear here when
                            data is available.
                        </p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Program
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Total Payments
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
                                    v-for="program in collectionsByProgram"
                                    :key="program.program"
                                    class="hover:bg-gray-50"
                                >
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                    >
                                        {{ program.program }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ program.totalTrainees }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ program.fullyPaid }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ program.partiallyPaid }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ program.unpaid }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                    >
                                        {{
                                            formatCurrency(
                                                program.collectionAmount
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
                        <div>
                            <h2
                                class="text-lg font-semibold text-gray-900 flex items-center"
                            >
                                <svg
                                    v-if="paymentType === 'registration'"
                                    class="w-5 h-5 mr-2 text-orange-600"
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
                                <svg
                                    v-else-if="paymentType === 'enrollment'"
                                    class="w-5 h-5 mr-2 text-blue-600"
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
                                <svg
                                    v-else-if="paymentType === 'assessment'"
                                    class="w-5 h-5 mr-2 text-purple-600"
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
                                {{ pageTitle }}
                            </h2>
                            <p
                                v-if="pageDescription"
                                :class="[
                                    'text-sm mt-1',
                                    paymentType === 'registration'
                                        ? 'text-orange-600'
                                        : paymentType === 'enrollment'
                                        ? 'text-blue-600'
                                        : 'text-purple-600',
                                ]"
                            >
                                {{ pageDescription }}
                            </p>
                        </div>
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
                                    Program
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
                                    {{ payment.program }}
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
                                                : payment.status ===
                                                  'paid_pending_enrollment'
                                                ? 'bg-yellow-100 text-yellow-800'
                                                : 'bg-red-100 text-red-800',
                                        ]"
                                    >
                                        {{
                                            payment.status === "paid"
                                                ? "Paid"
                                                : payment.status ===
                                                  "paid_pending_enrollment"
                                                ? "Paid - Pending Receipt"
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
                                            class="text-blue-600 hover:text-blue-900 p-2 rounded hover:bg-blue-50"
                                            title="View Details"
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
                                            v-if="payment.status === 'unpaid'"
                                            @click="markAsPaid(payment.id)"
                                            class="text-blue-600 hover:text-blue-900 p-2 rounded hover:bg-blue-50"
                                            title="Mark as Paid"
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
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                                ></path>
                                            </svg>
                                        </button>
                                        <button
                                            v-if="
                                                payment.status ===
                                                'paid_pending_enrollment'
                                            "
                                            @click="
                                                generateReceiptForPaidPayment(
                                                    payment
                                                )
                                            "
                                            class="text-orange-600 hover:text-orange-900 p-2 rounded hover:bg-orange-50"
                                            title="Complete Enrollment - Generate Receipt"
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
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                                ></path>
                                            </svg>
                                        </button>
                                        <button
                                            @click="generateReceipt(payment.id)"
                                            class="text-green-600 hover:text-green-900 p-2 rounded hover:bg-green-50"
                                            title="Generate New Receipt"
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
                        No
                        {{
                            paymentType === "registration"
                                ? "registration"
                                : paymentType === "enrollment"
                                ? "enrollment"
                                : "assessment"
                        }}
                        payments found
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        <span v-if="searchQuery">
                            Try adjusting your search terms.
                        </span>
                        <span v-else-if="allPayments.length > 0">
                            No
                            {{
                                paymentType === "registration"
                                    ? "registration"
                                    : paymentType === "enrollment"
                                    ? "enrollment"
                                    : "assessment"
                            }}
                            payments on this page. Try navigating to other
                            pages.
                        </span>
                        <span v-else>
                            Get started by recording a new payment.
                        </span>
                    </p>
                </div>

                <!-- Pagination -->
                <div v-if="allPayments.length > 0" class="mt-6">
                    <Pagination :data="props.enrollmentPayments" />
                </div>
            </div>

            <!-- Payment Details Modal -->
            <div
                v-if="showPaymentDetails"
                class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
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
                                                        : selectedPayment.status ===
                                                          'paid_pending_enrollment'
                                                        ? 'bg-yellow-100 text-yellow-800'
                                                        : 'bg-red-100 text-red-800',
                                                ]"
                                            >
                                                {{
                                                    selectedPayment.status ===
                                                    "paid"
                                                        ? "Paid"
                                                        : selectedPayment.status ===
                                                          "paid_pending_enrollment"
                                                        ? "Paid - Pending Receipt"
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
                                                >Program:</span
                                            >
                                            <span
                                                class="text-sm font-medium text-gray-900"
                                                >{{
                                                    selectedPayment.program
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
                                    v-if="
                                        selectedPayment.status ===
                                        'paid_pending_enrollment'
                                    "
                                    @click="
                                        generateReceiptForPaidPayment(
                                            selectedPayment
                                        )
                                    "
                                    class="px-4 py-2 bg-orange-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
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
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        ></path>
                                    </svg>
                                    Complete Enrollment
                                </button>
                                <button
                                    @click="generateReceipt(selectedPayment.id)"
                                    class="px-4 py-2 bg-green-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                    title="Generate a new receipt (multiple receipts allowed)"
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
                                    Generate New Receipt
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Editable Receipt Modal -->
            <div
                v-if="showReceiptModal"
                class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
            >
                <div
                    class="relative top-4 md:top-10 mx-auto p-4 md:p-5 border w-11/12 md:w-4/5 lg:w-3/4 xl:w-2/3 max-h-[90vh] overflow-y-auto shadow-lg rounded-md bg-white"
                    @click.stop
                >
                    <!-- Modal Header -->
                    <div
                        class="flex items-center justify-between pb-4 border-b border-gray-200"
                    >
                        <h3 class="text-lg font-semibold text-gray-900">
                            Generate New Receipt
                        </h3>
                        <button
                            @click="closeReceiptModalDirectly"
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

                    <div class="mt-6">
                        <!-- Information Banner -->
                        <div
                            class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg"
                        >
                            <div class="flex items-start">
                                <svg
                                    class="w-5 h-5 text-blue-600 mt-0.5 mr-3"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                    ></path>
                                </svg>
                                <div>
                                    <h4
                                        class="text-sm font-medium text-blue-800"
                                    >
                                        Multiple Receipt Generation
                                    </h4>
                                    <p class="text-sm text-blue-700 mt-1">
                                        Each time you save a receipt, a new
                                        receipt will be created. Previous
                                        receipts will remain unchanged and can
                                        be viewed in the Receipts page.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Left Side: Editable Form -->
                            <div class="space-y-6">
                                <h4 class="text-md font-semibold text-gray-900">
                                    Receipt Information
                                </h4>

                                <div class="space-y-4">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700"
                                            >Receipt No.</label
                                        >
                                        <input
                                            v-model="
                                                editableReceiptData.receiptNo
                                            "
                                            type="text"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>

                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700"
                                            >Date Generated</label
                                        >
                                        <input
                                            v-model="
                                                editableReceiptData.dateGenerated
                                            "
                                            type="date"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>

                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700"
                                            >Fund Type</label
                                        >
                                        <select
                                            v-model="
                                                editableReceiptData.fundType
                                            "
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        >
                                            <option value="General Fund">
                                                General Fund
                                            </option>
                                            <option value="Trust Fund">
                                                Trust Fund
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700"
                                            >Trainee Name</label
                                        >
                                        <input
                                            v-model="
                                                editableReceiptData.trainee.name
                                            "
                                            type="text"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>

                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700"
                                            >ULI Number</label
                                        >
                                        <input
                                            v-model="
                                                editableReceiptData.trainee
                                                    .uli_number
                                            "
                                            type="text"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>
                                </div>

                                <!-- Fees Section -->
                                <div class="space-y-4">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <h5
                                            class="text-md font-semibold text-gray-900"
                                        >
                                            Fees
                                        </h5>
                                        <button
                                            @click="addFee"
                                            type="button"
                                            class="inline-flex items-center px-3 py-1 border border-transparent text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
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
                                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                                ></path>
                                            </svg>
                                            Add Fee
                                        </button>
                                    </div>

                                    <div
                                        v-for="(
                                            fee, index
                                        ) in editableReceiptData.fees"
                                        :key="index"
                                        class="p-4 border border-gray-200 rounded-lg space-y-3"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <h6
                                                class="text-sm font-medium text-gray-700"
                                            >
                                                Fee {{ index + 1 }}
                                            </h6>
                                            <button
                                                v-if="
                                                    editableReceiptData.fees
                                                        .length > 1
                                                "
                                                @click="removeFee(index)"
                                                type="button"
                                                class="text-red-600 hover:text-red-800"
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
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                    ></path>
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 gap-3">
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700"
                                                    >Nature of Collection</label
                                                >
                                                <input
                                                    v-model="
                                                        fee.natureOfCollection
                                                    "
                                                    type="text"
                                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                                />
                                            </div>

                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700"
                                                    >Program</label
                                                >
                                                <input
                                                    v-model="fee.program"
                                                    type="text"
                                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                                />
                                            </div>

                                            <div class="grid grid-cols-2 gap-3">
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-gray-700"
                                                        >Account Code</label
                                                    >
                                                    <input
                                                        v-model="
                                                            fee.accountCode
                                                        "
                                                        type="text"
                                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                                    />
                                                </div>

                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-gray-700"
                                                        >Amount
                                                        <span
                                                            class="text-red-500"
                                                            >*</span
                                                        ></label
                                                    >
                                                    <input
                                                        v-model="fee.amount"
                                                        type="number"
                                                        step="0.01"
                                                        required
                                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                                        :class="{
                                                            'border-red-500':
                                                                !fee.amount &&
                                                                receiptModalErrors.amount,
                                                        }"
                                                    />
                                                    <p
                                                        v-if="
                                                            !fee.amount &&
                                                            receiptModalErrors.amount
                                                        "
                                                        class="mt-1 text-sm text-red-600"
                                                    >
                                                        Amount is required
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Total Amount Display -->
                                    <div class="p-3 bg-gray-50 rounded-lg">
                                        <div
                                            class="flex justify-between items-center"
                                        >
                                            <span
                                                class="text-sm font-medium text-gray-700"
                                                >Total Amount:</span
                                            >
                                            <span
                                                class="text-lg font-bold text-blue-600"
                                                >₱{{
                                                    formatAmount(
                                                        getTotalAmount()
                                                    )
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Side: Receipt Preview -->
                            <div class="space-y-4">
                                <h4 class="text-md font-semibold text-gray-900">
                                    Receipt Preview
                                </h4>

                                <!-- Receipt Preview -->
                                <div
                                    class="bg-white border-2 border-gray-800 font-mono text-xs leading-tight"
                                >
                                    <!-- Header Section -->
                                    <div
                                        class="border-b-2 border-gray-800 p-4 bg-gray-50 text-center"
                                    >
                                        <div
                                            class="flex justify-between items-center mb-4"
                                        >
                                            <div
                                                class="w-16 h-16 border-2 border-gray-600 flex items-center justify-center text-sm rounded-full overflow-hidden"
                                                style="
                                                    background-image: url('/images/Philippine_logo.png');
                                                    background-size: cover;
                                                    background-repeat: no-repeat;
                                                    background-position: center;
                                                "
                                            ></div>
                                            <div class="flex-1">
                                                <h3 class="font-bold text-lg">
                                                    OFFICIAL RECEIPT
                                                </h3>
                                                <p class="text-sm">
                                                    Republic of the Philippines
                                                </p>
                                                <h4 class="font-bold text-base">
                                                    CITY OF SURIGAO
                                                </h4>
                                                <h5 class="font-bold text-sm">
                                                    LTPC TRAINING CENTER
                                                </h5>
                                                <p class="text-sm">
                                                    Office of the Cashier
                                                </p>
                                            </div>
                                            <div
                                                class="w-16 h-16 border-2 border-gray-600 rounded-full overflow-hidden"
                                                style="
                                                    background-image: url('/images/Surigao_logo.jpg');
                                                    background-size: cover;
                                                    background-repeat: no-repeat;
                                                    background-position: center;
                                                "
                                            ></div>
                                        </div>
                                    </div>

                                    <!-- Form Info -->
                                    <div class="flex border-b border-gray-800">
                                        <div
                                            class="border-r border-gray-800 p-2 w-2/5"
                                        >
                                            <strong
                                                >Accountable Form No. 51</strong
                                            ><br />
                                            <small>(Revised June 2008)</small>
                                        </div>
                                        <div class="p-2 w-3/5 text-center">
                                            <h4 class="font-bold">ORIGINAL</h4>
                                            <h3 class="font-bold text-red-600">
                                                {{
                                                    editableReceiptData.receiptNo.padStart(
                                                        7,
                                                        "0"
                                                    )
                                                }}
                                            </h3>
                                        </div>
                                    </div>

                                    <!-- Date -->
                                    <div class="flex border-b border-gray-800">
                                        <div
                                            class="border-r border-gray-800 p-2 w-2/5 font-bold"
                                        >
                                            DATE
                                        </div>
                                        <div class="p-2 w-3/5">
                                            {{
                                                editableReceiptData.dateGenerated
                                            }}
                                        </div>
                                    </div>

                                    <!-- Payor -->
                                    <div class="flex border-b border-gray-800">
                                        <div
                                            class="border-r border-gray-800 p-2 w-3/4"
                                        >
                                            <strong>PAYOR</strong><br />
                                            {{ editableReceiptData.trainee.name
                                            }}<br />
                                            <small
                                                >ULI:
                                                {{
                                                    editableReceiptData.trainee
                                                        .uli_number
                                                }}</small
                                            >
                                        </div>
                                        <div class="p-2 w-1/4 text-center">
                                            <strong>FUND</strong>
                                        </div>
                                    </div>

                                    <!-- Table Header -->
                                    <div
                                        class="flex border-b-2 border-gray-800 bg-gray-100"
                                    >
                                        <div
                                            class="border-r border-gray-800 p-2 w-2/5 text-center font-bold"
                                        >
                                            NATURE OF COLLECTION
                                        </div>
                                        <div
                                            class="border-r border-gray-800 p-2 w-1/5 text-center font-bold"
                                        >
                                            ACCOUNT CODE
                                        </div>
                                        <div
                                            class="p-2 w-2/5 text-center font-bold"
                                        >
                                            AMOUNT
                                        </div>
                                    </div>

                                    <!-- Fee Entries -->
                                    <div
                                        v-for="(
                                            fee, index
                                        ) in editableReceiptData.fees"
                                        :key="index"
                                        class="flex border-b border-gray-800"
                                    >
                                        <div
                                            class="border-r border-gray-800 p-2 w-2/5"
                                        >
                                            {{ fee.natureOfCollection }}<br />
                                            <small>{{ fee.program }}</small>
                                        </div>
                                        <div
                                            class="border-r border-gray-800 p-2 w-1/5 text-center"
                                        >
                                            {{ fee.accountCode }}
                                        </div>
                                        <div
                                            class="p-2 w-2/5 text-right font-bold"
                                        >
                                            ₱ {{ formatAmount(fee.amount) }}
                                        </div>
                                    </div>

                                    <!-- Empty Rows (only show if there are less than 4 fees) -->
                                    <div
                                        v-for="i in Math.max(
                                            0,
                                            4 - editableReceiptData.fees.length
                                        )"
                                        :key="'empty-' + i"
                                        class="flex border-b border-gray-800"
                                    >
                                        <div
                                            class="border-r border-gray-800 p-2 w-2/5 h-6"
                                        ></div>
                                        <div
                                            class="border-r border-gray-800 p-2 w-1/5 h-6"
                                        ></div>
                                        <div class="p-2 w-2/5 h-6"></div>
                                    </div>

                                    <!-- Total -->
                                    <div
                                        class="flex border-b-2 border-gray-800 bg-gray-100 font-bold"
                                    >
                                        <div
                                            class="border-r border-gray-800 p-2 w-2/5 text-center"
                                        >
                                            TOTAL
                                        </div>
                                        <div
                                            class="border-r border-gray-800 p-2 w-1/5"
                                        ></div>
                                        <div class="p-2 w-2/5 text-right">
                                            ₱
                                            {{ formatAmount(getTotalAmount()) }}
                                        </div>
                                    </div>

                                    <!-- Amount in Words -->
                                    <div class="border-b-2 border-gray-800 p-2">
                                        <strong>AMOUNT IN WORDS</strong><br />
                                        <strong>{{
                                            convertNumberToWords(
                                                getTotalAmount()
                                            ).toUpperCase()
                                        }}</strong>
                                    </div>

                                    <!-- Payment Method -->
                                    <div class="flex border-b border-gray-800">
                                        <div
                                            class="border-r border-gray-800 p-2 w-1/3"
                                        >
                                            ☑ Cash<br />
                                            ☐ Check<br />
                                            ☐ Money Order
                                        </div>
                                        <div class="p-2 w-2/3">
                                            <div
                                                class="flex border-b border-gray-600 text-xs font-bold"
                                            >
                                                <div
                                                    class="border-r border-gray-600 p-1 w-1/3"
                                                >
                                                    DRAWEE BANK
                                                </div>
                                                <div
                                                    class="border-r border-gray-600 p-1 w-1/3"
                                                >
                                                    NUMBER
                                                </div>
                                                <div class="p-1 w-1/3">
                                                    DATE
                                                </div>
                                            </div>
                                            <div class="flex">
                                                <div
                                                    class="border-r border-gray-600 p-1 w-1/3 h-6"
                                                ></div>
                                                <div
                                                    class="border-r border-gray-600 p-1 w-1/3 h-6"
                                                ></div>
                                                <div
                                                    class="p-1 w-1/3 h-6"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Footer -->
                                    <div class="border-b border-gray-800 p-2">
                                        Received the amount stated above
                                    </div>

                                    <!-- Signature -->
                                    <div class="p-4 text-center">
                                        <div class="float-right mr-8">
                                            <div
                                                class="border-b border-gray-800 w-32 mb-1"
                                            ></div>
                                            <div class="text-xs">
                                                Collecting Officer
                                            </div>
                                        </div>
                                        <div class="clear-both"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div
                        class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200 mt-6"
                    >
                        <button
                            @click="closeReceiptModal(false)"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Cancel Receipt
                        </button>
                        <button
                            @click="saveReceipt"
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
                                    d="M5 13l4 4L19 7"
                                ></path>
                            </svg>
                            Generate New Receipt
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
