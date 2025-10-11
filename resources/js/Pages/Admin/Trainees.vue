<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router, usePage } from "@inertiajs/vue3";
import { ref, reactive, computed, watch } from "vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import DeleteConfirmationModal from "@/Components/DeleteConfirmationModal.vue";
import Pagination from "@/Components/Pagination.vue";
import jsPDF from "jspdf";
import * as XLSX from "xlsx";
import ExcelJS from "exceljs";
import { saveAs } from "file-saver";

const props = defineProps({
    trainees: Object, // Changed from Array to Object to support pagination
    programs: Array,
    flash: Object,
    filters: Object, // Added filters prop
});

const user = computed(() => usePage().props.auth.user);
const isOfficer = computed(() => user.value?.role === "officer");

const showModal = ref(false);
const showDeleteModal = ref(false);
const editingTrainee = ref(null);
const deletingTrainee = ref(null);
const searchQuery = ref(props.filters?.search || "");
const selectedProgram = ref(props.filters?.program || "");
const selectedStatus = ref(props.filters?.status || "");
const selectedEnrollmentType = ref(props.filters?.enrollment_type || "");
const dateFrom = ref(props.filters?.date_from || "");
const dateTo = ref(props.filters?.date_to || "");
const perPage = ref(props.filters?.per_page || 10);
const showFilters = ref(false);

const clearFilters = () => {
    searchQuery.value = "";
    selectedProgram.value = "";
    selectedStatus.value = "";
    selectedEnrollmentType.value = "";
    dateFrom.value = "";
    dateTo.value = "";
    performSearch();
};

const hasActiveFilters = computed(() => {
    return (
        searchQuery.value ||
        selectedProgram.value ||
        selectedEnrollmentType.value ||
        selectedStatus.value ||
        dateFrom.value ||
        dateTo.value
    );
});

const form = useForm({
    first_name: "",
    last_name: "",
    email: "",
    phone: "",
    address: "",
    birth_date: "",
    gender: "",
    program_id: "",
    date_enrolled: "",
    status: "active",
    enrollment_type: "regular",
});

const filteredTrainees = computed(() => {
    let filtered = props.trainees?.data || [];

    if (searchQuery.value) {
        const searchLower = searchQuery.value.toLowerCase();
        filtered = filtered.filter((trainee) => {
            const firstName = trainee.first_name?.toLowerCase() || "";
            const lastName = trainee.last_name?.toLowerCase() || "";
            const middleName = trainee.middle_name?.toLowerCase() || "";
            const fullName = `${firstName} ${lastName}`.trim();
            const fullNameWithMiddle =
                `${firstName} ${middleName} ${lastName}`.trim();
            const uliNumber = trainee.uli_number?.toLowerCase() || "";
            const email =
                trainee.email_facebook?.toLowerCase() ||
                trainee.email?.toLowerCase() ||
                "";

            return (
                firstName.includes(searchLower) ||
                lastName.includes(searchLower) ||
                fullName.includes(searchLower) ||
                fullNameWithMiddle.includes(searchLower) ||
                uliNumber.includes(searchLower) ||
                email.includes(searchLower)
            );
        });
    }

    if (selectedProgram.value) {
        filtered = filtered.filter(
            (trainee) =>
                Array.isArray(trainee.enrollments) &&
                trainee.enrollments.some(
                    (enrollment) =>
                        enrollment.program_id == selectedProgram.value
                )
        );
    }

    if (selectedEnrollmentType.value) {
        filtered = filtered.filter(
            (trainee) =>
                trainee.enrollment_type === selectedEnrollmentType.value
        );
    }

    if (selectedStatus.value) {
        filtered = filtered.filter(
            (trainee) => trainee.status === selectedStatus.value
        );
    }

    if (dateFrom.value) {
        filtered = filtered.filter(
            (trainee) => trainee.entry_date >= dateFrom.value
        );
    }

    if (dateTo.value) {
        filtered = filtered.filter(
            (trainee) => trainee.entry_date <= dateTo.value
        );
    }

    return filtered;
});

