<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Dropdown from "@/Components/Dropdown.vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import jsPDF from "jspdf";
import ExcelJS from "exceljs";
import { saveAs } from "file-saver";

const props = defineProps({
    groupedReceipts: Array,
    flash: Object,
});

const searchQuery = ref("");
const expandedTrainees = ref(new Set());

// Filter states
const selectedProgram = ref("");
const selectedStatus = ref("");
const dateFrom = ref("");
const dateTo = ref("");
const amountFrom = ref("");
const amountTo = ref("");
const showFilters = ref(false);

// Get unique programs and statuses for filter dropdowns
const availablePrograms = computed(() => {
    const programs = new Set();
    props.groupedReceipts?.forEach((trainee) => {
        if (trainee.program && trainee.program !== "Unknown Program") {
            programs.add(trainee.program);
        }
    });
    return Array.from(programs).sort();
});

const availableStatuses = computed(() => {
    const statuses = new Set();
    props.groupedReceipts?.forEach((trainee) => {
        trainee.receipts.forEach((receipt) => {
            statuses.add(receipt.status);
        });
    });
    return Array.from(statuses).sort();
});

const filteredTrainees = computed(() => {
    let filtered = props.groupedReceipts || [];

    // Search filter
    if (searchQuery.value) {
        filtered = filtered.filter((trainee) => {
            return (
                trainee.trainee_name
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                trainee.uli_number
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                trainee.receipts.some(
                    (receipt) =>
                        receipt.reference_number
                            ?.toLowerCase()
                            .includes(searchQuery.value.toLowerCase()) ||
                        receipt.description
                            ?.toLowerCase()
                            .includes(searchQuery.value.toLowerCase())
                )
            );
        });
    }

    // Program filter
    if (selectedProgram.value) {
        filtered = filtered.filter(
            (trainee) => trainee.program === selectedProgram.value
        );
    }

    // Status filter
    if (selectedStatus.value) {
        filtered = filtered.filter((trainee) =>
            trainee.receipts.some(
                (receipt) => receipt.status === selectedStatus.value
            )
        );
    }

    // Date range filter
    if (dateFrom.value || dateTo.value) {
        filtered = filtered.filter((trainee) =>
            trainee.receipts.some((receipt) => {
                const receiptDate = new Date(receipt.payment_date);
                const fromDate = dateFrom.value
                    ? new Date(dateFrom.value)
                    : null;
                const toDate = dateTo.value ? new Date(dateTo.value) : null;

                if (fromDate && toDate) {
                    return receiptDate >= fromDate && receiptDate <= toDate;
                } else if (fromDate) {
                    return receiptDate >= fromDate;
                } else if (toDate) {
                    return receiptDate <= toDate;
                }
                return true;
            })
        );
    }

    // Amount range filter
    if (amountFrom.value || amountTo.value) {
        filtered = filtered.filter((trainee) =>
            trainee.receipts.some((receipt) => {
                const amount = parseFloat(receipt.amount);
                const fromAmount = amountFrom.value
                    ? parseFloat(amountFrom.value)
                    : null;
                const toAmount = amountTo.value
                    ? parseFloat(amountTo.value)
                    : null;

                if (fromAmount && toAmount) {
                    return amount >= fromAmount && amount <= toAmount;
                } else if (fromAmount) {
                    return amount >= fromAmount;
                } else if (toAmount) {
                    return amount <= toAmount;
                }
                return true;
            })
        );
    }

    return filtered;
});

const clearFilters = () => {
    searchQuery.value = "";
    selectedProgram.value = "";
    selectedStatus.value = "";
    dateFrom.value = "";
    dateTo.value = "";
    amountFrom.value = "";
    amountTo.value = "";
};

const hasActiveFilters = computed(() => {
    return (
        searchQuery.value ||
        selectedProgram.value ||
        selectedStatus.value ||
        dateFrom.value ||
        dateTo.value ||
        amountFrom.value ||
        amountTo.value
    );
});

