<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useNotifications } from "@/composables/useNotifications";
import ExcelJS from "exceljs";
import { saveAs } from "file-saver";

// Define props to receive data from backend
const notifications = useNotifications();

const props = defineProps({
    groupedReceipts: {
        type: Array,
        default: () => [],
    },
    cancelledReceipts: {
        type: Array,
        default: () => [],
    },
    // Keep old props for backward compatibility
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
const groupedReceipts = ref(props.groupedReceipts);
const cancelledReceipts = ref(props.cancelledReceipts);

// Active tab state
const activeTab = ref("generated");

const searchQuery = ref("");

// Filter state
const dateFrom = ref("");
const dateTo = ref("");
const fundTypeFilter = ref("all"); // 'all', 'General Fund', 'Trust Fund'

// Track which trainee sections are expanded
const expandedTrainees = ref(new Set());

// Toggle trainee section expansion
const toggleTraineeExpansion = (traineeId) => {
    if (expandedTrainees.value.has(traineeId)) {
        expandedTrainees.value.delete(traineeId);
    } else {
        expandedTrainees.value.add(traineeId);
    }
};

// Check if trainee section is expanded
const isTraineeExpanded = (traineeId) => {
    return expandedTrainees.value.has(traineeId);
};

// Tab switching function
const setActiveTab = (tab) => {
    activeTab.value = tab;
};

// Clear all filters
const clearFilters = () => {
    dateFrom.value = "";
    dateTo.value = "";
    fundTypeFilter.value = "all";
    searchQuery.value = "";
};

// Computed filtered receipts based on search, date, and fund type
const filteredGroupedReceipts = computed(() => {
    let filtered = groupedReceipts.value;

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter((group) => {
            // Search in trainee info
            const traineeMatch =
                group.trainee_name.toLowerCase().includes(query) ||
                group.trainee_id_number.toLowerCase().includes(query) ||
                (group.trainee_uli_number &&
                    group.trainee_uli_number.toLowerCase().includes(query));

            // Search in receipts
            const receiptMatch = group.receipts.some(
                (receipt) =>
                    receipt.id.toLowerCase().includes(query) ||
                    receipt.program.toLowerCase().includes(query) ||
                    receipt.type.toLowerCase().includes(query)
            );

            return traineeMatch || receiptMatch;
        });
    }

    // Filter by date range and fund type for receipts within each group
    filtered = filtered
        .map((group) => {
            let filteredReceipts = [...group.receipts];

            // Filter by date range
            if (dateFrom.value || dateTo.value) {
                filteredReceipts = filteredReceipts.filter((receipt) => {
                    const receiptDate = new Date(receipt.dateGenerated);
                    const fromDate = dateFrom.value
                        ? new Date(dateFrom.value)
                        : null;
                    const toDate = dateTo.value ? new Date(dateTo.value) : null;

                    if (fromDate && receiptDate < fromDate) return false;
                    if (toDate && receiptDate > toDate) return false;
                    return true;
                });
            }

            // Filter by fund type
            if (fundTypeFilter.value !== "all") {
                filteredReceipts = filteredReceipts.filter(
                    (receipt) =>
                        (receipt.fund_type || "General Fund") ===
                        fundTypeFilter.value
                );
            }

            return {
                ...group,
                receipts: filteredReceipts,
                total_receipts: filteredReceipts.length,
                total_amount: filteredReceipts.reduce(
                    (sum, r) => sum + Number(r.amount || 0),
                    0
                ),
            };
        })
        .filter((group) => group.receipts.length > 0); // Remove groups with no matching receipts

    return filtered;
});

// Computed filtered cancelled receipts based on search, date, and fund type
const filteredCancelledReceipts = computed(() => {
    let filtered = cancelledReceipts.value;

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter((receipt) => {
            return (
                receipt.id.toLowerCase().includes(query) ||
                receipt.trainee.name.toLowerCase().includes(query) ||
                receipt.trainee.id.toLowerCase().includes(query) ||
                receipt.program.toLowerCase().includes(query) ||
                (receipt.cancellation_reason &&
                    receipt.cancellation_reason.toLowerCase().includes(query))
            );
        });
    }

    // Filter by date range
    if (dateFrom.value || dateTo.value) {
        filtered = filtered.filter((receipt) => {
            const receiptDate = new Date(receipt.dateGenerated);
            const fromDate = dateFrom.value ? new Date(dateFrom.value) : null;
            const toDate = dateTo.value ? new Date(dateTo.value) : null;

            if (fromDate && receiptDate < fromDate) return false;
            if (toDate && receiptDate > toDate) return false;
            return true;
        });
    }

    // Filter by fund type
    if (fundTypeFilter.value !== "all") {
        filtered = filtered.filter(
            (receipt) =>
                (receipt.fund_type || "General Fund") === fundTypeFilter.value
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

// Remove success feedback state - using notifications now

const viewReceipt = (receipt) => {
    selectedReceipt.value = receipt;
    showReceiptModal.value = true;
};

const closeReceiptModal = () => {
    showReceiptModal.value = false;
    selectedReceipt.value = null;
};

const downloadReceipt = (receipt) => {
    if (!receipt) return;

    // Set loading state
    isDownloading.value[receipt.id] = true;

    try {
        // Create receipt content
        const receiptContent = generateReceiptContent(receipt);

        // Create and download file
        const blob = new Blob([receiptContent], { type: "text/plain" });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = `Receipt_${receipt.id}.txt`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);

        // Show success message
        notifications.success("Receipt downloaded successfully!");
    } catch (error) {
        console.error("Download error:", error);
        notifications.error("Error downloading receipt");
    } finally {
        // Clear loading state
        setTimeout(() => {
            isDownloading.value[receipt.id] = false;
        }, 500);
    }
};

const printReceipt = (receipt) => {
    if (!receipt) return;

    // Set loading state
    isPrinting.value[receipt.id] = true;

    try {
        // Create a new window for printing
        const printWindow = window.open("", "_blank");
        const receiptHTML = generateReceiptHTML(receipt);

        printWindow.document.write(receiptHTML);
        printWindow.document.close();
        printWindow.print();

        // Show success message
        notifications.success("Receipt sent to printer!");
    } catch (error) {
        console.error("Print error:", error);
        notifications.error("Error printing receipt");
    } finally {
        // Clear loading state
        setTimeout(() => {
            isPrinting.value[receipt.id] = false;
        }, 500);
    }
};

// showSuccessFeedback function removed - using notifications system now

const exportToExcel = async () => {
    try {
        // Create a new workbook
        const workbook = new ExcelJS.Workbook();
        const worksheet = workbook.addWorksheet("Receipts");

        // Add title
        worksheet.mergeCells("A1:H1");
        const titleCell = worksheet.getCell("A1");
        titleCell.value = "LTPC Receipts Report";
        titleCell.font = { size: 16, bold: true };
        titleCell.alignment = { horizontal: "center", vertical: "middle" };
        titleCell.fill = {
            type: "pattern",
            pattern: "solid",
            fgColor: { argb: "FF4F7942" },
        };
        titleCell.font = { ...titleCell.font, color: { argb: "FFFFFFFF" } };
        worksheet.getRow(1).height = 30;

        // Add date and filter info
        worksheet.mergeCells("A2:H2");
        const dateCell = worksheet.getCell("A2");
        let filterText = `Generated on: ${new Date().toLocaleString("en-PH")}`;

        // Add date range filter info
        if (dateFrom.value || dateTo.value) {
            const fromText = dateFrom.value || "beginning";
            const toText = dateTo.value || "present";
            filterText += ` | Date Range: ${fromText} to ${toText}`;
        }

        // Add fund type filter info
        if (fundTypeFilter.value !== "all") {
            filterText += ` | Fund Type: ${fundTypeFilter.value}`;
        }

        dateCell.value = filterText;
        dateCell.alignment = { horizontal: "center" };
        dateCell.font = { size: 10, italic: true };
        worksheet.getRow(2).height = 20;

        // Add spacing
        worksheet.addRow([]);

        // Add headers
        const headers = [
            "Receipt No.",
            "Trainee Name",
            "ULI Number",
            "Trainee ID",
            "Program",
            "Fund Type",
            "Amount",
            "Date Generated",
        ];
        worksheet.addRow(headers);
        const headerRow = worksheet.getRow(4);
        headerRow.font = { bold: true };
        headerRow.fill = {
            type: "pattern",
            pattern: "solid",
            fgColor: { argb: "FF4F7942" },
        };
        headerRow.eachCell((cell) => {
            cell.font = { ...cell.font, color: { argb: "FFFFFFFF" } };
            cell.border = {
                top: { style: "thin" },
                left: { style: "thin" },
                bottom: { style: "thin" },
                right: { style: "thin" },
            };
            cell.alignment = { horizontal: "center", vertical: "middle" };
        });

        // Add data rows from grouped receipts
        let totalAmount = 0;
        filteredGroupedReceipts.value.forEach((group) => {
            group.receipts.forEach((receipt) => {
                worksheet.addRow([
                    receipt.id,
                    receipt.trainee.name,
                    receipt.trainee.uli_number || "N/A",
                    receipt.trainee.id,
                    receipt.program || "N/A",
                    receipt.fund_type || "General Fund",
                    formatCurrency(receipt.amount),
                    receipt.dateGenerated,
                ]);
                totalAmount += Number(receipt.amount || 0);
            });
        });

        // Style data rows
        const lastRow = worksheet.lastRow.number;
        for (let i = 5; i <= lastRow; i++) {
            const row = worksheet.getRow(i);
            row.eachCell((cell) => {
                cell.border = {
                    top: { style: "thin" },
                    left: { style: "thin" },
                    bottom: { style: "thin" },
                    right: { style: "thin" },
                };
            });
            // Alternate row colors
            if ((i - 5) % 2 === 0) {
                row.fill = {
                    type: "pattern",
                    pattern: "solid",
                    fgColor: { argb: "FFF9F9F9" },
                };
            }
        }

        // Add totals row
        const totalRow = worksheet.addRow([
            "",
            "",
            "",
            "",
            "",
            "TOTAL",
            formatCurrency(totalAmount),
            "",
        ]);
        totalRow.font = { bold: true };
        totalRow.fill = {
            type: "pattern",
            pattern: "solid",
            fgColor: { argb: "FFD0E0FF" },
        };
        totalRow.eachCell((cell) => {
            cell.border = {
                top: { style: "double" },
                left: { style: "thin" },
                bottom: { style: "double" },
                right: { style: "thin" },
            };
        });

        // Set column widths
        worksheet.columns = [
            { width: 15 }, // Receipt No
            { width: 25 }, // Trainee Name
            { width: 15 }, // ULI Number
            { width: 15 }, // Trainee ID
            { width: 30 }, // Program
            { width: 15 }, // Fund Type
            { width: 15 }, // Amount
            { width: 15 }, // Date Generated
        ];

        // Generate Excel file
        const buffer = await workbook.xlsx.writeBuffer();
        const blob = new Blob([buffer], {
            type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        });
        const fileName = `Receipts_Report_${
            new Date().toISOString().split("T")[0]
        }.xlsx`;
        saveAs(blob, fileName);

        notifications.success("Excel file generated successfully!");
    } catch (error) {
        console.error("Error generating Excel file:", error);
        notifications.error("Failed to generate Excel file. Please try again.");
    }
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

    if (numAmount === 0) return "Zero Pesos";

    const convertHundreds = (num) => {
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

    const convertThousands = (num) => {
        if (num >= 1000000) {
            return (
                convertHundreds(Math.floor(num / 1000000)) +
                "Million " +
                convertThousands(num % 1000000)
            );
        } else if (num >= 1000) {
            return (
                convertHundreds(Math.floor(num / 1000)) +
                "Thousand " +
                convertHundreds(num % 1000)
            );
        } else {
            return convertHundreds(num);
        }
    };

    const pesos = Math.floor(numAmount);
    const centavos = Math.round((numAmount - pesos) * 100);

    let result = convertThousands(pesos).trim() + " Pesos";

    if (centavos > 0) {
        result += " and " + convertHundreds(centavos).trim() + " Centavos";
    }

    return result + " Only";
};

const generateReceiptContent = (receipt) => {
    const amountInWords = convertNumberToWords(receipt.amount);
    const currentDate = new Date();
    const formattedDate = currentDate.toLocaleDateString("en-PH");

    // Get all fees from the receipt (both original and custom fees)
    const allFees = [];

    // Add original fees if they exist
    if (receipt.original_fees && Array.isArray(receipt.original_fees)) {
        allFees.push(...receipt.original_fees);
    }

    // Add custom fees if they exist
    if (receipt.fees && Array.isArray(receipt.fees)) {
        allFees.push(...receipt.fees);
    }

    // If no custom fees structure exists, create a basic fee from receipt data
    if (allFees.length === 0) {
        allFees.push({
            natureOfCollection:
                receipt.type === "enrollment"
                    ? "Enrollment Fee"
                    : receipt.type === "assessment"
                    ? "Assessment Fee"
                    : "Enrollment Fee",
            program: receipt.program,
            accountCode: "EDU-001",
            amount: receipt.amount,
        });
    }

    // Generate fee rows
    let feeRows = "";
    allFees.forEach((fee) => {
        const natureText = (fee.natureOfCollection || "Fee")
            .substring(0, 23)
            .padEnd(23);
        const programText = (fee.program || "").substring(0, 23).padEnd(23);
        const amountText = formatCurrency(safeNumber(fee.amount)).padStart(23);
        const accountCode = (fee.accountCode || "EDU-001").padEnd(14);

        feeRows += `│ ${natureText} │ ${accountCode} │ ${amountText} │\n`;
        if (fee.program) {
            feeRows += `│ ${programText} │                │                         │\n`;
        }
        feeRows += `├─────────────────────────┼────────────────┼─────────────────────────┤\n`;
    });

    // Add empty rows to fill up to 4 total rows
    const emptyRowsNeeded = Math.max(0, 3 - allFees.length);
    for (let i = 0; i < emptyRowsNeeded; i++) {
        feeRows += `│                         │                │                         │\n`;
        feeRows += `├─────────────────────────┼────────────────┼─────────────────────────┤\n`;
    }

    return `
LUZON TECHNOLOGICAL AND PROFESSIONAL CENTER, INC.
Brgy. Tanza, Surigao City
BIR Permit No. 12-074-134652-000-2013
Valid Until: 12/31/2024
ACC. No.: 7001-013-134652-000001-2013
PTR: 0125364-01-07-2024

                    OFFICIAL RECEIPT
                      No. ${receipt.id}

Date: ${formattedDate}
Fund Type: ${receipt.fund_type || "General Fund"}
Received from: ${receipt.trainee.name}
ULI No: ${receipt.trainee.uli_number || "N/A"}
Trainee ID: ${receipt.trainee.id}

The sum of: ${formatCurrency(safeNumber(receipt.amount))}

┌─────────────────────────┬────────────────┬─────────────────────────┐
│ NATURE OF COLLECTION    │ ACCOUNT CODE   │ AMOUNT                  │
├─────────────────────────┼────────────────┼─────────────────────────┤
${feeRows}│ TOTAL                   │                │ ${formatCurrency(
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

    // Get all fees from the receipt (both original and custom fees)
    const allFees = [];

    // Add original fees if they exist
    if (receipt.original_fees && Array.isArray(receipt.original_fees)) {
        allFees.push(...receipt.original_fees);
    }

    // Add custom fees if they exist
    if (receipt.fees && Array.isArray(receipt.fees)) {
        allFees.push(...receipt.fees);
    }

    // If no custom fees structure exists, create a basic fee from receipt data
    if (allFees.length === 0) {
        allFees.push({
            natureOfCollection:
                receipt.type === "enrollment"
                    ? "Enrollment Fee"
                    : receipt.type === "assessment"
                    ? "Assessment Fee"
                    : "Enrollment Fee",
            program: receipt.program,
            accountCode: "EDU-001",
            amount: receipt.amount,
        });
    }

    // Generate fee rows HTML
    let feeRowsHTML = "";
    allFees.forEach((fee) => {
        feeRowsHTML += `
        <div class="table-row">
            <div class="col-nature">${fee.natureOfCollection || "Fee"}</div>
            <div class="col-account">${fee.accountCode || "EDU-001"}</div>
            <div class="col-amount">${formatCurrency(
                safeNumber(fee.amount)
            )}</div>
        </div>`;

        if (fee.program) {
            feeRowsHTML += `
        <div class="table-row">
            <div class="col-nature">${fee.program}</div>
            <div class="col-account"></div>
            <div class="col-amount"></div>
        </div>`;
        }
    });

    // Add empty rows to fill up to 4 total rows
    const emptyRowsNeeded = Math.max(0, 4 - allFees.length * 2);
    for (let i = 0; i < emptyRowsNeeded; i++) {
        feeRowsHTML += `
        <div class="table-row">
            <div class="col-nature"></div>
            <div class="col-account"></div>
            <div class="col-amount"></div>
        </div>`;
    }

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
        <div class="header-section">
            <div class="header-logos">
                <div class="logo-placeholder phil-logo"></div>
                <div style="text-align: center; flex: 1; margin: 0 20px;">
                    <h2 style="margin: 0; font-size: 16px; font-weight: bold;">
                        LUZON TECHNOLOGICAL AND PROFESSIONAL CENTER, INC.
                    </h2>
                    <p style="margin: 5px 0; font-size: 12px;">Brgy. Tanza, Surigao City</p>
                    <p style="margin: 2px 0; font-size: 10px;">BIR Permit No. 12-074-134652-000-2013</p>
                    <p style="margin: 2px 0; font-size: 10px;">Valid Until: 12/31/2024</p>
                    <p style="margin: 2px 0; font-size: 10px;">ACC. No.: 7001-013-134652-000001-2013</p>
                    <p style="margin: 2px 0; font-size: 10px;">PTR: 0125364-01-07-2024</p>
                </div>
                <div class="logo-placeholder surigao-logo"></div>
            </div>
        </div>
        
        <div class="form-info">
            <div class="form-left">
                <strong>OFFICIAL RECEIPT</strong>
            </div>
            <div class="form-right">
                <strong>No. ${receipt.id}</strong>
            </div>
        </div>

        <div class="date-section">
            <div class="date-left">Date:</div>
            <div class="date-right">${formattedDate}</div>
        </div>

        <div class="date-section">
            <div class="date-left">Fund Type:</div>
            <div class="date-right"><strong>${
                receipt.fund_type || "General Fund"
            }</strong></div>
        </div>

        <div class="payor-section">
            <div class="payor-left">
                <strong>Received from:</strong> ${receipt.trainee.name}<br>
                <strong>ULI No:</strong> ${
                    receipt.trainee.uli_number || "N/A"
                }<br>
                <strong>Trainee ID:</strong> ${receipt.trainee.id}
            </div>
            <div class="payor-right">
                <strong>Amount</strong><br>
                ${formatCurrency(safeNumber(receipt.amount))}
            </div>
        </div>

        <div class="table-header">
            <div class="col-nature">NATURE OF COLLECTION</div>
            <div class="col-account">ACCOUNT CODE</div>
            <div class="col-amount">AMOUNT</div>
        </div>

        ${feeRowsHTML}

        <div class="total-row">
            <div class="col-nature">TOTAL</div>
            <div class="col-account"></div>
            <div class="col-amount">${formatCurrency(
                safeNumber(receipt.amount)
            )}</div>
        </div>

        <div class="amount-words">
            <strong>AMOUNT IN WORDS:</strong><br>
            ${amountInWords.toUpperCase()}
        </div>

        <div class="payment-method">
            <div class="method-left">
                <input type="checkbox" class="checkbox" checked> Cash<br>
                <input type="checkbox" class="checkbox"> Check<br>
                <input type="checkbox" class="checkbox"> Money Order
            </div>
            <div class="method-right">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px solid #000;">
                        <th style="border-right: 1px solid #000; padding: 4px; text-align: center;">DRAWEE BANK</th>
                        <th style="border-right: 1px solid #000; padding: 4px; text-align: center;">NUMBER</th>
                        <th style="padding: 4px; text-align: center;">DATE</th>
                    </tr>
                    <tr>
                        <td style="border-right: 1px solid #000; padding: 4px; height: 25px;"></td>
                        <td style="border-right: 1px solid #000; padding: 4px;"></td>
                        <td style="padding: 4px;"></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="signature-section">
            <p>Received the amount stated above</p>
            <br><br>
            <div style="text-align: right; margin-right: 50px;">
                _________________________<br>
                Collecting Officer
            </div>
        </div>
    </div>
</body>
</html>`;
};

// Expand all trainees by default for better UX
onMounted(() => {
    groupedReceipts.value.forEach((group) => {
        expandedTrainees.value.add(group.trainee_id);
    });
});

// Keyboard event handler
const handleKeydown = (event) => {
    if (event.key === "Escape") {
        if (showReceiptModal.value) {
            closeReceiptModal();
        }
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
                        View and manage generated and cancelled receipts.
                    </p>
                </div>
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
                        ></path>
                    </svg>
                    Export to Excel
                </button>
            </div>

            <!-- Tabs -->
            <div class="mb-6 animate-fade-in">
                <nav class="flex space-x-8">
                    <button
                        @click="setActiveTab('generated')"
                        :class="[
                            'py-2 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'generated'
                                ? 'border-green-500 text-green-600'
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
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            ></path>
                        </svg>
                        Generated Receipts ({{ groupedReceipts.length }})
                    </button>
                    <button
                        @click="setActiveTab('cancelled')"
                        :class="[
                            'py-2 px-1 border-b-2 font-medium text-sm',
                            activeTab === 'cancelled'
                                ? 'border-red-500 text-red-600'
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
                                d="M6 18L18 6M6 6l12 12"
                            ></path>
                        </svg>
                        Cancelled Receipts ({{ cancelledReceipts.length }})
                    </button>
                </nav>
            </div>

            <!-- Success Message -->
            <div
                v-if="showSuccessMessage"
                class="mb-6 p-4 bg-green-100 border border-green-300 text-green-700 rounded-lg animate-fade-in"
            >
                {{ successMessage }}
            </div>

            <!-- Search and Summary for Generated Receipts -->
            <div
                v-if="activeTab === 'generated'"
                class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 animate-fade-in"
            >
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-6">
                            <h2
                                class="text-lg font-semibold text-gray-900 flex items-center"
                            >
                                <svg
                                    class="w-5 h-5 mr-2 text-green-600"
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
                                Generated Receipts by Trainee
                            </h2>
                            <div
                                class="flex items-center space-x-4 text-sm text-gray-600"
                            >
                                <span
                                    class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full"
                                >
                                    {{ filteredGroupedReceipts.length }}
                                    Trainees
                                </span>
                                <span
                                    class="px-3 py-1 bg-green-100 text-green-800 rounded-full"
                                >
                                    {{
                                        filteredGroupedReceipts.reduce(
                                            (sum, group) =>
                                                sum + group.total_receipts,
                                            0
                                        )
                                    }}
                                    Total Receipts
                                </span>
                                <span
                                    class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full"
                                >
                                    {{
                                        formatCurrency(
                                            filteredGroupedReceipts.reduce(
                                                (sum, group) =>
                                                    sum + group.total_amount,
                                                0
                                            )
                                        )
                                    }}
                                    Total Amount
                                </span>
                            </div>
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
                                    placeholder="Search trainees or receipts..."
                                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Filters Section -->
                    <div
                        class="mt-4 flex flex-wrap items-center gap-4 pt-4 border-t border-gray-200"
                    >
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-700"
                                >Date From:</label
                            >
                            <input
                                v-model="dateFrom"
                                type="date"
                                class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-700"
                                >Date To:</label
                            >
                            <input
                                v-model="dateTo"
                                type="date"
                                class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-700"
                                >Fund Type:</label
                            >
                            <select
                                v-model="fundTypeFilter"
                                class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option value="all">All Funds</option>
                                <option value="General Fund">
                                    General Fund
                                </option>
                                <option value="Trust Fund">Trust Fund</option>
                            </select>
                        </div>

                        <button
                            v-if="
                                dateFrom ||
                                dateTo ||
                                fundTypeFilter !== 'all' ||
                                searchQuery
                            "
                            @click="clearFilters"
                            class="px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors duration-200 flex items-center gap-2"
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
                                ></path>
                            </svg>
                            Clear Filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Search and Summary for Cancelled Receipts -->
            <div
                v-if="activeTab === 'cancelled'"
                class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 animate-fade-in"
            >
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-6">
                            <h2
                                class="text-lg font-semibold text-gray-900 flex items-center"
                            >
                                <svg
                                    class="w-5 h-5 mr-2 text-red-600"
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
                                Cancelled Receipts (Audit Trail)
                            </h2>
                            <div
                                class="flex items-center space-x-4 text-sm text-gray-600"
                            >
                                <span
                                    class="px-3 py-1 bg-red-100 text-red-800 rounded-full"
                                >
                                    {{ filteredCancelledReceipts.length }}
                                    Cancelled
                                </span>
                                <span
                                    class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full"
                                >
                                    {{
                                        formatCurrency(
                                            filteredCancelledReceipts.reduce(
                                                (sum, receipt) =>
                                                    sum + receipt.amount,
                                                0
                                            )
                                        )
                                    }}
                                    Total Amount
                                </span>
                            </div>
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
                                    placeholder="Search cancelled receipts..."
                                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Generated Receipts - Trainee Groups -->
            <div v-if="activeTab === 'generated'" class="space-y-4">
                <div
                    v-for="group in filteredGroupedReceipts"
                    :key="group.trainee_id"
                    class="bg-white rounded-xl shadow-sm border border-gray-100 animate-fade-in"
                >
                    <!-- Trainee Header -->
                    <div
                        @click="toggleTraineeExpansion(group.trainee_id)"
                        class="px-6 py-4 border-b border-gray-200 cursor-pointer hover:bg-gray-50 transition-colors duration-200"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0 h-12 w-12">
                                    <div
                                        class="h-12 w-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center"
                                    >
                                        <span
                                            class="text-lg font-bold text-white"
                                        >
                                            {{
                                                group.trainee_name
                                                    .split(" ")
                                                    .map((n) => n[0])
                                                    .join("")
                                            }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <h3
                                        class="text-lg font-semibold text-gray-900"
                                    >
                                        {{ group.trainee_name }}
                                    </h3>
                                    <div
                                        class="flex items-center space-x-4 text-sm text-gray-600"
                                    >
                                        <span>{{
                                            group.trainee_id_number
                                        }}</span>
                                        <span v-if="group.trainee_uli_number"
                                            >ULI:
                                            {{ group.trainee_uli_number }}</span
                                        >
                                    </div>
                                    <!-- Program names under trainee name -->
                                    <div
                                        v-if="group.program_names"
                                        class="text-sm text-blue-600 mt-1"
                                    >
                                        {{ group.program_names }}
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-6">
                                <div class="text-right">
                                    <div class="text-sm text-gray-500">
                                        {{ group.total_receipts }} Receipt{{
                                            group.total_receipts !== 1
                                                ? "s"
                                                : ""
                                        }}
                                    </div>
                                    <div
                                        class="text-lg font-semibold text-gray-900"
                                    >
                                        {{ formatCurrency(group.total_amount) }}
                                    </div>
                                </div>
                                <svg
                                    class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                    :class="{
                                        'rotate-180': isTraineeExpanded(
                                            group.trainee_id
                                        ),
                                    }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    ></path>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Receipts List (Expandable) -->
                    <div
                        v-if="isTraineeExpanded(group.trainee_id)"
                        class="divide-y divide-gray-100"
                    >
                        <div
                            v-for="receipt in group.receipts"
                            :key="receipt.id"
                            class="px-6 py-4 hover:bg-gray-50 transition-colors duration-200"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="w-10 h-10 rounded-lg flex items-center justify-center text-white text-sm font-medium"
                                            :class="{
                                                'bg-blue-500':
                                                    receipt.type ===
                                                    'enrollment',
                                                'bg-purple-500':
                                                    receipt.type ===
                                                    'assessment',
                                                'bg-orange-500':
                                                    receipt.type ===
                                                    'registration',
                                            }"
                                        >
                                            {{
                                                receipt.type === "enrollment"
                                                    ? "EN"
                                                    : receipt.type ===
                                                      "assessment"
                                                    ? "AS"
                                                    : "REG"
                                            }}
                                        </div>
                                    </div>
                                    <div>
                                        <div
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{ receipt.id }}
                                        </div>
                                        <div class="text-sm text-gray-600">
                                            {{
                                                receipt.natureOfCollection ||
                                                receipt.program
                                            }}
                                        </div>
                                        <div
                                            class="flex items-center space-x-4 text-xs text-gray-500 mt-1"
                                        >
                                            <span class="capitalize">{{
                                                receipt.type
                                            }}</span>
                                            <span>{{
                                                receipt.dateGenerated
                                            }}</span>
                                            <span>{{
                                                receipt.timeGenerated
                                            }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <div class="text-right">
                                        <div
                                            class="text-lg font-semibold text-gray-900"
                                        >
                                            {{ formatCurrency(receipt.amount) }}
                                        </div>
                                        <div
                                            class="text-xs px-2 py-1 rounded-full"
                                            :class="{
                                                'bg-green-100 text-green-800':
                                                    receipt.status ===
                                                    'generated',
                                                'bg-blue-100 text-blue-800':
                                                    receipt.isCustom,
                                            }"
                                        >
                                            {{
                                                receipt.isCustom
                                                    ? "Custom"
                                                    : receipt.status
                                            }}
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button
                                            @click="viewReceipt(receipt)"
                                            class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors duration-200"
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
                                            @click="downloadReceipt(receipt)"
                                            :disabled="
                                                isDownloading[receipt.id]
                                            "
                                            class="p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors duration-200 disabled:opacity-50"
                                            title="Download Receipt"
                                        >
                                            <svg
                                                v-if="isDownloading[receipt.id]"
                                                class="w-4 h-4 animate-spin"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                                ></path>
                                            </svg>
                                            <svg
                                                v-else
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
                                        </button>
                                        <button
                                            @click="printReceipt(receipt)"
                                            :disabled="isPrinting[receipt.id]"
                                            class="p-2 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors duration-200 disabled:opacity-50"
                                            title="Print Receipt"
                                        >
                                            <svg
                                                v-if="isPrinting[receipt.id]"
                                                class="w-4 h-4 animate-spin"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                                ></path>
                                            </svg>
                                            <svg
                                                v-else
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
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State for Generated Receipts -->
            <div
                v-if="
                    activeTab === 'generated' &&
                    filteredGroupedReceipts.length === 0
                "
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center animate-fade-in"
            >
                <svg
                    class="w-16 h-16 text-gray-300 mx-auto mb-4"
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
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                    No Generated Receipts Found
                </h3>
                <p class="text-gray-600 mb-4">
                    No generated receipts match your search criteria.
                </p>
                <button
                    @click="searchQuery = ''"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200"
                >
                    Clear Search
                </button>
            </div>

            <!-- Cancelled Receipts List -->
            <div v-if="activeTab === 'cancelled'" class="space-y-4">
                <div
                    v-for="receipt in filteredCancelledReceipts"
                    :key="receipt.id"
                    class="bg-white rounded-xl shadow-sm border border-gray-100 animate-fade-in"
                >
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-12 h-12 rounded-lg bg-red-100 flex items-center justify-center"
                                    >
                                        <svg
                                            class="w-6 h-6 text-red-600"
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
                                    </div>
                                </div>
                                <div>
                                    <div
                                        class="text-lg font-medium text-gray-900"
                                    >
                                        {{ receipt.id }}
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        {{ receipt.trainee.name }} ({{
                                            receipt.trainee.id
                                        }})
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ receipt.program }}
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <div
                                    class="text-lg font-semibold text-gray-900"
                                >
                                    {{ formatCurrency(receipt.amount) }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ receipt.dateGenerated }}
                                    {{ receipt.timeGenerated }}
                                </div>
                                <div
                                    class="text-xs px-2 py-1 bg-red-100 text-red-800 rounded-full mt-1"
                                >
                                    CANCELLED
                                </div>
                            </div>
                        </div>
                        <div
                            v-if="receipt.cancellation_reason"
                            class="mt-4 pt-4 border-t border-gray-200"
                        >
                            <div class="text-sm text-gray-600">
                                <span class="font-medium"
                                    >Cancellation Reason:</span
                                >
                                {{ receipt.cancellation_reason }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State for Cancelled Receipts -->
            <div
                v-if="
                    activeTab === 'cancelled' &&
                    filteredCancelledReceipts.length === 0
                "
                class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center animate-fade-in"
            >
                <svg
                    class="w-16 h-16 text-gray-300 mx-auto mb-4"
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
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                    No Cancelled Receipts Found
                </h3>
                <p class="text-gray-600 mb-4">
                    {{
                        searchQuery
                            ? "No cancelled receipts match your search criteria."
                            : "No receipts have been cancelled yet."
                    }}
                </p>
                <button
                    v-if="searchQuery"
                    @click="searchQuery = ''"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200"
                >
                    Clear Search
                </button>
            </div>
        </div>

        <!-- Receipt Modal -->
        <div
            v-if="showReceiptModal"
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div
                class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0"
            >
                <div
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                    aria-hidden="true"
                    @click="closeReceiptModal"
                ></div>

                <span
                    class="hidden sm:inline-block sm:align-middle sm:h-screen"
                    aria-hidden="true"
                    >&#8203;</span
                >

                <div
                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full"
                >
                    <div class="bg-white">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Receipt Preview - {{ selectedReceipt?.id }}
                            </h3>
                        </div>
                        <div class="px-6 py-4">
                            <div
                                v-if="selectedReceipt"
                                v-html="generateReceiptHTML(selectedReceipt)"
                                class="border border-gray-300 rounded-lg p-4 bg-gray-50"
                            ></div>
                        </div>
                        <div
                            class="bg-gray-50 px-6 py-4 flex justify-end space-x-3"
                        >
                            <button
                                type="button"
                                @click="closeReceiptModal"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Close
                            </button>
                            <button
                                @click="printReceipt(selectedReceipt)"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Print Receipt
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.5s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.rotate-180 {
    transform: rotate(180deg);
}
</style>