// Add search functionality
const performSearch = () => {
    router.get(
        route("admin.trainees"),
        {
            search: searchQuery.value,
            program: selectedProgram.value,
            status: selectedStatus.value,
            enrollment_type: selectedEnrollmentType.value,
            date_from: dateFrom.value,
            date_to: dateTo.value,
            per_page: perPage.value,
            page: 1, // Reset to first page when searching
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

// Add change per page functionality
const changePerPage = () => {
    performSearch();
};

// Watch for filter changes and trigger search
watch(
    [selectedProgram, selectedStatus, selectedEnrollmentType, dateFrom, dateTo],
    () => {
        performSearch();
    },
    { deep: true }
);

const statusOptions = [
    { value: "", label: "All Statuses" },
    { value: "active", label: "Active" },
    { value: "completed", label: "Completed" },
    { value: "dropped", label: "Dropped" },
];

const totalTrainees = computed(() => filteredTrainees.value.length);

const summaryStats = computed(() => {
    const trainees = filteredTrainees.value;
    return {
        total: trainees.length,
        active: trainees.filter((t) => t.status === "active").length,
        completed: trainees.filter((t) => t.status === "completed").length,
        dropped: trainees.filter((t) => t.status === "dropped").length,
    };
});

// Percentage calculations for filtered results
const traineePercentages = computed(() => {
    const stats = summaryStats.value;
    const total = stats.total;

    if (total === 0) {
        return {
            active: 0,
            completed: 0,
            dropped: 0,
        };
    }

    return {
        active: Math.round((stats.active / total) * 100),
        completed: Math.round((stats.completed / total) * 100),
        dropped: Math.round((stats.dropped / total) * 100),
    };
});

const openCreateModal = () => {
    if (!isOfficer.value) return;
    form.reset();
    editingTrainee.value = null;
    showModal.value = true;
};

const openEditModal = (trainee) => {
    if (!isOfficer.value) return;
    editingTrainee.value = trainee;
    form.first_name = trainee.first_name;
    form.last_name = trainee.last_name;
    form.email = trainee.email;
    form.phone = trainee.phone;
    form.address = trainee.address;
    form.birth_date = trainee.birth_date;
    form.gender = trainee.gender;
    form.program_id = trainee.program_id;
    form.date_enrolled = trainee.date_enrolled;
    form.status = trainee.status;
    form.enrollment_type = trainee.enrollment_type;
    showModal.value = true;
};

const openDeleteModal = (trainee) => {
    if (!isOfficer.value) return;
    deletingTrainee.value = trainee;
    showDeleteModal.value = true;
};

const submitForm = () => {
    if (!isOfficer.value) return;
    if (editingTrainee.value) {
        form.put(`/admin/trainees/${editingTrainee.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post("/admin/trainees", {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const deleteTrainee = () => {
    if (!isOfficer.value) return;
    router.delete(`/admin/trainees/${deletingTrainee.value.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
            deletingTrainee.value = null;
        },
    });
};

const viewEnrollmentHistory = (trainee) => {
    // Navigate to trainee enrollment history page based on user role
    const rolePrefix = isOfficer.value ? "officer" : "admin";
    router.visit(`/${rolePrefix}/trainees/${trainee.id}/enrollment-history`);
};

const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("en-PH");
};

const exportToPDF = () => {
    const doc = new jsPDF();

    // Add title
    doc.setFontSize(18);
    doc.text("Trainees Report", 14, 22);

    // Add subtitle with date range if filters are applied
    doc.setFontSize(12);
    let subtitle = "All Trainees";
    if (dateFrom.value || dateTo.value) {
        subtitle = `Trainees enrolled from ${
            dateFrom.value || "beginning"
        } to ${dateTo.value || "present"}`;
    }
    doc.text(subtitle, 14, 32);

    // Add summary statistics
    doc.setFontSize(10);
    doc.text(`Total Trainees: ${summaryStats.value.total}`, 14, 42);

    const percentages = traineePercentages.value;
    doc.text(
        `Active: ${summaryStats.value.active} (${percentages.active}%)`,
        14,
        50
    );
    doc.text(
        `Completed: ${summaryStats.value.completed} (${percentages.completed}%)`,
        14,
        58
    );
    doc.text(
        `Dropped: ${summaryStats.value.dropped} (${percentages.dropped}%)`,
        14,
        66
    );

    // Create a simple table without autotable
    let yPosition = 80;
    const lineHeight = 8;
    const pageHeight = 280;
    let currentPage = 1;

    // Add headers
    doc.setFontSize(8);
    doc.setFillColor(34, 139, 34);
    doc.rect(14, yPosition - 5, 180, 8, "F");
    doc.setTextColor(255, 255, 255);
    doc.text("Name", 16, yPosition);
    doc.text("ULI Number", 50, yPosition);
    doc.text("Program", 90, yPosition);
    doc.text("Trainer", 130, yPosition);
    doc.text("Status", 150, yPosition);
    doc.text("Date Enrolled", 170, yPosition);

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

        const name = `${trainee.first_name || ""} ${
            trainee.last_name || ""
        }`.substring(0, 20);
        const uliNumber = (trainee.uli_number || "N/A").substring(0, 15);
        const program = (trainee.program_qualification || "N/A").substring(
            0,
            20
        );
        const trainer =
            trainee.assigned_trainers && trainee.assigned_trainers.length > 0
                ? trainee.assigned_trainers.join(", ").substring(0, 20)
                : "Not Assigned";
        const status = trainee.status;
        const enrollmentDate = formatDate(trainee.actual_enrollment_date);

        doc.text(name, 16, yPosition);
        doc.text(uliNumber, 50, yPosition);
        doc.text(program, 90, yPosition);
        doc.text(trainer, 130, yPosition);
        doc.text(status, 150, yPosition);
        doc.text(enrollmentDate, 170, yPosition);

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
    const filename = `trainees_report_${timestamp}.pdf`;

    // Save the PDF
    doc.save(filename);
};

const exportToExcel = async () => {
    const trainees = filteredTrainees.value;

    // Create a new workbook
    const workbook = new ExcelJS.Workbook();
    const worksheet = workbook.addWorksheet("Training Report");

    // Define headers
    const headers = [
        "Program",
        "Type of Training",
        "RQM/SECTOR",
        "Training Period",
        "Regular Amount",
        "TWSP Billed Amount",
        "TWSP Amount",
        "OR #",
        "Date Collected",
        "Age 18-30",
        "Female",
        "Male",
        "Started",
        "Finished",
        "% Finished",
        "Assessors Name",
        "Assessment Date",
        "Assessment Fee",
        "OR No.",
        "Date Collected",
        "Female",
        "Male",
        "Assessed",
        "",
        "Age 18 - 30 (Assessed)",
        "Absent / Did not take",
        "Not yet Competent",
        "Competent after Assessment",
        "",
        "Employed",
        "",
        "Self Employed",
        "",
        "Unemployed",
        "",
        "Age 18 - 30 (Employed)",
        "Employment Update",
        "",
        "TOTAL EMPLOYED",
        "Remarks",
    ];

    // Add grouped header row first
    const groupedHeaders = [
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "Assessed",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "",
        "Employment during Enrollment",
        "",
        "",
        "",
        "",
        "",
        "Employment after Training",
        "",
        "",
        "",
        "",
    ];
    worksheet.addRow(groupedHeaders);

    // Add main headers to worksheet
    worksheet.addRow(headers);

    // Style the grouped header row (row 1)
    const groupedHeaderRow = worksheet.getRow(1);
    groupedHeaderRow.height = 30;

    groupedHeaderRow.eachCell((cell, colNumber) => {
        if (cell.value) {
            cell.fill = {
                type: "pattern",
                pattern: "solid",
                fgColor: { argb: "FF4F7942" }, // Same green color
            };
            cell.font = {
                color: { argb: "FFFFFFFF" }, // White text
                bold: true,
                size: 12,
            };
            cell.alignment = {
                vertical: "middle",
                horizontal: "center",
                wrapText: true,
            };
            cell.border = {
                top: { style: "thin" },
                left: { style: "thin" },
                bottom: { style: "thin" },
                right: { style: "thin" },
            };
        }
    });

    // Merge cells for grouped headers
    worksheet.mergeCells("P1:AC1"); // Assessed (columns P through AC)
    worksheet.mergeCells("AD1:AI1"); // Employment during Enrollment (columns AD through AI - includes Employed, Self Employed, Unemployed)
    worksheet.mergeCells("AJ1:AM1"); // Employment after Training (columns AJ through AM - includes Age 18-30, Employment Update, TOTAL EMPLOYED, Remarks)

    // Merge cells for two-column spans in row 2
    worksheet.mergeCells("W2:X2"); // Assessed (spans 2 columns)
    worksheet.mergeCells("AB2:AC2"); // Competent after Assessment (spans 2 columns)
    worksheet.mergeCells("AD2:AE2"); // Employed (spans 2 columns)
    worksheet.mergeCells("AF2:AG2"); // Self Employed (spans 2 columns)
    worksheet.mergeCells("AH2:AI2"); // Unemployed (spans 2 columns)
    worksheet.mergeCells("AK2:AL2"); // Employment Update (spans 2 columns)

    // Style the main header row (row 2)
    const headerRow = worksheet.getRow(2);
    headerRow.height = 35;

    headerRow.eachCell((cell, colNumber) => {
        cell.fill = {
            type: "pattern",
            pattern: "solid",
            fgColor: { argb: "FF4F7942" }, // Dark green color matching your image
        };
        cell.font = {
            color: { argb: "FFFFFFFF" }, // White text
            bold: true,
            size: 11,
        };
        cell.alignment = {
            vertical: "middle",
            horizontal: "center",
            wrapText: true,
        };
        cell.border = {
            top: { style: "thin" },
            left: { style: "thin" },
            bottom: { style: "thin" },
            right: { style: "thin" },
        };
    });

    // Set column widths
    const columnWidths = [
        30, 25, 20, 20, 18, 22, 18, 12, 18, 15, 12, 12, 12, 12, 15, 25, 18, 18,
        12, 28, 20, 18, 15, 15, 25, 25, 22, 15, 30, 15, 18, 15, 25, 22, 15, 15,
        15, 15, 15, 15, 15, 25, 22, 18, 25,
    ];

    columnWidths.forEach((width, index) => {
        worksheet.getColumn(index + 1).width = width;
    });

    // Add an empty data row (since current export only shows headers)
    worksheet.addRow(new Array(headers.length).fill(""));

    // Generate filename
    const timestamp = new Date().toISOString().split("T")[0];
    const filename = `training_program_report_${timestamp}.xlsx`;

    // Write to buffer and save
    const buffer = await workbook.xlsx.writeBuffer();
    const blob = new Blob([buffer], {
        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    });
    saveAs(blob, filename);
};

const getStatusColor = (status) => {
    const colors = {
        active: "bg-green-100 text-green-800",
        completed: "bg-blue-100 text-blue-800",
        dropped: "bg-red-100 text-red-800",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};

const getEnrollmentTypeColor = (type) => {
    const colors = {
        regular: "bg-blue-100 text-blue-800",
        scholar: "bg-purple-100 text-purple-800",
    };
    return colors[type] || "bg-blue-100 text-blue-800";
};
</script>

<template>
    <Head title="Trainees Management" />
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
                                Enrollment Monitoring
                            </h3>
                            <!-- <p class="text-sm text-gray-500">Monitor enrollment numbers and track regular vs scholar trainees</p> -->
                        </div>
                        <div class="flex space-x-3">
                            <SecondaryButton
                                v-if="isOfficer"
                                @click="openCreateModal"
                                class="bg-gradient-to-r from-green-600 to-emerald-600 text-white border-none hover:from-green-700 hover:to-emerald-700 transition-all duration-300"
                            >
                                Add New Trainee
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Filters Section -->
                <div
                    class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 border-b border-gray-200"
                >
                    <!-- Search and Toggle Row -->
                    <div class="flex items-end gap-4 mb-4">
                        <div class="flex-1">
                            <InputLabel for="search" value="Search Trainees" />
                            <TextInput
                                id="search"
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by name or email..."
                                class="mt-1 block w-full transition-all duration-300 border-2 border-transparent focus:border-green-500 focus:ring-2 focus:ring-green-200 hover:border-green-300"
                                @keyup.enter="performSearch"
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
                        </div>
                    </div>

                    <!-- Advanced Filters -->
                    <div
                        v-if="showFilters"
                        class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-4 border-t border-gray-200"
                    >
                        <div>
                            <InputLabel
                                for="program-filter"
                                value="Filter by Program"
                            />
                            <select
                                id="program-filter"
                                v-model="selectedProgram"
                                class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            >
                                <option value="">All Programs</option>
                                <option
                                    v-for="program in programs"
                                    :key="program.program_id"
                                    :value="program.program_id"
                                >
                                    {{ program.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel
                                for="enrollment-type-filter"
                                value="Enrollment Type"
                            />
                            <select
                                id="enrollment-type-filter"
                                v-model="selectedEnrollmentType"
                                class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            >
                                <option value="">All Types</option>
                                <option value="regular">Regular</option>
                                <option value="scholar">Scholar</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="status-filter" value="Status" />
                            <select
                                id="status-filter"
                                v-model="selectedStatus"
                                class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            >
                                <option
                                    v-for="option in statusOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="per_page" value="Items per page" />
                            <select
                                id="per_page"
                                v-model="perPage"
                                @change="changePerPage"
                                class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            >
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="20">20</option>
                                <option value="50">50</option>
                            </select>
                        </div>
                    </div>

                    <!-- Date Range Filters -->
                    <div
                        v-if="showFilters"
                        class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4 pt-4 border-t border-gray-200"
                    >
                        <div>
                            <InputLabel
                                for="date-from"
                                value="Date Enrolled From"
                            />
                            <TextInput
                                id="date-from"
                                v-model="dateFrom"
                                type="date"
                                class="mt-1 block w-full"
                            />
                        </div>
                        <div>
                            <InputLabel
                                for="date-to"
                                value="Date Enrolled To"
                            />
                            <TextInput
                                id="date-to"
                                v-model="dateTo"
                                type="date"
                                class="mt-1 block w-full"
                            />
                        </div>
                        <div class="flex items-end gap-2">
                            <SecondaryButton
                                @click="clearFilters"
                                v-if="hasActiveFilters"
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
                </div>

                <!-- Active Filters Display -->
                <div
                    v-if="hasActiveFilters && showFilters"
                    class="flex flex-wrap gap-2 pt-4 border-t border-gray-200"
                >
                    <span class="text-sm text-gray-600">Active filters:</span>
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
                        Program:
                        {{
                            programs.find(
                                (p) => p.program_id == selectedProgram
                            )?.name || selectedProgram
                        }}
                    </span>
                    <span
                        v-if="selectedEnrollmentType"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800"
                    >
                        Type:
                        {{
                            selectedEnrollmentType.charAt(0).toUpperCase() +
                            selectedEnrollmentType.slice(1)
                        }}
                    </span>
                    <span
                        v-if="selectedStatus"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                    >
                        Status:
                        {{
                            statusOptions.find((s) => s.value == selectedStatus)
                                ?.label || selectedStatus
                        }}
                    </span>
                    <span
                        v-if="dateFrom || dateTo"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800"
                    >
                        Date: {{ dateFrom || "Any" }} -
                        {{ dateTo || "Any" }}
                    </span>
                </div>

                <!-- Summary Stats and Export Buttons -->
                <div
                    v-if="hasActiveFilters"
                    class="p-4 border-b border-gray-200 bg-gray-50"
                >
                    <div
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                    >
                        <div class="flex items-center gap-6">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-green-600">
                                    {{ summaryStats.total }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    Total Trainees
                                </p>
                            </div>
                            <div class="text-center">
                                <div class="flex flex-col items-center">
                                    <p
                                        class="text-2xl font-bold text-green-600"
                                    >
                                        {{ summaryStats.active }}
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        Active ({{
                                            traineePercentages.active
                                        }}%)
                                    </p>
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="flex flex-col items-center">
                                    <p class="text-2xl font-bold text-blue-600">
                                        {{ summaryStats.completed }}
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        Completed ({{
                                            traineePercentages.completed
                                        }}%)
                                    </p>
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="flex flex-col items-center">
                                    <p class="text-2xl font-bold text-red-600">
                                        {{ summaryStats.dropped }}
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        Dropped ({{
                                            traineePercentages.dropped
                                        }}%)
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <p class="text-sm text-gray-600">
                                    Showing {{ summaryStats.total }} of
                                    {{
                                        props.trainees?.data?.length ||
                                        props.trainees?.length ||
                                        0
                                    }}
                                    trainees
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
                                        ></path>
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
                                        ></path>
                                    </svg>
                                    Export Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Results Summary -->
                <div
                    v-if="trainees && trainees.data && trainees.data.length > 0"
                    class="px-6 py-3 bg-white border-b border-gray-200"
                >
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Showing
                            <span class="font-medium">{{
                                trainees.from || 0
                            }}</span>
                            to
                            <span class="font-medium">{{
                                trainees.to || 0
                            }}</span>
                            of
                            <span class="font-medium">{{
                                trainees.total || 0
                            }}</span>
                            results
                        </div>
                        <div class="text-sm text-gray-500">
                            Page {{ trainees.current_page || 1 }} of
                            {{ trainees.last_page || 1 }}
                        </div>
                    </div>
                </div>

                <!-- Table Section -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    ULI Number
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Name
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Program
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Trainer
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Batch
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Date Enrolled
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Enrollment History
                                </th>
                                <th
                                    v-if="isOfficer"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="trainee in filteredTrainees"
                                :key="trainee.id"
                                class="hover:bg-gray-50 transition-colors duration-200 group"
                            >
                                <!-- ULI Number -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        {{ trainee.uli_number || "N/A" }}
                                    </div>
                                </td>

                                <!-- Name -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div
                                                class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-medium relative overflow-hidden"
                                            >
                                                <span class="relative z-10">
                                                    {{
                                                        trainee.first_name?.charAt(
                                                            0
                                                        )
                                                    }}{{
                                                        trainee.last_name?.charAt(
                                                            0
                                                        )
                                                    }}
                                                </span>
                                                <div
                                                    class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-500"
                                                ></div>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div
                                                class="text-sm font-medium text-gray-900 group-hover:text-green-600 transition-colors duration-200"
                                            >
                                                {{ trainee.first_name }}
                                                {{ trainee.last_name }}
                                            </div>
                                            <div
                                                class="text-sm text-gray-500 group-hover:text-green-500 transition-colors duration-200"
                                            >
                                                {{ trainee.email }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Program -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{
                                            trainee.program?.name ||
                                            trainee.program_qualification ||
                                            "N/A"
                                        }}
                                    </div>
                                </td>

                                <!-- Trainer -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <span
                                            v-if="
                                                trainee.assigned_trainers &&
                                                trainee.assigned_trainers
                                                    .length > 0
                                            "
                                        >
                                            {{
                                                trainee.assigned_trainers.join(
                                                    ", "
                                                )
                                            }}
                                        </span>
                                        <span v-else> Not Assigned </span>
                                    </div>
                                </td>

                                <!-- Batch -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800"
                                    >
                                        Batch {{ trainee.batch || 1 }}
                                    </span>
                                </td>

                                <!-- Date Enrolled -->
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    {{
                                        trainee.actual_enrollment_date
                                            ? new Date(
                                                  trainee.actual_enrollment_date
                                              ).toLocaleDateString()
                                            : "N/A"
                                    }}
                                </td>

                                <!-- Status -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="getStatusColor(trainee.status)"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full border"
                                    >
                                        {{ trainee.status }}
                                    </span>
                                </td>

                                <!-- Enrollment History -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button
                                        @click="viewEnrollmentHistory(trainee)"
                                        class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800 hover:bg-indigo-200 transition-colors duration-200"
                                    >
                                        <svg
                                            class="w-3 h-3 mr-1"
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
                                        View History
                                    </button>
                                </td>

                                <!-- Actions (for officers only) -->
                                <td
                                    v-if="isOfficer"
                                    class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                                >
                                    <div class="flex justify-end space-x-2">
                                        <button
                                            @click="openEditModal(trainee)"
                                            class="px-3 py-1 text-green-600 hover:text-white hover:bg-green-600 border border-green-600 rounded transition-all duration-300 font-medium"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click="openDeleteModal(trainee)"
                                            class="px-3 py-1 text-red-600 hover:text-white hover:bg-red-600 border border-red-600 rounded transition-all duration-300 font-medium"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <Pagination
                        v-if="
                            trainees &&
                            trainees.data &&
                            trainees.data.length > 0
                        "
                        :data="trainees"
                    />

                    <div
                        v-if="
                            !trainees ||
                            !trainees.data ||
                            trainees.data.length === 0
                        "
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
                                    d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0z"
                                />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">
                                No trainees found
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{
                                    searchQuery
                                        ? "Try adjusting your filters."
                                        : "Get started by enrolling your first trainee."
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal
            :show="showModal"
            @close="showModal = false"
            max-width="2xl"
            :close-on-click-outside="false"
        >
            <div
                class="p-6 bg-white rounded-xl shadow-2xl border border-gray-100"
            >
                <div class="border-b border-gray-200 pb-4 mb-6 relative">
                    <div
                        class="absolute bottom-0 left-0 w-20 h-0.5 bg-gradient-to-r from-green-500 to-emerald-500 rounded"
                    ></div>
                    <h3 class="text-lg font-semibold text-green-900">
                        {{
                            editingTrainee
                                ? "Edit Trainee"
                                : "Create New Trainee"
                        }}
                    </h3>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="first_name" value="First Name *" />
                            <TextInput
                                id="first_name"
                                v-model="form.first_name"
                                type="text"
                                class="mt-1 block w-full border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                                required
                            />
                            <InputError
                                :message="form.errors.first_name"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel for="last_name" value="Last Name *" />
                            <TextInput
                                id="last_name"
                                v-model="form.last_name"
                                type="text"
                                class="mt-1 block w-full border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                                required
                            />
                            <InputError
                                :message="form.errors.last_name"
                                class="mt-2"
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="email" value="Email *" />
                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            required
                        />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="phone" value="Phone" />
                            <TextInput
                                id="phone"
                                v-model="form.phone"
                                type="text"
                                class="mt-1 block w-full border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            />
                            <InputError
                                :message="form.errors.phone"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel
                                for="date_enrolled"
                                value="Date Enrolled *"
                            />
                            <TextInput
                                id="date_enrolled"
                                v-model="form.date_enrolled"
                                type="date"
                                class="mt-1 block w-full border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                                required
                            />
                            <InputError
                                :message="form.errors.date_enrolled"
                                class="mt-2"
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="address" value="Address" />
                        <textarea
                            id="address"
                            v-model="form.address"
                            class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            rows="3"
                        ></textarea>
                        <InputError
                            :message="form.errors.address"
                            class="mt-2"
                        />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="program_id" value="Program *" />
                            <select
                                id="program_id"
                                v-model="form.program_id"
                                class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                                required
                            >
                                <option value="">Select Program</option>
                                <option
                                    v-for="program in programs"
                                    :key="program.program_id"
                                    :value="program.program_id"
                                >
                                    {{ program.name }}
                                </option>
                            </select>
                            <InputError
                                :message="form.errors.program_id"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel
                                for="enrollment_type"
                                value="Enrollment Type *"
                            />
                            <select
                                id="enrollment_type"
                                v-model="form.enrollment_type"
                                class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                                required
                            >
                                <option value="regular">Regular</option>
                                <option value="scholar">Scholar</option>
                            </select>
                            <InputError
                                :message="form.errors.enrollment_type"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel for="status" value="Status" />
                            <select
                                id="status"
                                v-model="form.status"
                                class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="graduated">Graduated</option>
                                <option value="dropped">Dropped</option>
                            </select>
                            <InputError
                                :message="form.errors.status"
                                class="mt-2"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <SecondaryButton
                            @click="showModal = false"
                            class="border-2 border-gray-300 hover:border-green-500 hover:text-green-600 transition-all duration-300"
                        >
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton
                            :disabled="form.processing"
                            class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 transition-all duration-300"
                        >
                            {{
                                form.processing
                                    ? "Saving..."
                                    : editingTrainee
                                    ? "Update"
                                    : "Create"
                            }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <DeleteConfirmationModal
            :show="showDeleteModal"
            :item="deletingTrainee"
            itemType="trainee"
            @close="showDeleteModal = false"
            @confirm="deleteTrainee"
        />
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
