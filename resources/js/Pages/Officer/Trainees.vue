<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import TraineeRegistrationModal from "@/Components/TraineeRegistrationModal.vue";
import TraineeDetailsModal from "@/Components/TraineeDetailsModal.vue";
import DeleteConfirmationModal from "@/Components/DeleteConfirmationModal.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    trainees: Array,
    courses: Array,
});

// Sample data - replace with real data from backend
const searchQuery = ref("");
const selectedCourse = ref("All Courses");
const showRegistrationModal = ref(false);
const showDetailsModal = ref(false);
const showDeleteModal = ref(false);
const selectedTrainee = ref(null);
const processing = ref(false);

// Process trainees data to match the expected format
const traineesList = ref(
    props.trainees?.map((trainee) => ({
        id: trainee.id, // Keep original ID for key purposes
        uli_number: trainee.uli_number || "Not assigned",
        name: `${trainee.first_name} ${trainee.last_name}`,
        trainer: "Not assigned",
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
        course: trainee.course_qualification,
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

const deleteTrainee = (trainee) => {
    // Double check if trainee can be deleted
    if (!canDeleteTrainee(trainee)) {
        alert(
            `Cannot delete trainee with ${getTraineeStatus(
                trainee
            )} status. Only active trainees can be deleted.`
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

// Helper function to check if trainee can be deleted
const canDeleteTrainee = (trainee) => {
    // Find the actual trainee data from props to get the real status
    const actualTrainee = props.trainees.find((t) => t.id === trainee.id);
    const status = actualTrainee?.status || trainee.status || "active";

    // Only allow deletion for active trainees
    return status === "active";
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

    // Filter by course
    if (selectedCourse.value !== "All Courses") {
        filtered = filtered.filter(
            (trainee) => trainee.course === selectedCourse.value
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
                trainee.course
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
                        >Filter by Course:</span
                    >
                    <select
                        v-model="selectedCourse"
                        class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="All Courses">All Courses</option>
                        <option
                            v-for="course in courses"
                            :key="course.id"
                            :value="course.name"
                        >
                            {{ course.name }}
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
                                    Trainer
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
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                >
                                    {{ trainee.trainer }}
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
                                                ) === 'suspended',
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
                                            class="text-orange-600 hover:text-orange-900 p-1 rounded"
                                            title="Archive"
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
                                                    d="M5 8l4 4 4-4m5-4v18l-5-4-5 4V4z"
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
                                                    d="M5 8l4 4 4-4m5-4v18l-5-4-5 4V4z"
                                                />
                                            </svg>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Trainee Registration Modal -->
        <TraineeRegistrationModal
            :show="showRegistrationModal"
            :courses="courses"
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
            :processing="processing"
            title="Delete Trainee"
            :message="`Are you sure you want to delete ${selectedTrainee?.first_name} ${selectedTrainee?.last_name}? This action cannot be undone.`"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
