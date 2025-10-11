<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import ExcelJS from "exceljs";
import { saveAs } from "file-saver";

const props = defineProps({
    summaryStats: { type: Object, required: true },
    collectionsByProgram: { type: Array, required: true },
});

const summary = computed(
    () =>
        props.summaryStats || {
            totalCollections: { amount: 0 },
            thisMonth: { amount: 0 },
            outstandingBalance: { amount: 0 },
        }
);

const collections = computed(() => props.collectionsByProgram || []);

// Filter state
const dateFrom = ref("");
const dateTo = ref("");

// Clear filters
const clearFilters = () => {
    dateFrom.value = "";
    dateTo.value = "";
};

const formatCurrency = (value) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(Number(value || 0));

const exportToExcel = async () => {
    try {
        // Create a new workbook
        const workbook = new ExcelJS.Workbook();
        const worksheet = workbook.addWorksheet("Collections Report");

        // Add title
        worksheet.mergeCells("A1:F1");
        const titleCell = worksheet.getCell("A1");
        titleCell.value = "LTPC Collections Report";
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
        worksheet.mergeCells("A2:F2");
        const dateCell = worksheet.getCell("A2");
        let dateRangeText = "";
        if (dateFrom.value || dateTo.value) {
            const fromText = dateFrom.value || "beginning";
            const toText = dateTo.value || "present";
            dateRangeText = ` | Date Range: ${fromText} to ${toText}`;
        }
        dateCell.value = `Generated on: ${new Date().toLocaleString(
            "en-PH"
        )}${dateRangeText}`;
        dateCell.alignment = { horizontal: "center" };
        dateCell.font = { size: 10, italic: true };
        worksheet.getRow(2).height = 20;

        // Add summary section
        worksheet.addRow([]);
        worksheet.addRow(["SUMMARY"]);
        worksheet.getRow(4).font = { bold: true, size: 12 };
        worksheet.getRow(4).fill = {
            type: "pattern",
            pattern: "solid",
            fgColor: { argb: "FFE0E0E0" },
        };

        worksheet.addRow([
            "Total Collections",
            formatCurrency(summary.value.totalCollections.amount),
        ]);
        worksheet.addRow([
            "This Month",
            formatCurrency(summary.value.thisMonth.amount),
        ]);
        worksheet.addRow([
            "Outstanding Balance",
            formatCurrency(summary.value.outstandingBalance.amount),
        ]);

        // Add spacing
        worksheet.addRow([]);
        worksheet.addRow([]);

        // Add collections by program section
        worksheet.addRow(["COLLECTIONS BY PROGRAM"]);
        worksheet.getRow(10).font = { bold: true, size: 12 };
        worksheet.getRow(10).fill = {
            type: "pattern",
            pattern: "solid",
            fgColor: { argb: "FFE0E0E0" },
        };

        // Add headers for collections table
        const headers = [
            "Program",
            "Total Payments",
            "Fully Paid",
            "Partially Paid",
            "Unpaid",
            "Collection Amount",
        ];
        worksheet.addRow(headers);
        const headerRow = worksheet.getRow(11);
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

        // Add data rows
        collections.value.forEach((program) => {
            worksheet.addRow([
                program.program,
                program.totalTrainees,
                program.fullyPaid,
                program.partiallyPaid,
                program.unpaid,
                formatCurrency(program.collectionAmount),
            ]);
        });

        // Style data rows
        const lastRow = worksheet.lastRow.number;
        for (let i = 12; i <= lastRow; i++) {
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
            if ((i - 12) % 2 === 0) {
                row.fill = {
                    type: "pattern",
                    pattern: "solid",
                    fgColor: { argb: "FFF9F9F9" },
                };
            }
        }

        // Add totals row
        const totalCollections = collections.value.reduce(
            (sum, p) => sum + Number(p.collectionAmount || 0),
            0
        );
        const totalRow = worksheet.addRow([
            "TOTAL",
            collections.value.reduce(
                (sum, p) => sum + Number(p.totalTrainees || 0),
                0
            ),
            collections.value.reduce(
                (sum, p) => sum + Number(p.fullyPaid || 0),
                0
            ),
            collections.value.reduce(
                (sum, p) => sum + Number(p.partiallyPaid || 0),
                0
            ),
            collections.value.reduce(
                (sum, p) => sum + Number(p.unpaid || 0),
                0
            ),
            formatCurrency(totalCollections),
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
            { width: 30 }, // Program
            { width: 15 }, // Total Payments
            { width: 12 }, // Fully Paid
            { width: 15 }, // Partially Paid
            { width: 10 }, // Unpaid
            { width: 20 }, // Collection Amount
        ];

        // Generate Excel file
        const buffer = await workbook.xlsx.writeBuffer();
        const blob = new Blob([buffer], {
            type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
        });
        const fileName = `Collections_Report_${
            new Date().toISOString().split("T")[0]
        }.xlsx`;
        saveAs(blob, fileName);
    } catch (error) {
        console.error("Error generating Excel file:", error);
        alert("Failed to generate Excel file. Please try again.");
    }
};
</script>

<template>
    <Head title="Reports" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Reports
                </h2>
                <div class="flex items-center gap-4">
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
                    <div class="text-sm text-gray-500">
                        Summary & Collections
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Date Filters for Excel Export -->
                <div
                    class="bg-white rounded-xl shadow-sm border border-gray-100 p-4"
                >
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-700"
                                >Export Date From:</label
                            >
                            <input
                                v-model="dateFrom"
                                type="date"
                                class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-700"
                                >Export Date To:</label
                            >
                            <input
                                v-model="dateTo"
                                type="date"
                                class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        <button
                            v-if="dateFrom || dateTo"
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

                        <div class="ml-auto text-sm text-gray-500 italic">
                            <svg
                                class="w-4 h-4 inline mr-1"
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
                            Date filters apply to Excel export only
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
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
                                        summary.totalCollections.amount
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                    <div
                        class="bg-white rounded-xl shadow-sm p-6 border border-gray-100"
                    >
                        <div class="mb-4">
                            <h3 class="text-sm font-medium text-gray-600">
                                This Month
                            </h3>
                            <p class="text-3xl font-bold text-blue-600">
                                {{ formatCurrency(summary.thisMonth.amount) }}
                            </p>
                        </div>
                    </div>
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
                                        summary.outstandingBalance.amount
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Collections by Program -->
                <div
                    class="bg-white rounded-xl shadow-sm border border-gray-100"
                >
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Collections by Program
                        </h2>
                    </div>
                    <div
                        v-if="collections.length === 0"
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
                            />
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
                                    v-for="program in collections"
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
        </div>
    </AuthenticatedLayout>
</template>
