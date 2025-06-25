<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import TraineeRegistrationModal from "@/Components/TraineeRegistrationModal.vue";
import TraineeDetailsModal from "@/Components/TraineeDetailsModal.vue";
import DeleteConfirmationModal from "@/Components/DeleteConfirmationModal.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    trainees: Array,
    programs: Array,
});

// Sample data - replace with real data from backend
const searchQuery = ref("");
const selectedProgram = ref("All Programs");
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

// Process trainees data to match the expected format
const traineesList = ref(
    props.trainees?.map((trainee) => ({
        id: trainee.id, // Keep original ID for key purposes
        uli_number: trainee.uli_number || "Not assigned",
        name: `${trainee.first_name} ${trainee.last_name}`,
        trainer: getTrainerNames(trainee.assigned_trainers),
        enrollmentDate: trainee.entry_date
            ? new Date(trainee.entry_date).toLocaleDateString()
            : new Date().toLocaleDateString(),
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
    })) || []
);

const exportData = () => {
    // Export functionality
    console.log("Exporting data...");
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
    const actualTrainee = props.trainees.find((t) => t.id === trainee.id);
    selectedTrainee.value = actualTrainee;
    showDetailsModal.value = true;
};

const editTrainee = (trainee) => {
    // For now, we'll reuse the registration modal for editing
    // Find the actual trainee data from props
    const actualTrainee = props.trainees.find((t) => t.id === trainee.id);
    selectedTrainee.value = actualTrainee;
    // TODO: Implement edit modal or redirect to edit page
    router.visit(`/officer/trainees/${trainee.id}/edit`);
};

const viewEnrollmentHistory = (trainee) => {
    // Find the actual trainee data from props
    const actualTrainee = props.trainees.find((t) => t.id === trainee.id);
    if (actualTrainee) {
        router.visit(
            route("officer.trainees.enrollment-history", actualTrainee.id)
        );
    }
};

const deleteTrainee = (trainee) => {
    // Double check if trainee can be deleted
    if (!canDeleteTrainee(trainee)) {
        const actualTrainee = props.trainees.find((t) => t.id === trainee.id);
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
    const actualTrainee = props.trainees.find((t) => t.id === trainee.id);
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
    const actualTrainee = props.trainees.find((t) => t.id === trainee.id);
    const status = actualTrainee?.status || trainee.status || "active";
    const paymentStatus =
        actualTrainee?.payment_status || trainee.payment_status || "unpaid";

    // Officers can change status if:
    // 1. Trainee is active and paid (can mark as completed or dropped)
    // 2. Trainee is pending (can activate if paid)
    return (
        (status === "active" && paymentStatus === "paid") ||
        (status === "pending" && paymentStatus === "paid") ||
        status === "completed" ||
        status === "dropped"
    );
};

// Helper function to check if trainee can be deleted
const canDeleteTrainee = (trainee) => {
    // Find the actual trainee data from props to get the real status
    const actualTrainee = props.trainees.find((t) => t.id === trainee.id);
    const status = actualTrainee?.status || trainee.status || "active";
    const paymentStatus =
        actualTrainee?.payment_status || trainee.payment_status || "unpaid";

    // Cannot delete if status is active OR payment status is paid
    return !(status === "active" || paymentStatus === "paid");
};

// Helper function to get trainee status for display
const getTraineeStatus = (trainee) => {
    const actualTrainee = props.trainees.find((t) => t.id === trainee.id);
    const status = actualTrainee?.status || trainee.status || "active";
    return status.charAt(0).toUpperCase() + status.slice(1);
};

// Helper function to get the actual status value (lowercase)
const getTraineeActualStatus = (trainee) => {
    const actualTrainee = props.trainees.find((t) => t.id === trainee.id);
    return actualTrainee?.status || trainee.status || "active";
};

// Computed property for filtered trainees
const filteredTrainees = computed(() => {
    let filtered = traineesList.value;

    // Filter by program
    if (selectedProgram.value !== "All Programs") {
        filtered = filtered.filter(
            (trainee) => trainee.program === selectedProgram.value
        );
    }

    // Filter by search query
    if (searchQuery.value) {
        filtered = filtered.filter(
            (trainee) =>
                trainee.name
                    .toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                trainee.uli_number
                    .toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                trainee.program
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase())
        );
    }

    return filtered;
});
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
                        @click="exportData"
                        class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors"
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
                                d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"
                            />
                        </svg>
                        Export
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
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">
                        All Trainees ({{ filteredTrainees.length }})
                    </h2>
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search trainees..."
                            class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-80"
                        />
                        <svg
                            class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400"
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
                </div>

                <div class="flex items-center gap-4">
                    <span class="text-sm font-medium text-gray-700"
                        >Filter by Program:</span
                    >
                    <select
                        v-model="selectedProgram"
                        class="border border-gray-300 rounded-lg px-5 py-2 focus:ring-4 focus:ring-blue-500 focus:border-blue-500 w-64"
                    >
                        <option value="All Programs">All Programs</option>
                        <option
                            v-for="program in programs"
                            :key="program.id"
                            :value="program.name"
                        >
                            {{ program.name }}
                        </option>
                    </select>
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
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="viewTrainee(trainee)"
                                            class="text-blue-600 hover:text-blue-900 p-1 rounded"
                                            title="View"
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
                                            class="text-green-600 hover:text-green-900 p-1 rounded"
                                            title="Enrollment History"
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
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                                />
                                            </svg>
                                        </button>
                                        <button
                                            v-if="canChangeStatus(trainee)"
                                            @click="changeStatus(trainee)"
                                            class="text-purple-600 hover:text-purple-900 p-1 rounded"
                                            title="Change Status"
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
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                        </button>
                                        <button
                                            @click="editTrainee(trainee)"
                                            class="text-yellow-600 hover:text-yellow-900 p-1 rounded"
                                            title="Edit"
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
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                />
                                            </svg>
                                        </button>
                                        <button
                                            v-if="canDeleteTrainee(trainee)"
                                            @click="deleteTrainee(trainee)"
                                            class="text-red-600 hover:text-red-900 p-1 rounded"
                                            title="Delete"
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
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                        </button>
                                        <span
                                            v-else
                                            class="text-gray-400 p-1 rounded cursor-not-allowed"
                                            :title="`Cannot delete trainee with ${getTraineeStatus(
                                                trainee
                                            )} status`"
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
