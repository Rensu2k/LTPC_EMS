<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import TraineeRegistrationModal from "@/Components/TraineeRegistrationModal.vue";
import TraineeDetailsModal from "@/Components/TraineeDetailsModal.vue";
import DeleteConfirmationModal from "@/Components/DeleteConfirmationModal.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import Pagination from "@/Components/Pagination.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import ExcelJS from "exceljs";
import { saveAs } from "file-saver";

const props = defineProps({
    trainees: [Object, Array], // Support both pagination object and legacy array
    programs: Array,
    filters: Object,
});

// Initialize from props filters
const searchQuery = ref(props.filters?.search || "");
const selectedProgram = ref(props.filters?.program || "");
const selectedStatus = ref(props.filters?.status || "");
const selectedEnrollmentType = ref(props.filters?.enrollment_type || "");
const dateFrom = ref(props.filters?.date_from || "");
const dateTo = ref(props.filters?.date_to || "");
const perPage = ref(props.filters?.per_page || 20);
const showFilters = ref(false);
const showRegistrationModal = ref(false);
const showDetailsModal = ref(false);
const showDeleteModal = ref(false);
const showStatusModal = ref(false);
const selectedTrainee = ref(null);
const processing = ref(false);
const statusForm = ref({
    status: "",
    processing: false,
});

// Helper function to format trainer names
const getTrainerNames = (assignedTrainers) => {
    if (!assignedTrainers || assignedTrainers.length === 0) {
        return "No trainers assigned";
    }

    if (assignedTrainers.length === 1) {
        return assignedTrainers[0];
    }

    return `${assignedTrainers[0]} +${assignedTrainers.length - 1} more`;
};

// Helper function to get the actual trainees data (handles both paginated and non-paginated)
const getTraineesData = () => {
    return props.trainees?.data || props.trainees || [];
};

// Helper function to find a trainee by ID
const findTraineeById = (id) => {
    const traineesData = getTraineesData();
    return traineesData.find((t) => t.id === id);
};

// Process trainees data to match the expected format
const traineesList = computed(() => {
    const traineesData = props.trainees?.data || props.trainees;

    if (!traineesData || !Array.isArray(traineesData)) {
        return [];
    }

    return traineesData.map((trainee) => ({
        id: trainee.id, // Keep original ID for key purposes
        uli_number: trainee.uli_number || "Not assigned",
        name: `${trainee.first_name} ${trainee.last_name}`,
        trainer: getTrainerNames(trainee.assigned_trainers),
        enrollmentDate: trainee.entry_date
            ? new Date(trainee.entry_date).toLocaleDateString()
            : "Not assigned",
        status: trainee.status
            ? trainee.status.charAt(0).toUpperCase() + trainee.status.slice(1)
            : "Active",
        payment: trainee.payment_status
            ? trainee.payment_status.charAt(0).toUpperCase() +
              trainee.payment_status.slice(1)
            : "Unpaid",
        avatar: `${trainee.first_name?.charAt(0) || ""}${
            trainee.last_name?.charAt(0) || ""
        }`,
        program: trainee.program_qualification,
    }));
});

