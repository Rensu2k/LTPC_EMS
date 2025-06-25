<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, onMounted, onUnmounted } from "vue";

// Define props to receive data from backend
const props = defineProps({
    enrollmentReceipts: {
        type: Array,
        default: () => [],
    },
    assessmentReceipts: {
        type: Array,
        default: () => [],
    },
});

// Convert props to reactive refs for template usage
const enrollmentReceipts = ref(props.enrollmentReceipts);
const assessmentReceipts = ref(props.assessmentReceipts);

// Active tab state - now separates by receipt type
const activeTab = ref("enrollments");

const searchQuery = ref("");

// Get current receipt data based on active tab
const currentReceipts = computed(() => {
    if (activeTab.value === "enrollments") {
        return enrollmentReceipts.value;
    } else if (activeTab.value === "assessments") {
        return assessmentReceipts.value;
    }
    return [];
});

// Computed filtered receipts based on search
const filteredReceipts = computed(() => {
    let filtered = currentReceipts.value;

    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(
            (receipt) =>
                receipt.id.toLowerCase().includes(query) ||
                receipt.trainee.name.toLowerCase().includes(query) ||
                receipt.course.toLowerCase().includes(query) ||
                receipt.trainee.id.toLowerCase().includes(query)
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

const safeNumber = (value) => {
    const num = parseFloat(value);
    return isNaN(num) ? 0 : num;
};

const formatAmount = (amount) => {
    return safeNumber(amount).toFixed(2);
};

// Receipt modal state
const showReceiptModal = ref(false);
const selectedReceipt = ref(null);

// Loading states for actions
const isDownloading = ref({});
const isPrinting = ref({});

// Success feedback
const showSuccessMessage = ref(false);
const successMessage = ref("");

const setActiveTab = (tab) => {
    activeTab.value = tab;
};

const viewReceipt = (receiptId) => {
    // Find the receipt in the current receipts
    const receipt = currentReceipts.value.find((r) => r.id === receiptId);
    if (receipt) {
        selectedReceipt.value = receipt;
        showReceiptModal.value = true;
    }
};

const closeReceiptModal = () => {
    showReceiptModal.value = false;
    selectedReceipt.value = null;
};

const downloadReceipt = (receiptId) => {
    const receipt = currentReceipts.value.find((r) => r.id === receiptId);
    if (!receipt) return;

    // Set loading state
    isDownloading.value[receiptId] = true;

    try {
        // Create receipt content
        const receiptContent = generateReceiptContent(receipt);

        // Create and download file
        const blob = new Blob([receiptContent], { type: "text/plain" });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = `Receipt_${receiptId}.txt`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);

        // Show success message
        showSuccessFeedback("Receipt downloaded successfully!");
    } catch (error) {
        console.error("Download error:", error);
        showSuccessFeedback("Error downloading receipt", true);
    } finally {
        // Clear loading state
        setTimeout(() => {
            isDownloading.value[receiptId] = false;
        }, 500);
    }
};

const printReceipt = (receiptId) => {
    const receipt = currentReceipts.value.find((r) => r.id === receiptId);
    if (!receipt) return;

    // Set loading state
    isPrinting.value[receiptId] = true;

    try {
        // Create a new window for printing
        const printWindow = window.open("", "_blank");
        const receiptHTML = generateReceiptHTML(receipt);

        printWindow.document.write(receiptHTML);
        printWindow.document.close();
        printWindow.print();

        // Show success message
        showSuccessFeedback("Receipt sent to printer!");
    } catch (error) {
        console.error("Print error:", error);
        showSuccessFeedback("Error printing receipt", true);
    } finally {
        // Clear loading state
        setTimeout(() => {
            isPrinting.value[receiptId] = false;
        }, 500);
    }
};

const showSuccessFeedback = (message, isError = false) => {
    successMessage.value = message;
    showSuccessMessage.value = true;

    // Auto-hide after 3 seconds
    setTimeout(() => {
        showSuccessMessage.value = false;
        successMessage.value = "";
    }, 3000);
};

const convertNumberToWords = (amount) => {
    const numAmount = safeNumber(amount);
    const ones = [
        "",
        "One",
        "Two",
        "Three",
        "Four",
        "Five",
        "Six",
        "Seven",
        "Eight",
        "Nine",
    ];
    const teens = [
        "Ten",
        "Eleven",
        "Twelve",
        "Thirteen",
        "Fourteen",
        "Fifteen",
        "Sixteen",
        "Seventeen",
        "Eighteen",
        "Nineteen",
    ];
    const tens = [
        "",
        "",
        "Twenty",
        "Thirty",
        "Forty",
        "Fifty",
        "Sixty",
        "Seventy",
        "Eighty",
        "Ninety",
    ];

    const convertGroup = (num) => {
        let result = "";

        if (num >= 100) {
            result += ones[Math.floor(num / 100)] + " Hundred ";
            num %= 100;
        }

        if (num >= 20) {
            result += tens[Math.floor(num / 10)] + " ";
            num %= 10;
        } else if (num >= 10) {
            result += teens[num - 10] + " ";
            return result;
        }

        if (num > 0) {
            result += ones[num] + " ";
        }

        return result;
    };

    if (numAmount === 0) return "Zero Pesos Only";

    let pesos = Math.floor(numAmount);
    const centavos = Math.round((numAmount - pesos) * 100);

    let words = "";

    if (pesos >= 1000000) {
        words += convertGroup(Math.floor(pesos / 1000000)) + "Million ";
        pesos %= 1000000;
    }

    if (pesos >= 1000) {
        words += convertGroup(Math.floor(pesos / 1000)) + "Thousand ";
        pesos %= 1000;
    }

    if (pesos > 0) {
        words += convertGroup(pesos);
    }

    words += pesos === 1 ? "Peso" : "Pesos";

    if (centavos > 0) {
        words +=
            " and " +
            convertGroup(centavos) +
            (centavos === 1 ? "Centavo" : "Centavos");
    }

    return words.trim() + " Only";
};

const generateReceiptContent = (receipt) => {
    const amountInWords = convertNumberToWords(receipt.amount);

    return `
┌─────────────────────────────────────────────────────────────────────┐
│                        OFFICIAL RECEIPT                             │
│                    Republic of the Philippines                      │
│                        CITY OF SURIGAO                              │
│                       LTPC TRAINING CENTER                          │
│                      Office of the Cashier                          │
└─────────────────────────────────────────────────────────────────────┘

┌──────────────────────────┬─────────────────────────────────────────┐
│ Accountable Form No. 51  │              ORIGINAL                   │
│ (Revised June 2008)      │              ${receipt.id.padStart(
        7,
        "0"
    )}              │
├──────────────────────────┼─────────────────────────────────────────┤
│ DATE                     │ ${receipt.dateGenerated}                │
├──────────────────────────┴──────────────┬──────────────────────────┤
│ PAYOR                                    │ FUND                     │
│ ${receipt.trainee.name.padEnd(36)}  │                          │
│ ID: ${receipt.trainee.id.padEnd(31)}  │                          │
├─────────────────────────┬────────────────┬─────────────────────────┤
│ NATURE OF COLLECTION    │ ACCOUNT CODE   │ AMOUNT                  │
├─────────────────────────┼────────────────┼─────────────────────────┤
│ ${(receipt.type === "enrollment"
        ? "Enrollment Fee"
        : "Assessment Fee"
    ).padEnd(23)} │ EDU-001        │ ${formatCurrency(
        safeNumber(receipt.amount)
    ).padStart(23)} │
│ ${receipt.course
        .substring(0, 23)
        .padEnd(23)} │                │                         │
├─────────────────────────┼────────────────┼─────────────────────────┤
│                         │                │                         │
├─────────────────────────┼────────────────┼─────────────────────────┤
│                         │                │                         │
├─────────────────────────┼────────────────┼─────────────────────────┤
│                         │                │                         │
├─────────────────────────┼────────────────┼─────────────────────────┤
│ TOTAL                   │                │ ${formatCurrency(
        safeNumber(receipt.amount)
    ).padStart(23)} │
├─────────────────────────┴────────────────┴─────────────────────────┤
│ AMOUNT IN WORDS                                                     │
│ ${amountInWords.toUpperCase().padEnd(67)} │
└─────────────────────────────────────────────────────────────────────┘

┌──┬──────────┬──────────────┬────────────┬─────────────────────────┐
│☑ │ Cash     │ DRAWEE BANK  │ NUMBER     │ DATE                    │
├──┼──────────┼──────────────┼────────────┼─────────────────────────┤
│☐ │ Check    │              │            │                         │
├──┼──────────┼──────────────┼────────────┼─────────────────────────┤
│☐ │ Money    │              │            │                         │
│  │ Order    │              │            │                         │
└──┴──────────┴──────────────┴────────────┴─────────────────────────┘

Received the amount stated above


                                        _________________________
                                         Collecting Officer
    `;
};

const generateReceiptHTML = (receipt) => {
    const amountInWords = convertNumberToWords(receipt.amount);
    const currentDate = new Date();
    const formattedDate = currentDate.toLocaleDateString("en-PH");

    return `
<!DOCTYPE html>
<html>
<head>
    <title>Official Receipt ${receipt.id}</title>
    <style>
        body { 
            font-family: 'Courier New', monospace; 
            max-width: 600px; 
            margin: 0 auto; 
            padding: 20px; 
            line-height: 1.2;
            font-size: 12px;
        }
        .receipt-container {
            border: 2px solid #000;
            background: white;
        }
                 .header-section { 
             text-align: center; 
             border-bottom: 2px solid #000; 
             padding: 20px;
             background: #f8f9fa;
         }
         .header-logos {
             display: flex;
             justify-content: space-between;
             align-items: center;
             margin-bottom: 15px;
         }
                 .logo-placeholder {
             width: 120px;
             height: 120px;
             border: 2px solid #000;
             display: flex;
             align-items: center;
             justify-content: center;
             font-size: 12px;
             background-size: contain;
             background-repeat: no-repeat;
             background-position: center;
         }
         .phil-logo {
             background-image: url('/images/Philippine_logo.png');
         }
         .surigao-logo {
             background-image: url('/images/Surigao_logo.jpg');
         }
        .form-info {
            display: flex;
            border-bottom: 1px solid #000;
        }
        .form-left {
            border-right: 1px solid #000;
            padding: 8px;
            width: 40%;
        }
        .form-right {
            padding: 8px;
            width: 60%;
            text-align: center;
        }
        .date-section {
            display: flex;
            border-bottom: 1px solid #000;
        }
        .date-left {
            border-right: 1px solid #000;
            padding: 8px;
            width: 40%;
            font-weight: bold;
        }
        .date-right {
            padding: 8px;
            width: 60%;
        }
        .payor-section {
            display: flex;
            border-bottom: 1px solid #000;
        }
        .payor-left {
            border-right: 1px solid #000;
            padding: 8px;
            width: 70%;
        }
        .payor-right {
            padding: 8px;
            width: 30%;
            text-align: center;
            font-weight: bold;
        }
        .table-header {
            display: flex;
            border-bottom: 2px solid #000;
            background: #f0f0f0;
        }
        .col-nature {
            border-right: 1px solid #000;
            padding: 8px;
            width: 40%;
            font-weight: bold;
            text-align: center;
        }
        .col-account {
            border-right: 1px solid #000;
            padding: 8px;
            width: 20%;
            font-weight: bold;
            text-align: center;
        }
        .col-amount {
            padding: 8px;
            width: 40%;
            font-weight: bold;
            text-align: center;
        }
        .table-row {
            display: flex;
            border-bottom: 1px solid #000;
            min-height: 25px;
        }
        .total-row {
            display: flex;
            border-bottom: 2px solid #000;
            background: #f0f0f0;
            font-weight: bold;
        }
        .amount-words {
            border-bottom: 2px solid #000;
            padding: 8px;
        }
        .payment-method {
            display: flex;
            border-bottom: 1px solid #000;
        }
        .method-left {
            border-right: 1px solid #000;
            padding: 8px;
            width: 30%;
        }
        .method-right {
            padding: 8px;
            width: 70%;
        }
        .signature-section {
            padding: 20px 8px;
            text-align: center;
        }
        .checkbox {
            margin-right: 5px;
        }
        @media print {
            body { margin: 0; padding: 10px; }
            .receipt-container { border: 3px solid #000; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
                 <!-- Header -->
         <div class="header-section">
             <div class="header-logos">
                 <div class="logo-placeholder phil-logo"></div>
                 <div style="text-align: center; flex: 1;">
                     <h2 style="margin: 0; font-size: 20px; font-weight: bold;">OFFICIAL RECEIPT</h2>
                     <p style="margin: 5px 0; font-size: 14px;">Republic of the Philippines</p>
                     <h3 style="margin: 0; font-size: 16px; font-weight: bold;">CITY OF SURIGAO</h3>
                     <h4 style="margin: 0; font-size: 14px; font-weight: bold;">LTPC TRAINING CENTER</h4>
                     <p style="margin: 5px 0; font-size: 12px;">Office of the Cashier</p>
                 </div>
                 <div class="logo-placeholder surigao-logo"></div>
             </div>
         </div>
        
        <!-- Form Info -->
        <div class="form-info">
            <div class="form-left">
                <strong>Accountable Form No. 51</strong><br>
                <small>(Revised June 2008)</small>
            </div>
            <div class="form-right">
                <h3 style="margin: 0;">ORIGINAL</h3>
                <h2 style="margin: 5px 0; color: red;">${receipt.id.padStart(
                    7,
                    "0"
                )}</h2>
            </div>
        </div>
        
        <!-- Date -->
        <div class="date-section">
            <div class="date-left">DATE</div>
            <div class="date-right">${receipt.dateGenerated}</div>
        </div>
        
        <!-- Payor -->
        <div class="payor-section">
            <div class="payor-left">
                <strong>PAYOR</strong><br>
                ${receipt.trainee.name}<br>
                <small>ID: ${receipt.trainee.id}</small>
            </div>
            <div class="payor-right">
                <strong>FUND</strong>
            </div>
        </div>
        
        <!-- Table Header -->
        <div class="table-header">
            <div class="col-nature">NATURE OF COLLECTION</div>
            <div class="col-account">ACCOUNT CODE</div>
            <div class="col-amount">AMOUNT</div>
        </div>
        
        <!-- Main Entry -->
        <div class="table-row">
            <div class="col-nature">${
                receipt.type === "enrollment"
                    ? "Enrollment Fee"
                    : "Assessment Fee"
            }<br><small>${receipt.course}</small></div>
            <div class="col-account">EDU-001</div>
                         <div class="col-amount" style="text-align: right; font-weight: bold;">₱ ${formatAmount(
                             receipt.amount
                         )}</div>
        </div>
        
        <!-- Empty Rows -->
        <div class="table-row">
            <div class="col-nature"></div>
            <div class="col-account"></div>
            <div class="col-amount"></div>
        </div>
        <div class="table-row">
            <div class="col-nature"></div>
            <div class="col-account"></div>
            <div class="col-amount"></div>
        </div>
        <div class="table-row">
            <div class="col-nature"></div>
            <div class="col-account"></div>
            <div class="col-amount"></div>
        </div>
        
        <!-- Total -->
        <div class="total-row">
            <div class="col-nature" style="text-align: center;">TOTAL</div>
            <div class="col-account"></div>
                         <div class="col-amount" style="text-align: right;">₱ ${formatAmount(
                             receipt.amount
                         )}</div>
        </div>
        
        <!-- Amount in Words -->
        <div class="amount-words">
            <strong>AMOUNT IN WORDS</strong><br>
            <strong>${amountInWords.toUpperCase()}</strong>
        </div>
        
                 <!-- Payment Method -->
         <div class="payment-method">
             <div class="method-left">
                 <input type="checkbox" class="checkbox" checked> Cash<br>
                 <input type="checkbox" class="checkbox"> Check<br>
                 <input type="checkbox" class="checkbox"> Money Order
             </div>
            <div class="method-right">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid #000;">
                        <td style="border-right: 1px solid #000; padding: 4px;"><strong>DRAWEE BANK</strong></td>
                        <td style="border-right: 1px solid #000; padding: 4px;"><strong>NUMBER</strong></td>
                        <td style="padding: 4px;"><strong>DATE</strong></td>
                    </tr>
                    <tr>
                        <td style="border-right: 1px solid #000; padding: 8px; height: 20px;"></td>
                        <td style="border-right: 1px solid #000; padding: 8px;"></td>
                        <td style="padding: 8px;"></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Footer -->
        <div style="padding: 8px; border-bottom: 1px solid #000;">
            Received the amount stated above
        </div>
        
        <!-- Signature -->
        <div class="signature-section">
            <div style="float: right; margin-right: 50px;">
                <div style="border-bottom: 1px solid #000; width: 200px; margin-bottom: 5px;"></div>
                <div style="text-align: center;"><small>Collecting Officer</small></div>
            </div>
            <div style="clear: both;"></div>
        </div>
    </div>
</body>
</html>
    `;
};

// Function to add new receipt (called from payments page)
const addReceipt = (receiptData) => {
    // Determine which array to add to based on receipt type
    if (receiptData.type === "enrollment") {
        enrollmentReceipts.value.unshift(receiptData);
    } else if (receiptData.type === "assessment") {
        assessmentReceipts.value.unshift(receiptData);
    }
};

// Expose the addReceipt function globally for use from other pages
window.addReceiptToPage = addReceipt;

// Keyboard event handler
const handleKeydown = (event) => {
    if (event.key === "Escape" && showReceiptModal.value) {
        closeReceiptModal();
    }
};

// Set up keyboard listener
onMounted(() => {
    document.addEventListener("keydown", handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener("keydown", handleKeydown);
});
</script>

<template>
    <Head title="Receipts" />
    <AuthenticatedLayout>
        <div class="py-8 px-8 bg-gray-50 min-h-screen">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8 animate-fade-in">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        Receipt Management
                    </h1>
                    <p class="text-gray-600">
                        View and manage official receipts generated from
                        payments.
                    </p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="mb-6 animate-fade-in">
                <nav class="flex space-x-8">
                    <button
                        @click="setActiveTab('enrollments')"
                        :class="[
                            'py-2 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'enrollments'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                        ]"
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
                                d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C20.168 18.477 18.582 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                            ></path>
                        </svg>
                        Enrollment Receipts ({{ enrollmentReceipts.length }})
                    </button>
                    <button
                        @click="setActiveTab('assessments')"
                        :class="[
                            'py-2 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'assessments'
                                ? 'border-purple-500 text-purple-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                        ]"
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
                                d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                            ></path>
                        </svg>
                        Assessment Receipts ({{ assessmentReceipts.length }})
                    </button>
                </nav>
            </div>

            <!-- Receipts Table -->
            <div
                class="bg-white rounded-xl shadow-sm border border-gray-100 animate-fade-in"
            >
                <!-- Section Header -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">
                            <span
                                v-if="activeTab === 'enrollments'"
                                class="flex items-center"
                            >
                                <svg
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
                                Enrollment Receipt History
                            </span>
                            <span
                                v-else-if="activeTab === 'assessments'"
                                class="flex items-center"
                            >
                                <svg
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
                                Assessment Receipt History
                            </span>
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
                                    placeholder="Search receipts..."
                                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                />
                            </div>
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
                                    Receipt No.
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
                                    Date Generated
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Time
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
                                v-for="receipt in filteredReceipts"
                                :key="receipt.id"
                                class="hover:bg-gray-50"
                            >
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-blue-600"
                                >
                                    {{ receipt.id }}
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
                                                        receipt.trainee.name
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
                                                {{ receipt.trainee.name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ receipt.trainee.id }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    {{ receipt.course }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                >
                                    {{ formatCurrency(receipt.amount) }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    {{ receipt.dateGenerated }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    {{ receipt.timeGenerated }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                    >
                                        Generated
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium"
                                >
                                    <div class="flex items-center space-x-1">
                                        <button
                                            @click="viewReceipt(receipt.id)"
                                            class="inline-flex items-center justify-center w-8 h-8 text-blue-600 hover:text-blue-900 hover:bg-blue-50 rounded-full transition-colors"
                                            title="View Receipt"
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
                                            @click="downloadReceipt(receipt.id)"
                                            :disabled="
                                                isDownloading[receipt.id]
                                            "
                                            class="inline-flex items-center justify-center w-8 h-8 text-green-600 hover:text-green-900 hover:bg-green-50 rounded-full transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                            title="Download Receipt"
                                        >
                                            <svg
                                                v-if="
                                                    !isDownloading[receipt.id]
                                                "
                                                class="w-4 h-4"
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
                                            <svg
                                                v-else
                                                class="w-4 h-4 animate-spin"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                            >
                                                <circle
                                                    class="opacity-25"
                                                    cx="12"
                                                    cy="12"
                                                    r="10"
                                                    stroke="currentColor"
                                                    stroke-width="4"
                                                ></circle>
                                                <path
                                                    class="opacity-75"
                                                    fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                                ></path>
                                            </svg>
                                        </button>
                                        <button
                                            @click="printReceipt(receipt.id)"
                                            :disabled="isPrinting[receipt.id]"
                                            class="inline-flex items-center justify-center w-8 h-8 text-purple-600 hover:text-purple-900 hover:bg-purple-50 rounded-full transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                            title="Print Receipt"
                                        >
                                            <svg
                                                v-if="!isPrinting[receipt.id]"
                                                class="w-4 h-4"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                                ></path>
                                            </svg>
                                            <svg
                                                v-else
                                                class="w-4 h-4 animate-spin"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                            >
                                                <circle
                                                    class="opacity-25"
                                                    cx="12"
                                                    cy="12"
                                                    r="10"
                                                    stroke="currentColor"
                                                    stroke-width="4"
                                                ></circle>
                                                <path
                                                    class="opacity-75"
                                                    fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
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
                    v-if="filteredReceipts.length === 0"
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
                        No receipts found
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{
                            searchQuery
                                ? "Try adjusting your search terms."
                                : "Receipts will appear here when generated from payments."
                        }}
                    </p>
                </div>
            </div>

            <!-- Receipt View Modal -->
            <div
                v-if="showReceiptModal"
                class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
                @click="closeReceiptModal"
            >
                <div
                    class="relative top-4 md:top-10 mx-auto p-4 md:p-5 border w-11/12 md:w-3/4 lg:w-1/2 xl:w-1/3 max-h-[90vh] overflow-y-auto shadow-lg rounded-md bg-white"
                    @click.stop
                >
                    <!-- Modal Header -->
                    <div
                        class="flex items-center justify-between pb-4 border-b border-gray-200"
                    >
                        <h3 class="text-lg font-semibold text-gray-900">
                            Receipt Details
                        </h3>
                        <button
                            @click="closeReceiptModal"
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

                    <!-- Receipt Content -->
                    <div v-if="selectedReceipt" class="mt-6">
                        <!-- Receipt Preview -->
                        <div
                            class="bg-white border-2 border-gray-800 font-mono text-xs leading-tight max-w-lg mx-auto"
                        >
                            <!-- Header Section -->
                            <div
                                class="border-b-2 border-gray-800 p-4 bg-gray-50 text-center"
                            >
                                <div
                                    class="flex justify-between items-center mb-4"
                                >
                                    <div
                                        class="w-20 h-20 border-2 border-gray-600 flex items-center justify-center text-sm rounded-full overflow-hidden"
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
                                        class="w-20 h-20 border-2 border-gray-600 rounded-full overflow-hidden"
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
                                <div class="border-r border-gray-800 p-2 w-2/5">
                                    <strong>Accountable Form No. 51</strong
                                    ><br />
                                    <small>(Revised June 2008)</small>
                                </div>
                                <div class="p-2 w-3/5 text-center">
                                    <h4 class="font-bold">ORIGINAL</h4>
                                    <h3 class="font-bold text-red-600">
                                        {{
                                            selectedReceipt.id.padStart(7, "0")
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
                                    {{ selectedReceipt.dateGenerated }}
                                </div>
                            </div>

                            <!-- Payor -->
                            <div class="flex border-b border-gray-800">
                                <div class="border-r border-gray-800 p-2 w-3/4">
                                    <strong>PAYOR</strong><br />
                                    {{ selectedReceipt.trainee.name }}<br />
                                    <small
                                        >ID:
                                        {{ selectedReceipt.trainee.id }}</small
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
                                <div class="p-2 w-2/5 text-center font-bold">
                                    AMOUNT
                                </div>
                            </div>

                            <!-- Main Entry -->
                            <div class="flex border-b border-gray-800">
                                <div class="border-r border-gray-800 p-2 w-2/5">
                                    {{
                                        selectedReceipt.type === "enrollment"
                                            ? "Enrollment Fee"
                                            : "Assessment Fee"
                                    }}<br />
                                    <small>{{ selectedReceipt.course }}</small>
                                </div>
                                <div
                                    class="border-r border-gray-800 p-2 w-1/5 text-center"
                                >
                                    EDU-001
                                </div>
                                <div class="p-2 w-2/5 text-right font-bold">
                                    ₱ {{ formatAmount(selectedReceipt.amount) }}
                                </div>
                            </div>

                            <!-- Empty Rows -->
                            <div
                                class="flex border-b border-gray-800"
                                v-for="i in 3"
                                :key="i"
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
                                    ₱ {{ formatAmount(selectedReceipt.amount) }}
                                </div>
                            </div>

                            <!-- Amount in Words -->
                            <div class="border-b-2 border-gray-800 p-2">
                                <strong>AMOUNT IN WORDS</strong><br />
                                <strong>{{
                                    convertNumberToWords(
                                        selectedReceipt.amount
                                    ).toUpperCase()
                                }}</strong>
                            </div>

                            <!-- Payment Method -->
                            <div class="flex border-b border-gray-800">
                                <div class="border-r border-gray-800 p-2 w-1/3">
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
                                        <div class="p-1 w-1/3">DATE</div>
                                    </div>
                                    <div class="flex">
                                        <div
                                            class="border-r border-gray-600 p-1 w-1/3 h-6"
                                        ></div>
                                        <div
                                            class="border-r border-gray-600 p-1 w-1/3 h-6"
                                        ></div>
                                        <div class="p-1 w-1/3 h-6"></div>
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

                        <!-- Action Buttons -->
                        <div
                            class="flex items-center justify-end space-x-3 pt-6 border-t border-gray-200 mt-6"
                        >
                            <button
                                @click="closeReceiptModal"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Close
                            </button>
                            <button
                                @click="downloadReceipt(selectedReceipt.id)"
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
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    ></path>
                                </svg>
                                Download
                            </button>
                            <button
                                @click="printReceipt(selectedReceipt.id)"
                                class="px-4 py-2 bg-purple-600 border border-transparent rounded-lg text-sm font-medium text-white hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
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
                                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                    ></path>
                                </svg>
                                Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Success/Error Message -->
            <div
                v-if="showSuccessMessage"
                class="fixed bottom-4 right-4 z-50 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg shadow-lg animate-fade-in"
            >
                <div class="flex items-center">
                    <svg
                        class="w-5 h-5 mr-2"
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
                    {{ successMessage }}
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