// Summary statistics
const totalFilteredReceipts = computed(() => {
    return filteredTrainees.value.reduce((total, trainee) => {
        return total + trainee.receipts.length;
    }, 0);
});

const totalFilteredAmount = computed(() => {
    return filteredTrainees.value.reduce((total, trainee) => {
        return total + trainee.total_amount;
    }, 0);
});

const totalAllReceipts = computed(() => {
    return (
        props.groupedReceipts?.reduce((total, trainee) => {
            return total + trainee.receipts.length;
        }, 0) || 0
    );
});

const toggleTraineeExpansion = (traineeId) => {
    if (expandedTrainees.value.has(traineeId)) {
        expandedTrainees.value.delete(traineeId);
    } else {
        expandedTrainees.value.add(traineeId);
    }
};

const getStatusColor = (status) => {
    const colors = {
        pending: "bg-yellow-100 text-yellow-800",
        paid: "bg-green-100 text-green-800",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};

const getPaymentMethodIcon = (method) => {
    const icons = {
        cash: "💵",
        card: "💳",
        bank_transfer: "🏦",
        online: "💻",
        check: "📋",
    };
    return icons[method] || "💰";
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(amount);
};

const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("en-PH");
};

const exportToPDF = () => {
    const doc = new jsPDF();

    // Add title
    doc.setFontSize(18);
    doc.text("Payments Report", 14, 22);

    // Add subtitle with date range if filters are applied
    doc.setFontSize(12);
    let subtitle = "All Payments";
    if (dateFrom.value || dateTo.value) {
        subtitle = `Payments from ${dateFrom.value || "beginning"} to ${
            dateTo.value || "present"
        }`;
    }
    doc.text(subtitle, 14, 32);

    // Add summary statistics
    doc.setFontSize(10);
    doc.text(`Total Trainees: ${filteredTrainees.value.length}`, 14, 42);
    doc.text(`Total Receipts: ${totalFilteredReceipts.value}`, 14, 50);
    doc.text(
        `Total Amount: ${formatCurrency(totalFilteredAmount.value)}`,
        14,
        58
    );

    // Create a simple table without autotable
    let yPosition = 75;
    const lineHeight = 8;
    const pageHeight = 280;
    let currentPage = 1;

    // Add headers
    doc.setFontSize(8);
    doc.setFillColor(34, 139, 34);
    doc.rect(14, yPosition - 5, 180, 8, "F");
    doc.setTextColor(255, 255, 255);
    doc.text("Trainee", 16, yPosition);
    doc.text("Program", 60, yPosition);
    doc.text("Receipts", 100, yPosition);
    doc.text("Total Amount", 130, yPosition);
    doc.text("Latest Payment", 160, yPosition);

    yPosition += 10;
    doc.setTextColor(0, 0, 0);

    // Add data rows
    filteredTrainees.value.forEach((trainee, index) => {
        // Check if we need a new page
        if (yPosition > pageHeight) {
            doc.addPage();
            currentPage++;
            yPosition = 20;
        }

        const traineeName = (trainee.trainee_name || "N/A").substring(0, 25);
        const program = (trainee.program || "N/A").substring(0, 20);
        const receipts = trainee.receipts.length;
        const totalAmount = formatCurrency(trainee.total_amount);
        const latestPayment =
            trainee.receipts.length > 0
                ? formatDate(trainee.receipts[0].payment_date)
                : "N/A";

        doc.text(traineeName, 16, yPosition);
        doc.text(program, 60, yPosition);
        doc.text(receipts.toString(), 100, yPosition);
        doc.text(totalAmount, 130, yPosition);
        doc.text(latestPayment, 160, yPosition);

        yPosition += lineHeight;
    });

    // Add page numbers
    for (let i = 1; i <= currentPage; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.text(`Page ${i} of ${currentPage}`, 14, pageHeight + 10);
    }

    // Generate filename
    const timestamp = new Date().toISOString().split("T")[0];
    const filename = `payments_report_${timestamp}.pdf`;

    // Save the PDF
    doc.save(filename);
};