const exportToExcel = async () => {
    const traineesData = props.trainees?.data || props.trainees || [];

    // Create a new workbook
    const workbook = new ExcelJS.Workbook();
    const worksheet = workbook.addWorksheet("Trainees Report");

    // Add title section
    const titleRow1 = worksheet.addRow(["Republic of the Philippines"]);
    titleRow1.font = { name: "Times New Roman", size: 15 };
    titleRow1.alignment = { horizontal: "center", vertical: "middle" };
    worksheet.mergeCells(1, 1, 1, 20);

    const titleRow2 = worksheet.addRow([
        "PUBLIC EMPLOYMENT SKILLS AND DEVELOPMENT OFFICE (PESDO)",
    ]);
    titleRow2.font = { name: "Times New Roman", bold: true, size: 20 };
    titleRow2.alignment = { horizontal: "center", vertical: "middle" };
    worksheet.mergeCells(2, 1, 2, 20);

    const titleRow3 = worksheet.addRow(["Surigao City, Surigao Del Norte"]);
    titleRow3.font = { name: "Times New Roman", size: 15 };
    titleRow3.alignment = { horizontal: "center", vertical: "middle" };
    worksheet.mergeCells(3, 1, 3, 20);

    // Add empty row
    worksheet.addRow([]);

    // Get current year
    const currentYear = new Date().getFullYear();
    const programName = selectedProgram.value || "All Programs";

    const titleRow4 = worksheet.addRow([
        `PROFILE OF TRAINEES FOR ${programName.toUpperCase()}`,
    ]);
    titleRow4.font = { bold: true, size: 15 };
    titleRow4.alignment = { horizontal: "center", vertical: "middle" };
    worksheet.mergeCells(5, 1, 5, 20);

    const titleRow5 = worksheet.addRow([`C.Y. ${currentYear}`]);
    titleRow5.font = { bold: true, size: 15 };
    titleRow5.alignment = { horizontal: "center", vertical: "middle" };
    worksheet.mergeCells(6, 1, 6, 20);

    // Add empty row
    worksheet.addRow([]);

    // Define headers
    const headers = [
        "No.",
        "Last Name",
        "First Name",
        "Middle Name",
        "Extension Name",
        "Contact Number",
        "Barangay",
        "Municipality - City",
        "Sex",
        "Date of Birth",
        "Age",
        "Civil Status",
        "Highest Grade Completed",
        "Training Status",
        "Type of Scholarship",
        "Date Started",
        "Date Finished",
        "Date Assessed",
        "Assessment Results",
        "Employment Status Before the Training",
    ];

    // Add header row (now at row 8)
    worksheet.addRow(headers);

    // Style the header row
    const headerRow = worksheet.getRow(8);
    headerRow.height = 25;

    headerRow.eachCell((cell, colNumber) => {
        cell.fill = {
            type: "pattern",
            pattern: "solid",
            fgColor: { argb: "FF4F7942" },
        };
        cell.font = {
            color: { argb: "FFFFFFFF" },
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
        8, // No.
        20, // Last Name
        20, // First Name
        20, // Middle Name
        15, // Extension Name
        18, // Contact Number
        20, // Barangay
        25, // Municipality - City
        12, // Sex
        18, // Date of Birth
        8, // Age
        15, // Civil Status
        25, // Highest Grade Completed
        18, // Training Status
        20, // Type of Scholarship
        18, // Date Started
        18, // Date Finished
        18, // Date Assessed
        20, // Assessment Results
        30, // Employment Status Before the Training
    ];

    columnWidths.forEach((width, index) => {
        worksheet.getColumn(index + 1).width = width;
    });

    // Helper function to format date
    const formatDate = (date) => {
        if (!date) return "N/A";
        const d = new Date(date);
        const month = String(d.getMonth() + 1).padStart(2, "0");
        const day = String(d.getDate()).padStart(2, "0");
        const year = d.getFullYear();
        return `${month}/${day}/${year}`;
    };

    // Helper function to get date of birth
    const getDateOfBirth = (trainee) => {
        if (trainee.birth_month && trainee.birth_day && trainee.birth_year) {
            return `${String(trainee.birth_month).padStart(2, "0")}/${String(
                trainee.birth_day
            ).padStart(2, "0")}/${trainee.birth_year}`;
        }
        return "N/A";
    };

    // Helper function to get highest education
    const getHighestEducation = (education) => {
        if (!education) {
            return "N/A";
        }

        // Education mapping
        const educationMap = {
            elementary_graduate: "Elementary Graduate",
            elementary_undergraduate: "Elementary Undergraduate",
            junior_high_graduate: "Junior High School Graduate",
            junior_high_undergraduate: "Junior High School Undergraduate",
            senior_high_graduate: "Senior High School Graduate",
            senior_high_undergraduate: "Senior High School Undergraduate",
            vocational_graduate: "Vocational Graduate",
            vocational_undergraduate: "Vocational Undergraduate",
            college_graduate: "College Graduate",
            college_undergraduate: "College Undergraduate",
            post_graduate: "Post Graduate",
        };

        // If education is a string (e.g., 'college_undergraduate')
        if (typeof education === "string") {
            return (
                educationMap[education] ||
                education
                    .replace(/_/g, " ")
                    .replace(/\b\w/g, (l) => l.toUpperCase())
            );
        }

        // If education is an array
        if (Array.isArray(education) && education.length > 0) {
            // Check if array contains strings (e.g., ['college_undergraduate'])
            if (typeof education[0] === "string") {
                // Get the highest education from the array
                const educationLevels = {
                    elementary: 1,
                    junior_high: 2,
                    senior_high: 3,
                    vocational: 3,
                    college: 4,
                    post_graduate: 5,
                };

                const highest = education.reduce((prev, curr) => {
                    const prevLevel = Object.keys(educationLevels).find((key) =>
                        prev.includes(key)
                    );
                    const currLevel = Object.keys(educationLevels).find((key) =>
                        curr.includes(key)
                    );
                    const prevScore = educationLevels[prevLevel] || 0;
                    const currScore = educationLevels[currLevel] || 0;
                    return currScore > prevScore ? curr : prev;
                });

                return (
                    educationMap[highest] ||
                    highest
                        .replace(/_/g, " ")
                        .replace(/\b\w/g, (l) => l.toUpperCase())
                );
            }

            // If education is an array of objects (legacy format)
            const educationLevels = {
                elementary: 1,
                junior_high: 2,
                senior_high: 3,
                vocational: 3,
                college: 4,
                post_graduate: 5,
            };

            const highest = education.reduce((prev, curr) => {
                const prevLevel = educationLevels[prev.level] || 0;
                const currLevel = educationLevels[curr.level] || 0;
                return currLevel > prevLevel ? curr : prev;
            });

            const levelNames = {
                elementary: "Elementary",
                junior_high: "Junior High School",
                senior_high: "Senior High School",
                vocational: "Vocational",
                college: "College",
                post_graduate: "Post Graduate",
            };

            const statusLabels = {
                graduated: "Graduate",
                undergraduate: "Undergraduate",
                ongoing: "Ongoing",
                completed: "Graduate",
                not_completed: "Undergraduate",
            };

            const levelName = levelNames[highest.level] || highest.level;
            const statusLabel = statusLabels[highest.status] || highest.status;

            if (
                highest.status &&
                highest.status !== "graduated" &&
                highest.status !== "completed"
            ) {
                return `${levelName} ${statusLabel}`;
            }

            return `${levelName} Graduate`;
        }

        return "N/A";
    };

    // Add data rows
    traineesData.forEach((trainee, index) => {
        const row = [
            index + 1, // No.
            trainee.last_name || "N/A",
            trainee.first_name || "N/A",
            trainee.middle_name || "N/A",
            trainee.extension || "N/A",
            trainee.contact_number || "N/A",
            trainee.barangay || "N/A",
            trainee.city_municipality || "N/A",
            trainee.sex
                ? trainee.sex.charAt(0).toUpperCase() + trainee.sex.slice(1)
                : "N/A",
            getDateOfBirth(trainee),
            trainee.age || "N/A",
            trainee.civil_status
                ? trainee.civil_status
                      .replace(/_/g, " ")
                      .replace(/\b\w/g, (l) => l.toUpperCase())
                : "N/A",
            getHighestEducation(trainee.education),
            trainee.status
                ? trainee.status.charAt(0).toUpperCase() +
                  trainee.status.slice(1)
                : "N/A",
            trainee.scholarship_package || "N/A",
            formatDate(trainee.date_started), // Date Started from enrollment
            formatDate(trainee.date_ended), // Date Finished (End Date) from enrollment
            formatDate(trainee.latest_assessment_date), // Date Assessed - latest assessment
            trainee.latest_assessment_result
                ? trainee.latest_assessment_result
                      .replace(/_/g, " ")
                      .replace(/\b\w/g, (l) => l.toUpperCase())
                : "N/A", // Assessment Results
            trainee.employment_status
                ? trainee.employment_status
                      .replace(/_/g, " ")
                      .replace(/\b\w/g, (l) => l.toUpperCase())
                : "N/A",
        ];

        const dataRow = worksheet.addRow(row);

        // Style data rows
        dataRow.eachCell((cell) => {
            cell.alignment = {
                vertical: "middle",
                horizontal: "left",
            };
            cell.border = {
                top: { style: "thin" },
                left: { style: "thin" },
                bottom: { style: "thin" },
                right: { style: "thin" },
            };
        });

        // Center align specific columns
        dataRow.getCell(1).alignment = {
            vertical: "middle",
            horizontal: "center",
        }; // No.
        dataRow.getCell(9).alignment = {
            vertical: "middle",
            horizontal: "center",
        }; // Sex
        dataRow.getCell(10).alignment = {
            vertical: "middle",
            horizontal: "center",
        }; // Date of Birth
        dataRow.getCell(11).alignment = {
            vertical: "middle",
            horizontal: "center",
        }; // Age
    });

    // Generate filename with current filters info
    const timestamp = new Date().toISOString().split("T")[0];
    let filename = `trainees_report_${timestamp}`;

    if (selectedProgram.value) {
        filename += `_${selectedProgram.value.replace(/\s+/g, "_")}`;
    }
    if (selectedStatus.value) {
        filename += `_${selectedStatus.value}`;
    }
    if (selectedEnrollmentType.value) {
        filename += `_${selectedEnrollmentType.value}`;
    }

    filename += ".xlsx";

    // Write to buffer and save
    const buffer = await workbook.xlsx.writeBuffer();
    const blob = new Blob([buffer], {
        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    });
    saveAs(blob, filename);
};

const registerTrainee = () => {
    showRegistrationModal.value = true;
};

const closeRegistrationModal = () => {
    showRegistrationModal.value = false;
};

const onTraineeSubmitted = () => {
    // Refresh the page to show the new trainee
    window.location.reload();
};

const viewTrainee = (trainee) => {
    // Find the actual trainee data from props
    const actualTrainee = findTraineeById(trainee.id);
    selectedTrainee.value = actualTrainee;
    showDetailsModal.value = true;
};

const editTrainee = (trainee) => {
    // For now, we'll reuse the registration modal for editing
    // Find the actual trainee data from props
    const actualTrainee = findTraineeById(trainee.id);
    selectedTrainee.value = actualTrainee;
    // TODO: Implement edit modal or redirect to edit page
    router.visit(`/officer/trainees/${trainee.id}/edit`);
};

const viewEnrollmentHistory = (trainee) => {
    // Find the actual trainee data from props
    const actualTrainee = findTraineeById(trainee.id);
    if (actualTrainee) {
        router.visit(
            route("officer.trainees.enrollment-history", actualTrainee.id)
        );
    }
};

const deleteTrainee = (trainee) => {
    // Double check if trainee can be deleted
    if (!canDeleteTrainee(trainee)) {
        const actualTrainee = findTraineeById(trainee.id);
        const status = actualTrainee?.status || trainee.status || "active";
        const paymentStatus =
            actualTrainee?.payment_status || trainee.payment_status || "unpaid";
        alert(
            `Cannot delete trainee. Trainees with active status or paid payment status cannot be deleted. Current status: ${status}, Payment: ${paymentStatus}.`
        );
        return;
    }

    selectedTrainee.value = trainee;
    showDeleteModal.value = true;
};

const closeDetailsModal = () => {
    showDetailsModal.value = false;
    selectedTrainee.value = null;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedTrainee.value = null;
};

const confirmDelete = () => {
    if (!selectedTrainee.value) return;

    processing.value = true;
    router.delete(`/officer/trainees/${selectedTrainee.value.id}`, {
        onSuccess: () => {
            processing.value = false;
            closeDeleteModal();
            // Refresh to show updated list
            window.location.reload();
        },
        onError: () => {
            processing.value = false;
        },
    });
};

const handleEditFromDetails = (trainee) => {
    closeDetailsModal();
    editTrainee({ id: trainee.id });
};

// Status change functionality
const changeStatus = (trainee) => {
    const actualTrainee = findTraineeById(trainee.id);
    selectedTrainee.value = actualTrainee;
    statusForm.value.status = actualTrainee.status;
    showStatusModal.value = true;
};

const closeStatusModal = () => {
    showStatusModal.value = false;
    selectedTrainee.value = null;
    statusForm.value.status = "";
};

const confirmStatusChange = () => {
    if (!selectedTrainee.value || !statusForm.value.status) return;

    statusForm.value.processing = true;
    router.patch(
        `/officer/trainees/${selectedTrainee.value.id}/status`,
        {
            status: statusForm.value.status,
        },
        {
            onSuccess: () => {
                statusForm.value.processing = false;
                closeStatusModal();
                // Refresh to show updated status
                window.location.reload();
            },
            onError: () => {
                statusForm.value.processing = false;
            },
        }
    );
};

// Helper function to check if trainee status can be changed
const canChangeStatus = (trainee) => {
    const actualTrainee = findTraineeById(trainee.id);
    const status = actualTrainee?.status || trainee.status || "active";
    const paymentStatus =
        actualTrainee?.payment_status || trainee.payment_status || "unpaid";

    // Once completed or dropped, do not allow any further status changes
    if (status === "completed" || status === "dropped") {
        return false;
    }

    // Officers can change status if:
    // 1. Trainee is active and paid (can mark as completed or dropped)
    // 2. Trainee is pending and paid (can activate)
    return (
        (status === "active" && paymentStatus === "paid") ||
        (status === "pending" && paymentStatus === "paid")
    );
};

// Helper function to check if trainee can be deleted
const canDeleteTrainee = (trainee) => {
    // Find the actual trainee data from props to get the real status
    const actualTrainee = findTraineeById(trainee.id);
    const status = actualTrainee?.status || trainee.status || "active";
    const paymentStatus =
        actualTrainee?.payment_status || trainee.payment_status || "unpaid";

    // Cannot delete if status is active OR payment status is paid
    return !(status === "active" || paymentStatus === "paid");
};

// Helper function to get trainee status for display
const getTraineeStatus = (trainee) => {
    const actualTrainee = findTraineeById(trainee.id);
    const status = actualTrainee?.status || trainee.status || "active";
    return status.charAt(0).toUpperCase() + status.slice(1);
};

// Helper function to get the actual status value (lowercase)
const getTraineeActualStatus = (trainee) => {
    const actualTrainee = findTraineeById(trainee.id);
    return actualTrainee?.status || trainee.status || "active";
};

// Clear all filters
const clearFilters = () => {
    searchQuery.value = "";
    selectedProgram.value = "";
    selectedStatus.value = "";
    selectedEnrollmentType.value = "";
    dateFrom.value = "";
    dateTo.value = "";
    performSearch();
};

// Check if any filters are active
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

// Server-side filtering is now handled by the backend
const filteredTrainees = computed(() => traineesList.value);

// Status options
const statusOptions = [
    { value: "", label: "All Statuses" },
    { value: "active", label: "Active" },
    { value: "completed", label: "Completed" },
    { value: "dropped", label: "Dropped" },
];

// Perform search with all filters
const performSearch = () => {
    router.get(
        route("officer.trainees"),
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

// Change per page functionality
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
</script>

<template>
    <Head title="Trainees" />

    <AuthenticatedLayout>
        <div class="p-8">
            <!-- Header Section -->
            <div class="flex justify-between items-center mb-8 animate-fade-in">
                <h1 class="text-3xl font-bold text-gray-900">
                    Trainees Management
                </h1>
                <div class="flex gap-3">
                    <button
                        v-if="hasActiveFilters"
                        @click="exportToExcel"
                        class="flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors"
                        title="Export filtered trainees to Excel"
                    >
                        <svg
                            class="h-5 w-5"
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
                        Export to Excel
                    </button>
                    <button
                        @click="registerTrainee"
                        class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                            />
                        </svg>
                        Register Trainee
                    </button>
                </div>
            </div>

            <!-- Filters and Search Section -->
            <div
                class="bg-white rounded-lg shadow-sm border p-6 mb-6 animate-fade-in"
            >
                <!-- Search and Toggle Row -->
                <div class="flex items-end gap-4 mb-4">
                    <div class="flex-1">
                        <InputLabel for="search" value="Search Trainees" />
                        <TextInput
                            id="search"
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search by name, ULI number, or email..."
                            class="mt-1 block w-full transition-all duration-300 border-2 border-transparent focus:border-blue-500 focus:ring-2 focus:ring-blue-200 hover:border-blue-300"
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
                            class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-300"
                        >
                            <option value="">All Programs</option>
                            <option
                                v-for="program in programs"
                                :key="program.program_id"
                                :value="program.name"
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
                            class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-300"
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
                            class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-300"
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
                            class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-300"
                        >
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
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
                        <InputLabel for="date-to" value="Date Enrolled To" />
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

                <!-- Active Filters Display -->
                <div
                    v-if="hasActiveFilters && showFilters"
                    class="flex flex-wrap gap-2 pt-4 border-t border-gray-200 mt-4"
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
                        Program: {{ selectedProgram }}
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
                        Date: {{ dateFrom || "Any" }} - {{ dateTo || "Any" }}
                    </span>
                </div>
            </div>

            <!-- Trainees Table -->
            <div
                class="bg-white rounded-lg shadow-sm border overflow-hidden animate-fade-in"
            >
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    ULI Number
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Name
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Program
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Trainer
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Batch
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Enrollment Date
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Payment
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
                                v-for="trainee in filteredTrainees"
                                :key="trainee.id"
                                class="hover:bg-gray-50"
                            >
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                >
                                    {{ trainee.uli_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div
                                            class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center text-white font-semibold mr-3"
                                        >
                                            {{ trainee.avatar }}
                                        </div>
                                        <div
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{ trainee.name }}
                                        </div>
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    {{ trainee.program }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                >
                                    {{ trainee.trainer }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800"
                                    >
                                        Batch {{ trainee.batch || 1 }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    {{ trainee.enrollmentDate }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="{
                                            'bg-green-100 text-green-800':
                                                getTraineeActualStatus(
                                                    trainee
                                                ) === 'active',
                                            'bg-blue-100 text-blue-800':
                                                getTraineeActualStatus(
                                                    trainee
                                                ) === 'completed',
                                            'bg-red-100 text-red-800':
                                                getTraineeActualStatus(
                                                    trainee
                                                ) === 'dropped',
                                            'bg-yellow-100 text-yellow-800':
                                                getTraineeActualStatus(
                                                    trainee
                                                ) === 'pending',
                                        }"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{ getTraineeStatus(trainee) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="{
                                            'bg-green-100 text-green-800':
                                                trainee.payment === 'Paid',
                                            'bg-red-100 text-red-800':
                                                trainee.payment === 'Unpaid',
                                        }"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{ trainee.payment }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium"
                                >
                                    <div class="flex items-center gap-3">
                                        <button
                                            @click="viewTrainee(trainee)"
                                            class="text-blue-600 hover:text-blue-900 p-2 rounded"
                                            title="View"
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
                                                />
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                                />
                                            </svg>
                                        </button>
                                        <button
                                            @click="
                                                viewEnrollmentHistory(trainee)
                                            "
                                            class="text-green-600 hover:text-green-900 p-2 rounded"
                                            title="Enrollment History"
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
                                                />
                                            </svg>
                                        </button>
                                        <button
                                            v-if="canChangeStatus(trainee)"
                                            @click="changeStatus(trainee)"
                                            class="text-purple-600 hover:text-purple-900 p-2 rounded"
                                            title="Change Status"
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
                                                />
                                            </svg>
                                        </button>
                                        <button
                                            @click="editTrainee(trainee)"
                                            class="text-yellow-600 hover:text-yellow-900 p-2 rounded"
                                            title="Edit"
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
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                />
                                            </svg>
                                        </button>
                                        <button
                                            v-if="canDeleteTrainee(trainee)"
                                            @click="deleteTrainee(trainee)"
                                            class="text-red-600 hover:text-red-900 p-2 rounded"
                                            title="Delete"
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
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                        </button>
                                        <span
                                            v-else
                                            class="text-gray-400 p-2 rounded cursor-not-allowed"
                                            :title="`Cannot delete trainee with ${getTraineeStatus(
                                                trainee
                                            )} status`"
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
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div
                    v-if="filteredTrainees.length === 0"
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
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"
                        />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">
                        No trainees found
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{
                            searchQuery
                                ? "Try adjusting your search terms."
                                : "Get started by adding a new trainee."
                        }}
                    </p>
                    <div class="mt-6" v-if="!searchQuery">
                        <button
                            @click="registerTrainee"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                        >
                            <svg
                                class="-ml-1 mr-2 h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                />
                            </svg>
                            Add Trainee
                        </button>
                    </div>
                </div>

                <!-- Pagination -->
                <Pagination v-if="traineesList.length > 0" :data="trainees" />
            </div>
        </div>

        <!-- Trainee Registration Modal -->
        <TraineeRegistrationModal
            :show="showRegistrationModal"
            :programs="programs"
            @close="closeRegistrationModal"
            @submitted="onTraineeSubmitted"
        />

        <!-- Trainee Details Modal -->
        <TraineeDetailsModal
            :show="showDetailsModal"
            :trainee="selectedTrainee"
            @close="closeDetailsModal"
            @edit="handleEditFromDetails"
        />

        <!-- Delete Confirmation Modal -->
        <DeleteConfirmationModal
            :show="showDeleteModal"
            :item="selectedTrainee"
            itemType="trainee"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />

        <!-- Status Change Modal -->
        <Modal :show="showStatusModal" @close="closeStatusModal">
            <div class="p-6">
                <h2 class="text-lg font-semibold mb-4">
                    Change Trainee Status
                </h2>

                <div v-if="selectedTrainee" class="mb-4">
                    <p class="text-sm text-gray-600 mb-2">
                        Trainee:
                        <strong
                            >{{ selectedTrainee.first_name }}
                            {{ selectedTrainee.last_name }}</strong
                        >
                    </p>
                    <p class="text-sm text-gray-600 mb-4">
                        Current Status:
                        <span
                            :class="{
                                'text-green-600':
                                    selectedTrainee.status === 'active',
                                'text-blue-600':
                                    selectedTrainee.status === 'completed',
                                'text-red-600':
                                    selectedTrainee.status === 'dropped',
                                'text-yellow-600':
                                    selectedTrainee.status === 'pending',
                            }"
                            class="font-medium"
                        >
                            {{
                                selectedTrainee.status.charAt(0).toUpperCase() +
                                selectedTrainee.status.slice(1)
                            }}
                        </span>
                    </p>
                </div>

                <div class="mb-4">
                    <label
                        for="new_status"
                        class="block text-sm font-medium text-gray-700 mb-2"
                    >
                        New Status
                    </label>
                    <select
                        id="new_status"
                        v-model="statusForm.status"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option value="pending">Pending (Not Enrolled)</option>
                        <option value="active">Active (Enrolled)</option>
                        <option value="completed">Completed</option>
                        <option value="dropped">Dropped</option>
                    </select>
                </div>

                <!-- Status Change Guidelines -->
                <div
                    class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4"
                >
                    <p class="text-sm text-blue-700 font-medium mb-1">
                        📋 Status Change Guidelines:
                    </p>
                    <ul class="text-xs text-blue-600 space-y-1">
                        <li>
                            • <strong>Active:</strong> Trainee is officially
                            enrolled and attending classes
                        </li>
                        <li>
                            • <strong>Completed:</strong> Trainee has
                            successfully finished the program
                        </li>
                        <li>
                            • <strong>Dropped:</strong> Trainee has withdrawn or
                            been removed from the program
                        </li>
                        <li>
                            • <strong>Pending:</strong> Trainee is registered
                            but not yet enrolled
                        </li>
                    </ul>
                </div>

                <div class="flex justify-end gap-4">
                    <SecondaryButton
                        @click="closeStatusModal"
                        :disabled="statusForm.processing"
                    >
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton
                        @click="confirmStatusChange"
                        :disabled="statusForm.processing || !statusForm.status"
                        class="bg-purple-600 hover:bg-purple-700"
                    >
                        <span v-if="statusForm.processing">Updating...</span>
                        <span v-else>Update Status</span>
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
