<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import jsPDF from "jspdf";
import * as XLSX from "xlsx";
import { saveAs } from "file-saver";

const props = defineProps({
    program: Object,
    enrollments: Array,
    flash: Object,
});

const searchQuery = ref("");
const statusFilter = ref("all");
const startDate = ref("");
const endDate = ref("");

const filteredEnrollments = computed(() => {
    let filtered = props.enrollments || [];

    if (searchQuery.value) {
        filtered = filtered.filter(
            (enrollment) =>
                enrollment.trainee?.full_name
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                enrollment.trainee?.email
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                enrollment.trainee?.contact_number?.includes(searchQuery.value)
        );
    }

    if (statusFilter.value !== "all") {
        filtered = filtered.filter(
            (enrollment) => enrollment.status === statusFilter.value
        );
    }

    // Filter by date range
    if (startDate.value) {
        filtered = filtered.filter((enrollment) => {
            if (!enrollment.enrollment_date) return false;
            
            // Extract date-only string directly to avoid timezone conversion issues
            const enrollmentDateStr = String(enrollment.enrollment_date).split('T')[0].split(' ')[0];
            return enrollmentDateStr >= startDate.value;
        });
    }

    if (endDate.value) {
        filtered = filtered.filter((enrollment) => {
            if (!enrollment.enrollment_date) return false;
            
            // Extract date-only string directly to avoid timezone conversion issues
            const enrollmentDateStr = String(enrollment.enrollment_date).split('T')[0].split(' ')[0];
            return enrollmentDateStr <= endDate.value;
        });
    }

    return filtered;
});

// Percentage calculations for filtered results
const completionPercentage = computed(() => {
    if (filteredEnrollments.value.length === 0) return 0;
    const completed = filteredEnrollments.value.filter(
        (e) => e.status === "completed"
    ).length;
    return Math.round((completed / filteredEnrollments.value.length) * 100);
});

const activePercentage = computed(() => {
    if (filteredEnrollments.value.length === 0) return 0;
    const active = filteredEnrollments.value.filter(
        (e) => e.status === "active"
    ).length;
    return Math.round((active / filteredEnrollments.value.length) * 100);
});

const droppedPercentage = computed(() => {
    if (filteredEnrollments.value.length === 0) return 0;
    const dropped = filteredEnrollments.value.filter(
        (e) => e.status === "dropped"
    ).length;
    return Math.round((dropped / filteredEnrollments.value.length) * 100);
});

const pendingPercentage = computed(() => {
    if (filteredEnrollments.value.length === 0) return 0;
    const pending = filteredEnrollments.value.filter(
        (e) => e.status === "pending"
    ).length;
    return Math.round((pending / filteredEnrollments.value.length) * 100);
});