const exportToExcel = async () => {
    // Create a new workbook
    const workbook = new ExcelJS.Workbook();
    const worksheet = workbook.addWorksheet("Payments");

    // Add summary information at the top
    worksheet.addRow(["Report Date:", new Date().toLocaleDateString()]);
    worksheet.addRow([""]);
    worksheet.addRow(["Summary Statistics:"]);
    worksheet.addRow(["Total Trainees:", filteredTrainees.value.length]);
    worksheet.addRow(["Total Receipts:", totalFilteredReceipts.value]);
    worksheet.addRow(["Total Amount:", totalFilteredAmount.value]);
    worksheet.addRow([""]);

    // Add headers
    const headers = [
        "Trainee Name",
        "ULI Number",
        "Program",
        "Total Receipts",
        "Total Amount",
        "Latest Payment Date",
        "Payment Status",
    ];
    worksheet.addRow(headers);

    // Style header row
    const headerRow = worksheet.getRow(8);
    headerRow.fill = {
        type: "pattern",
        pattern: "solid",
        fgColor: { argb: "FF228B22" },
    };
    headerRow.font = { bold: true, color: { argb: "FFFFFFFF" } };
    headerRow.alignment = { vertical: "middle", horizontal: "center" };

    // Add data rows
    filteredTrainees.value.forEach((trainee) => {
        worksheet.addRow([
            trainee.trainee_name || "N/A",
            trainee.uli_number || "N/A",
            trainee.program || "N/A",
            trainee.receipts.length,
            trainee.total_amount,
            trainee.receipts.length > 0
                ? formatDate(trainee.receipts[0].payment_date)
                : "N/A",
            trainee.receipts.some((r) => r.status === "paid")
                ? "Paid"
                : "Pending",
        ]);
    });

    // Adjust column widths
    worksheet.getColumn(1).width = 25; // Trainee Name
    worksheet.getColumn(2).width = 15; // ULI Number
    worksheet.getColumn(3).width = 25; // Program
    worksheet.getColumn(4).width = 15; // Total Receipts
    worksheet.getColumn(5).width = 15; // Total Amount
    worksheet.getColumn(6).width = 20; // Latest Payment Date
    worksheet.getColumn(7).width = 15; // Payment Status

    // Generate filename
    const timestamp = new Date().toISOString().split("T")[0];
    const filename = `payments_report_${timestamp}.xlsx`;

    // Write to buffer and save
    const buffer = await workbook.xlsx.writeBuffer();
    const blob = new Blob([buffer], {
        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    });
    saveAs(blob, filename);
};

const exportPaymentReport = () => {
    // Default to PDF export for backward compatibility
    exportToPDF();
};
</script>

