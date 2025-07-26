<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router, usePage } from "@inertiajs/vue3";
import { ref, reactive, computed } from "vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";
import DeleteConfirmationModal from "@/Components/DeleteConfirmationModal.vue";

const props = defineProps({
    trainees: Array,
    programs: Array,
    flash: Object,
});

const user = computed(() => usePage().props.auth.user);
const isOfficer = computed(() => user.value?.role === "officer");

const showModal = ref(false);
const showDeleteModal = ref(false);
const editingTrainee = ref(null);
const deletingTrainee = ref(null);
const searchQuery = ref("");
const selectedProgram = ref("");
const selectedEnrollmentType = ref("");
const selectedStatus = ref("");
const dateFrom = ref("");
const dateTo = ref("");

const clearFilters = () => {
    searchQuery.value = "";
    selectedProgram.value = "";
    selectedEnrollmentType.value = "";
    selectedStatus.value = "";
    dateFrom.value = "";
    dateTo.value = "";
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
    let filtered = props.trainees || [];

    if (searchQuery.value) {
        filtered = filtered.filter(
            (trainee) =>
                trainee.name
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                trainee.uli_number
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                trainee.email
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase())
        );
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
        filtered = filtered.filter((trainee) => {
            if (selectedEnrollmentType.value === "regular") {
                return !trainee.scholarship_package;
            } else if (selectedEnrollmentType.value === "scholar") {
                return !!trainee.scholarship_package;
            }
            return true;
        });
    }

    if (selectedStatus.value) {
        filtered = filtered.filter(
            (trainee) => trainee.status === selectedStatus.value
        );
    }

    if (dateFrom.value || dateTo.value) {
        filtered = filtered.filter((trainee) => {
            const enrolledDate = trainee.actual_enrollment_date
                ? new Date(trainee.actual_enrollment_date)
                : null;
            const fromDate = dateFrom.value ? new Date(dateFrom.value) : null;
            const toDate = dateTo.value ? new Date(dateTo.value) : null;
            if (!enrolledDate) return false;
            if (fromDate && toDate) {
                return enrolledDate >= fromDate && enrolledDate <= toDate;
            } else if (fromDate) {
                return enrolledDate >= fromDate;
            } else if (toDate) {
                return enrolledDate <= toDate;
            }
            return true;
        });
    }

    return filtered;
});

const statusOptions = [
    { value: "", label: "All Statuses" },
    { value: "active", label: "Active" },
    { value: "graduated", label: "Graduated" },
    { value: "dropped", label: "Dropped" },
    { value: "inactive", label: "Inactive" },
];

const totalTrainees = computed(() => filteredTrainees.value.length);

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

const exportEnrollments = () => {
    // TODO: Implement export functionality
};

const viewEnrollmentHistory = (trainee) => {
    // Navigate to trainee enrollment history page based on user role
    const rolePrefix = isOfficer.value ? "officer" : "admin";
    router.visit(`/${rolePrefix}/trainees/${trainee.id}/enrollment-history`);
};

const getStatusColor = (status) => {
    const colors = {
        active: "bg-green-100 text-green-800",
        inactive: "bg-gray-100 text-gray-800",
        graduated: "bg-blue-100 text-blue-800",
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
                            <SecondaryButton
                                @click="exportEnrollments"
                                class="bg-gradient-to-r from-green-600 to-emerald-600 text-white border-none hover:from-green-700 hover:to-emerald-700 transition-all duration-300"
                            >
                                📄 Export Enrollment Report
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Filters Section -->
                <div
                    class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 border-b border-gray-200"
                >
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="relative">
                            <InputLabel for="search" value="Search Trainees" />
                            <TextInput
                                id="search"
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by name or email..."
                                class="mt-1 block w-full transition-all duration-300 border-2 border-transparent focus:border-green-500 focus:ring-2 focus:ring-green-200 hover:border-green-300"
                            />
                        </div>
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
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
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
                        <div class="flex items-end gap-2 mt-6">
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
                                statusOptions.find(
                                    (s) => s.value == selectedStatus
                                )?.label || selectedStatus
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
                </div>

                <!-- Results Summary -->
                <div class="p-4 border-b border-gray-200 bg-gray-50">
                    <div
                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                    >
                        <div class="flex items-center gap-6">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-green-600">
                                    {{ totalTrainees }}
                                </p>
                                <p class="text-sm text-gray-600">Trainees</p>
                            </div>
                        </div>
                        <div v-if="hasActiveFilters" class="text-right">
                            <p class="text-sm text-gray-600">
                                Showing {{ totalTrainees }} of
                                {{ props.trainees.length }} trainees
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Role-based notification -->
                <div
                    v-if="!isOfficer"
                    class="p-4 bg-yellow-50 border-l-4 border-yellow-400"
                >
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg
                                class="h-5 w-5 text-yellow-400"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Only officers can add, edit, or delete trainees.
                                You have view-only access.
                            </p>
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
                                        {{
                                            trainee.trainer?.full_name ||
                                            trainee.assigned_trainer ||
                                            "Not Assigned"
                                        }}
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