const getStatusColor = (status) => {
    const colors = {
        active: "bg-green-100 text-green-800",
        completed: "bg-blue-100 text-blue-800",
        dropped: "bg-red-100 text-red-800",
        pending: "bg-yellow-100 text-yellow-800",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};

const getPaymentStatusColor = (status) => {
    const colors = {
        paid: "bg-green-100 text-green-800",
        unpaid: "bg-red-100 text-red-800",
        partial: "bg-yellow-100 text-yellow-800",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};

const formatCurrency = (amount) => {
    if (!amount) return "Free";
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(amount);
};

const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("en-PH");
};

const goBack = () => {
    router.visit("/admin/programs");
};

const exportToPDF = () => {
    const doc = new jsPDF();

    // Add title
    doc.setFontSize(18);
    doc.text(`${props.program?.name} - Enrollment Report`, 14, 22);

    // Add subtitle with date range if filters are applied
    doc.setFontSize(12);
    let subtitle = "All Enrollments";
    if (startDate.value || endDate.value) {
        subtitle = `Enrollments from ${startDate.value || "beginning"} to ${
            endDate.value || "present"
        }`;
    }
    doc.text(subtitle, 14, 32);

    // Add summary statistics
    doc.setFontSize(10);
    doc.text(`Total Enrollments: ${filteredEnrollments.value.length}`, 14, 42);

    const completedCount = filteredEnrollments.value.filter(
        (e) => e.status === "completed"
    ).length;
    const activeCount = filteredEnrollments.value.filter(
        (e) => e.status === "active"
    ).length;
    const droppedCount = filteredEnrollments.value.filter(
        (e) => e.status === "dropped"
    ).length;
    const pendingCount = filteredEnrollments.value.filter(
        (e) => e.status === "pending"
    ).length;

    doc.text(
        `Completed: ${completedCount} (${completionPercentage.value}%)`,
        14,
        50
    );
    doc.text(`Active: ${activeCount} (${activePercentage.value}%)`, 14, 58);
    doc.text(`Dropped: ${droppedCount} (${droppedPercentage.value}%)`, 14, 66);
    doc.text(`Pending: ${pendingCount} (${pendingPercentage.value}%)`, 14, 74);

    // Create a simple table without autotable
    let yPosition = 86;
    const lineHeight = 8;
    const pageHeight = 280;
    let currentPage = 1;

    // Add headers
    doc.setFontSize(8);
    doc.setFillColor(34, 139, 34);
    doc.rect(14, yPosition - 5, 180, 8, "F");
    doc.setTextColor(255, 255, 255);
    doc.text("Name", 16, yPosition);
    doc.text("Email", 50, yPosition);
    doc.text("Batch", 90, yPosition);
    doc.text("Status", 110, yPosition);
    doc.text("Enrollment Date", 130, yPosition);
    doc.text("Fee", 170, yPosition);

    yPosition += 10;
    doc.setTextColor(0, 0, 0);

    // Add data rows
    filteredEnrollments.value.forEach((enrollment, index) => {
        // Check if we need a new page
        if (yPosition > pageHeight) {
            doc.addPage();
            currentPage++;
            yPosition = 20;
        }

        const name = (enrollment.trainee?.full_name || "N/A").substring(0, 20);
        const email = (enrollment.trainee?.email || "N/A").substring(0, 25);
        const batch = `Batch ${enrollment.batch}`;
        const status = enrollment.status;
        const enrollmentDate = formatDate(enrollment.enrollment_date);
        const fee = formatCurrency(enrollment.enrollment_fee);

        doc.text(name, 16, yPosition);
        doc.text(email, 50, yPosition);
        doc.text(batch, 90, yPosition);
        doc.text(status, 110, yPosition);
        doc.text(enrollmentDate, 130, yPosition);
        doc.text(fee, 170, yPosition);

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
    const filename = `${props.program?.name.replace(
        /\s+/g,
        "_"
    )}_enrollments_${timestamp}.pdf`;

    // Save the PDF
    doc.save(filename);
};

const exportToExcel = () => {
    // Prepare data for Excel
    const excelData = filteredEnrollments.value.map((enrollment) => ({
        "Student Name": enrollment.trainee?.full_name || "N/A",
        Email: enrollment.trainee?.email || "N/A",
        "Contact Number": enrollment.trainee?.contact_number || "N/A",
        Batch: enrollment.batch,
        "Enrollment Date": enrollment.enrollment_date
            ? formatDate(enrollment.enrollment_date)
            : "N/A",
        Status: enrollment.status,
        "Payment Status": enrollment.payment_status,
        "Enrollment Fee": enrollment.enrollment_fee
            ? formatCurrency(enrollment.enrollment_fee)
            : "N/A",
        "Completion Date": enrollment.completion_date
            ? formatDate(enrollment.completion_date)
            : "N/A",
    }));

    // Create workbook and worksheet
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.json_to_sheet(excelData);

    // Calculate counts for summary
    const totalCount = filteredEnrollments.value.length;
    const completedCount = filteredEnrollments.value.filter(
        (e) => e.status === "completed"
    ).length;
    const activeCount = filteredEnrollments.value.filter(
        (e) => e.status === "active"
    ).length;
    const droppedCount = filteredEnrollments.value.filter(
        (e) => e.status === "dropped"
    ).length;
    const pendingCount = filteredEnrollments.value.filter(
        (e) => e.status === "pending"
    ).length;

    // Add summary information at the top
    const summaryData = [
        ["Program:", props.program?.name],
        ["Report Date:", new Date().toLocaleDateString()],
        [""],
        ["Summary Statistics:"],
        ["Total Enrollments:", totalCount],
        ["Completed:", `${completedCount} (${completionPercentage.value}%)`],
        ["Active:", `${activeCount} (${activePercentage.value}%)`],
        ["Dropped:", `${droppedCount} (${droppedPercentage.value}%)`],
        ["Pending:", `${pendingCount} (${pendingPercentage.value}%)`],
        [""],
    ];

    // Insert summary at the beginning
    XLSX.utils.sheet_add_aoa(ws, summaryData, { origin: "A1" });

    // Adjust column widths
    const colWidths = [
        { wch: 25 }, // Student Name
        { wch: 30 }, // Email
        { wch: 15 }, // Contact Number
        { wch: 10 }, // Batch
        { wch: 15 }, // Enrollment Date
        { wch: 12 }, // Status
        { wch: 15 }, // Payment Status
        { wch: 15 }, // Enrollment Fee
        { wch: 15 }, // Completion Date
    ];
    ws["!cols"] = colWidths;

    // Add worksheet to workbook
    XLSX.utils.book_append_sheet(wb, ws, "Enrollments");

    // Generate filename
    const timestamp = new Date().toISOString().split("T")[0];
    const filename = `${props.program?.name.replace(
        /\s+/g,
        "_"
    )}_enrollments_${timestamp}.xlsx`;

    // Save the Excel file
    const wbout = XLSX.write(wb, { bookType: "xlsx", type: "array" });
    const blob = new Blob([wbout], { type: "application/octet-stream" });
    saveAs(blob, filename);
};
</script>

<template>
    <Head :title="`${program?.name} - Enrollments`" />
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
                            <div class="flex items-center gap-3 mb-2">
                                <button
                                    @click="goBack"
                                    class="inline-flex items-center rounded-md border border-gray-300 bg-gray-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25"
                                >
                                    ← Back to Programs
                                </button>
                            </div>
                            <h3
                                class="text-lg font-semibold text-green-900 relative pb-2 after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-20 after:h-0.5 after:bg-gradient-to-r after:rounded"
                            >
                                {{ program?.name }} - Total Enrollments
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">
                                View all students who have ever enrolled in this
                                program
                            </p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="text-right">
                                <div class="text-2xl font-bold text-green-600">
                                    {{ enrollments?.length || 0 }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    Total Enrollments
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Program Info Section -->
                <div class="p-6 bg-gray-50 border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">
                                Program Details
                            </h4>
                            <div class="space-y-1 text-sm text-gray-600">
                                <div>
                                    <strong>Duration:</strong>
                                    {{ program?.duration }}
                                </div>
                                <div>
                                    <strong>Fee:</strong>
                                    {{
                                        formatCurrency(program?.enrollment_fee)
                                    }}
                                </div>
                                <div>
                                    <strong>Status:</strong>
                                    <span
                                        :class="getStatusColor(program?.status)"
                                        class="px-2 py-1 rounded-full text-xs"
                                    >
                                        {{ program?.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 mb-2">
                                Enrollment Statistics
                            </h4>
                            <div class="space-y-1 text-sm text-gray-600">
                                <div>
                                    <strong>Active:</strong>
                                    {{
                                        enrollments?.filter(
                                            (e) => e.status === "active"
                                        ).length || 0
                                    }}
                                </div>
                                <div>
                                    <strong>Completed:</strong>
                                    {{
                                        enrollments?.filter(
                                            (e) => e.status === "completed"
                                        ).length || 0
                                    }}
                                </div>
                                <div>
                                    <strong>Dropped:</strong>
                                    {{
                                        enrollments?.filter(
                                            (e) => e.status === "dropped"
                                        ).length || 0
                                    }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters Section -->
                <div
                    class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 border-b border-gray-200"
                >
                    <div
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4"
                    >
                        <div class="relative">
                            <InputLabel for="search" value="Search Students" />
                            <TextInput
                                id="search"
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by name, email, or contact number"
                                class="mt-1 block w-full transition-all duration-300 border-2 border-transparent focus:border-green-500 focus:ring-2 focus:ring-green-200 hover:border-green-300"
                            />
                        </div>
                        <div>
                            <InputLabel for="status" value="Filter by Status" />
                            <select
                                id="status"
                                v-model="statusFilter"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500"
                            >
                                <option value="all">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="dropped">Dropped</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="startDate" value="From Date" />
                            <TextInput
                                id="startDate"
                                v-model="startDate"
                                type="date"
                                class="mt-1 block w-full transition-all duration-300 border-2 border-transparent focus:border-green-500 focus:ring-2 focus:ring-green-200 hover:border-green-300"
                            />
                        </div>
                        <div>
                            <InputLabel for="endDate" value="To Date" />
                            <TextInput
                                id="endDate"
                                v-model="endDate"
                                type="date"
                                class="mt-1 block w-full transition-all duration-300 border-2 border-transparent focus:border-green-500 focus:ring-2 focus:ring-green-200 hover:border-green-300"
                            />
                        </div>
                        <div class="flex items-end">
                            <button
                                @click="
                                    () => {
                                        searchQuery = '';
                                        statusFilter = 'all';
                                        startDate = '';
                                        endDate = '';
                                    }
                                "
                                class="w-full inline-flex items-center justify-center rounded-md border border-gray-300 bg-gray-600 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-150 ease-in-out hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25"
                            >
                                Clear Filters
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filtered Results Summary -->
                <div
                    v-if="
                        searchQuery ||
                        statusFilter !== 'all' ||
                        startDate ||
                        endDate
                    "
                    class="p-4 bg-blue-50 border-b border-blue-200"
                >
                    <div
                        class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                    >
                        <div>
                            <h4 class="text-sm font-medium text-blue-900 mb-2">
                                Filtered Results Summary
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="text-center">
                                    <div
                                        class="text-2xl font-bold text-blue-600"
                                    >
                                        {{ filteredEnrollments.length }}
                                    </div>
                                    <div class="text-xs text-blue-700">
                                        Total Enrollments
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="flex flex-col items-center">
                                        <div
                                            class="text-2xl font-bold text-green-600"
                                        >
                                            {{
                                                filteredEnrollments.filter(
                                                    (e) =>
                                                        e.status === "completed"
                                                ).length
                                            }}
                                        </div>
                                        <div class="text-xs text-green-700">
                                            Completed ({{
                                                completionPercentage
                                            }}%)
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="flex flex-col items-center">
                                        <div
                                            class="text-2xl font-bold text-orange-600"
                                        >
                                            {{
                                                filteredEnrollments.filter(
                                                    (e) => e.status === "active"
                                                ).length
                                            }}
                                        </div>
                                        <div class="text-xs text-orange-700">
                                            Active ({{ activePercentage }}%)
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center">
                                    <div class="flex flex-col items-center">
                                        <div
                                            class="text-2xl font-bold text-red-600"
                                        >
                                            {{
                                                filteredEnrollments.filter(
                                                    (e) =>
                                                        e.status === "dropped"
                                                ).length
                                            }}
                                        </div>
                                        <div class="text-xs text-red-700">
                                            Dropped ({{ droppedPercentage }}%)
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-blue-700 mb-3">
                                Showing {{ filteredEnrollments.length }} of
                                {{ enrollments?.length || 0 }} total enrollments
                            </div>
                            <div class="flex gap-2">
                                <button
                                    @click="exportToPDF"
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-xs font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200"
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
                                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        ></path>
                                    </svg>
                                    Export PDF
                                </button>
                                <button
                                    @click="exportToExcel"
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200"
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
                                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                        ></path>
                                    </svg>
                                    Export Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enrollments Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Student
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Batch
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Enrollment Date
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Status
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Payment Status
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Fee
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Completion Date
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="enrollment in filteredEnrollments"
                                :key="enrollment.id"
                                class="hover:bg-gray-50 transition-colors duration-200"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div>
                                            <div
                                                class="text-sm font-medium text-gray-900"
                                            >
                                                {{
                                                    enrollment.trainee
                                                        ?.full_name
                                                }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ enrollment.trainee?.email }}
                                            </div>
                                            <div class="text-sm text-gray-400">
                                                {{
                                                    enrollment.trainee
                                                        ?.contact_number
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        Batch {{ enrollment.batch }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{
                                            formatDate(
                                                enrollment.enrollment_date
                                            )
                                        }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="
                                            getStatusColor(enrollment.status)
                                        "
                                    >
                                        {{ enrollment.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="
                                            getPaymentStatusColor(
                                                enrollment.payment_status
                                            )
                                        "
                                    >
                                        {{ enrollment.payment_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{
                                            formatCurrency(
                                                enrollment.enrollment_fee
                                            )
                                        }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{
                                            formatDate(
                                                enrollment.completion_date
                                            )
                                        }}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div
                    v-if="filteredEnrollments.length === 0"
                    class="text-center py-12"
                >
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
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
                        />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">
                        No enrollments found
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{
                            searchQuery ||
                            statusFilter !== "all" ||
                            startDate ||
                            endDate
                                ? "Try adjusting your search or filter criteria."
                                : "No students have enrolled in this program yet."
                        }}
                    </p>
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
</style>