<template>
    <Head title="Payments Management" />
    <AuthenticatedLayout>
        <div class="py-8 px-8 bg-gray-50 min-h-screen">
            <div
                class="bg-white rounded-xl shadow-sm overflow-hidden animate-fade-in border border-gray-100"
            >
                <!-- Header Section -->
                <div
                    class="p-6 border-b border-gray-200 bg-gradient-to-r from-green-50 to-emerald-50 relative"
                >
                    <div
                        class="absolute bottom-0 left-0 w-full h-0.5 bg-gradient-to-r from-green-500 to-emerald-500"
                    ></div>
                    <div
                        class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                    >
                        <div>
                            <h3
                                class="text-lg font-semibold text-green-900 relative pb-2 after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-20 after:h-0.5 after:bg-gradient-to-r after:rounded"
                            >
                                Payment Status Monitoring
                            </h3>
                            <p class="text-sm text-green-700 mt-1">
                                Centralized payment tracking by trainee
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Filters Section -->
                <div class="p-6 border-b border-gray-200 bg-white">
                    <div class="flex flex-col gap-4">
                        <!-- Search and Filter Toggle -->
                        <div class="flex flex-col md:flex-row gap-4">
                            <div class="flex-1">
                                <InputLabel
                                    for="search"
                                    value="Search"
                                    class="text-sm font-medium text-gray-700 mb-2"
                                />
                                <TextInput
                                    id="search"
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search by trainee name, ULI number, or payment details..."
                                    class="w-full"
                                />
                            </div>
                            <div class="flex items-end gap-2">
                                <SecondaryButton
                                    @click="showFilters = !showFilters"
                                    class="flex items-center gap-2"
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
                                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"
                                        />
                                    </svg>
                                    {{ showFilters ? "Hide" : "Show" }} Filters
                                </SecondaryButton>
                                <SecondaryButton
                                    v-if="hasActiveFilters"
                                    @click="clearFilters"
                                    class="flex items-center gap-2 text-red-600 hover:text-red-700"
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
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                    Clear Filters
                                </SecondaryButton>
                            </div>
                        </div>

                        <!-- Advanced Filters -->
                        <div
                            v-if="showFilters"
                            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pt-4 border-t border-gray-200"
                        >
                            <!-- Program Filter -->
                            <div>
                                <InputLabel
                                    for="program"
                                    value="Program"
                                    class="text-sm font-medium text-gray-700 mb-2"
                                />
                                <select
                                    id="program"
                                    v-model="selectedProgram"
                                    class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <option value="">All Programs</option>
                                    <option
                                        v-for="program in availablePrograms"
                                        :key="program"
                                        :value="program"
                                    >
                                        {{ program }}
                                    </option>
                                </select>
                            </div>

                            <!-- Status Filter -->
                            <div>
                                <InputLabel
                                    for="status"
                                    value="Status"
                                    class="text-sm font-medium text-gray-700 mb-2"
                                />
                                <select
                                    id="status"
                                    v-model="selectedStatus"
                                    class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <option value="">All Statuses</option>
                                    <option
                                        v-for="status in availableStatuses"
                                        :key="status"
                                        :value="status"
                                    >
                                        {{
                                            status.charAt(0).toUpperCase() +
                                            status.slice(1)
                                        }}
                                    </option>
                                </select>
                            </div>

                            <!-- Date Range Filter -->
                            <div>
                                <InputLabel
                                    for="dateFrom"
                                    value="Date From"
                                    class="text-sm font-medium text-gray-700 mb-2"
                                />
                                <TextInput
                                    id="dateFrom"
                                    v-model="dateFrom"
                                    type="date"
                                    class="w-full"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    for="dateTo"
                                    value="Date To"
                                    class="text-sm font-medium text-gray-700 mb-2"
                                />
                                <TextInput
                                    id="dateTo"
                                    v-model="dateTo"
                                    type="date"
                                    class="w-full"
                                />
                            </div>

                            <!-- Amount Range Filter -->
                            <div>
                                <InputLabel
                                    for="amountFrom"
                                    value="Amount From"
                                    class="text-sm font-medium text-gray-700 mb-2"
                                />
                                <TextInput
                                    id="amountFrom"
                                    v-model="amountFrom"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="₱0.00"
                                    class="w-full"
                                />
                            </div>

                            <div>
                                <InputLabel
                                    for="amountTo"
                                    value="Amount To"
                                    class="text-sm font-medium text-gray-700 mb-2"
                                />
                                <TextInput
                                    id="amountTo"
                                    v-model="amountTo"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="₱0.00"
                                    class="w-full"
                                />
                            </div>
                        </div>

                        <!-- Active Filters Summary -->
                        <div
                            v-if="hasActiveFilters"
                            class="flex flex-wrap gap-2 pt-2"
                        >
                            <span class="text-sm text-gray-600"
                                >Active filters:</span
                            >
                            <span
                                v-if="searchQuery"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                            >
                                Search: "{{ searchQuery }}"
                            </span>
                            <span
                                v-if="selectedProgram"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                            >
                                Program: {{ selectedProgram }}
                            </span>
                            <span
                                v-if="selectedStatus"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                            >
                                Status: {{ selectedStatus }}
                            </span>
                            <span
                                v-if="dateFrom || dateTo"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800"
                            >
                                Date: {{ dateFrom || "Any" }} -
                                {{ dateTo || "Any" }}
                            </span>
                            <span
                                v-if="amountFrom || amountTo"
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800"
                            >
                                Amount:
                                {{ amountFrom ? "₱" + amountFrom : "₱0" }} -
                                {{ amountTo ? "₱" + amountTo : "Any" }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Results Summary -->
                <div
                    v-if="hasActiveFilters"
                    class="p-4 border-b border-gray-200 bg-gray-50"
                >
                    <div
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                    >
                        <div class="flex items-center gap-6">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-900">
                                    {{ filteredTrainees.length }}
                                </p>
                                <p class="text-sm text-gray-600">Trainees</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-blue-600">
                                    {{ totalFilteredReceipts }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    Total Receipts
                                </p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-green-600">
                                    {{ formatCurrency(totalFilteredAmount) }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    Total Amount
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <p class="text-sm text-gray-600">
                                    Showing {{ filteredTrainees.length }} of
                                    {{ props.groupedReceipts?.length || 0 }}
                                    trainees
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ totalFilteredReceipts }} of
                                    {{ totalAllReceipts }} receipts
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <button
                                    @click="exportToPDF"
                                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md transition-colors duration-200 flex items-center gap-2"
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
                                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        />
                                    </svg>
                                    Export PDF
                                </button>
                                <button
                                    @click="exportToExcel"
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition-colors duration-200 flex items-center gap-2"
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
                                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        />
                                    </svg>
                                    Export Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trainees List -->
                <div class="overflow-hidden">
                    <div
                        v-for="trainee in filteredTrainees"
                        :key="trainee.trainee_id"
                        class="border-b border-gray-200 last:border-b-0"
                    >
                        <!-- Trainee Summary Row -->
                        <div
                            class="p-6 hover:bg-gray-50 cursor-pointer transition-colors duration-200"
                            @click="toggleTraineeExpansion(trainee.trainee_id)"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <!-- Trainee Avatar -->
                                    <div class="flex-shrink-0 h-12 w-12">
                                        <div
                                            class="h-12 w-12 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-medium relative overflow-hidden"
                                        >
                                            <span class="relative z-10">
                                                {{
                                                    trainee.trainee_name?.charAt(
                                                        0
                                                    )
                                                }}
                                                {{
                                                    trainee.trainee_name
                                                        ?.split(" ")[1]
                                                        ?.charAt(0) || ""
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                    <!-- Trainee Info -->
                                    <div>
                                        <h4
                                            class="text-lg font-semibold text-gray-900"
                                        >
                                            {{ trainee.trainee_name }}
                                        </h4>
                                        <p class="text-sm text-gray-500">
                                            ULI:
                                            {{ trainee.uli_number || "N/A" }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ trainee.program }}
                                        </p>
                                    </div>
                                </div>
                                <!-- Payment Summary -->
                                <div class="flex items-center space-x-6">
                                    <div class="text-right">
                                        <p class="text-sm text-gray-500">
                                            Total Receipts
                                        </p>
                                        <p
                                            class="text-lg font-semibold text-gray-900"
                                        >
                                            {{ trainee.total_receipts }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-gray-500">
                                            Total Amount
                                        </p>
                                        <p
                                            class="text-lg font-semibold text-green-600"
                                        >
                                            {{
                                                formatCurrency(
                                                    trainee.total_amount
                                                )
                                            }}
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <svg
                                            :class="[
                                                'w-5 h-5 text-gray-400 transition-transform duration-200',
                                                expandedTrainees.has(
                                                    trainee.trainee_id
                                                )
                                                    ? 'rotate-180'
                                                    : '',
                                            ]"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 9l-7 7-7-7"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Expanded Receipts Details -->
                        <div
                            v-if="expandedTrainees.has(trainee.trainee_id)"
                            class="bg-gray-50 border-t border-gray-200"
                        >
                            <div class="p-6">
                                <h5
                                    class="text-md font-semibold text-gray-900 mb-4"
                                >
                                    Receipt History
                                </h5>
                                <!-- Receipts Table -->
                                <div class="overflow-x-auto">
                                    <table
                                        class="min-w-full divide-y divide-gray-200"
                                    >
                                        <thead class="bg-gray-100">
                                            <tr>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                >
                                                    Reference
                                                </th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                >
                                                    Details
                                                </th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                >
                                                    Program
                                                </th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                >
                                                    Amount
                                                </th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                >
                                                    Method
                                                </th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                >
                                                    Status
                                                </th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                                >
                                                    Date
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody
                                            class="bg-white divide-y divide-gray-200"
                                        >
                                            <tr
                                                v-for="receipt in trainee.receipts"
                                                :key="receipt.id"
                                                class="hover:bg-gray-50 transition-colors duration-200"
                                            >
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap"
                                                >
                                                    <div
                                                        class="text-sm font-medium text-gray-900"
                                                    >
                                                        {{
                                                            receipt.reference_number
                                                        }}
                                                    </div>
                                                    <div
                                                        class="text-sm text-gray-500"
                                                    >
                                                        {{
                                                            receipt.description ||
                                                            "Receipt"
                                                        }}
                                                    </div>
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap"
                                                >
                                                    <div
                                                        class="text-sm font-medium text-gray-900"
                                                    >
                                                        {{
                                                            receipt.description ||
                                                            "Receipt"
                                                        }}
                                                    </div>
                                                    <div
                                                        class="text-sm text-gray-500"
                                                    >
                                                        {{ receipt.type }}
                                                    </div>
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap"
                                                >
                                                    <div
                                                        class="text-sm font-medium text-gray-900"
                                                    >
                                                        {{ receipt.program }}
                                                    </div>
                                                    <div
                                                        class="text-sm text-gray-500"
                                                    >
                                                        ID:
                                                        {{ receipt.program_id }}
                                                    </div>
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap"
                                                >
                                                    <div
                                                        class="text-sm font-medium text-gray-900"
                                                    >
                                                        {{
                                                            formatCurrency(
                                                                receipt.amount
                                                            )
                                                        }}
                                                    </div>
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap"
                                                >
                                                    <div
                                                        class="flex items-center"
                                                    >
                                                        <span
                                                            class="text-lg mr-2"
                                                        >
                                                            {{
                                                                getPaymentMethodIcon(
                                                                    receipt.payment_method
                                                                )
                                                            }}
                                                        </span>
                                                        <span
                                                            class="text-sm text-gray-900 capitalize"
                                                        >
                                                            {{
                                                                receipt.payment_method?.replace(
                                                                    "_",
                                                                    " "
                                                                )
                                                            }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap"
                                                >
                                                    <span
                                                        :class="
                                                            getStatusColor(
                                                                receipt.status
                                                            )
                                                        "
                                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full border"
                                                    >
                                                        {{ receipt.status }}
                                                    </span>
                                                </td>
                                                <td
                                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                                >
                                                    {{
                                                        new Date(
                                                            receipt.payment_date
                                                        ).toLocaleDateString()
                                                    }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Empty State -->
                    <div
                        v-if="filteredTrainees.length === 0"
                        class="p-8 text-center bg-gradient-to-br from-white to-green-50"
                    >
                        <div class="text-gray-500">
                            <svg
                                class="mx-auto h-12 w-12 text-gray-400 animate-bounce"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3zm0 0V4m0 7v7m0 0h4m-4 0H8"
                                />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">
                                No trainees found
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{
                                    searchQuery
                                        ? "Try adjusting your filters."
                                        : "No payment records available."
                                }}
                            </p>
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
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.6s ease-out;
}
</style>
