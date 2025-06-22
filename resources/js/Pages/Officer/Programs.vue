<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ProgramRegistrationModal from "@/Components/ProgramRegistrationModal.vue";
import ProgramDetailsModal from "@/Components/ProgramDetailsModal.vue";
import DeleteConfirmationModal from "@/Components/DeleteConfirmationModal.vue";
import TrainerAssignmentModal from "@/Components/TrainerAssignmentModal.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    programs: Array,
    trainers: Array,
});

const searchQuery = ref("");
const showRegistrationModal = ref(false);
const showDetailsModal = ref(false);
const showDeleteModal = ref(false);
const showAssignmentModal = ref(false);
const selectedProgram = ref(null);
const processing = ref(false);

// Process programs data to match the expected format
const programsList = ref(
    props.programs?.map((program) => ({
        id: program.program_id,
        program_id: program.program_id,
        name: program.name,
        description: program.description,
        duration: program.duration,
        enrollment_fee: program.enrollment_fee || 0,
        status: program.status,
        enrollments: program.enrollments || 0,
        max_enrollments: program.max_enrollments || 25,
        assigned_trainers: program.assigned_trainers || [],
        start_date: program.start_date,
        end_date: program.end_date,
        prerequisites: program.prerequisites,
    })) || []
);

const addNewProgram = () => {
    showRegistrationModal.value = true;
};

const closeRegistrationModal = () => {
    showRegistrationModal.value = false;
};

const onProgramSubmitted = () => {
    // Refresh the page to show the new program
    window.location.reload();
};

const viewProgram = (program) => {
    selectedProgram.value = program;
    showDetailsModal.value = true;
};

const editProgram = (program) => {
    router.visit(route("officer.programs.edit", program.id));
};

const deleteProgram = (program) => {
    selectedProgram.value = program;
    showDeleteModal.value = true;
};

const closeDetailsModal = () => {
    showDetailsModal.value = false;
    selectedProgram.value = null;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedProgram.value = null;
};

const assignTrainers = (program) => {
    selectedProgram.value = program;
    showAssignmentModal.value = true;
};

const closeAssignmentModal = () => {
    showAssignmentModal.value = false;
    selectedProgram.value = null;
};

const onTrainersAssigned = () => {
    // Refresh the page to show updated trainers
    window.location.reload();
};

const handleEditFromDetails = (program) => {
    closeDetailsModal();
    editProgram(program);
};

const confirmDelete = () => {
    if (!selectedProgram.value) return;

    processing.value = true;

    router.delete(route("officer.programs.destroy", selectedProgram.value.id), {
        onSuccess: () => {
            processing.value = false;
            closeDeleteModal();
            // Refresh to show updated list
            window.location.reload();
        },
        onError: () => {
            processing.value = false;
            alert("Failed to delete program. Please try again.");
        },
    });
};

const getAssignedTrainers = (assignedTrainerIds) => {
    if (!assignedTrainerIds || assignedTrainerIds.length === 0) {
        return "";
    }

    const assignedTrainers = props.trainers.filter((trainer) =>
        assignedTrainerIds.includes(trainer.id)
    );

    if (assignedTrainers.length === 0) {
        return "";
    }

    if (assignedTrainers.length === 1) {
        return assignedTrainers[0].name;
    }

    return `${assignedTrainers[0].name} +${assignedTrainers.length - 1} more`;
};

// Computed property for filtered programs
const filteredPrograms = computed(() => {
    if (!searchQuery.value) {
        return programsList.value;
    }

    return programsList.value.filter(
        (program) =>
            program.name
                .toLowerCase()
                .includes(searchQuery.value.toLowerCase()) ||
            program.description
                .toLowerCase()
                .includes(searchQuery.value.toLowerCase()) ||
            getAssignedTrainers(program.assigned_trainers)
                .toLowerCase()
                .includes(searchQuery.value.toLowerCase())
    );
});

const formatCurrency = (amount) => {
    if (!amount || amount === 0) return "Free";
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(amount);
};

const getStatusColor = (status) => {
    switch (status) {
        case "active":
            return "bg-green-100 text-green-800";
        case "inactive":
            return "bg-gray-100 text-gray-800";
        case "completed":
            return "bg-blue-100 text-blue-800";
        default:
            return "bg-gray-100 text-gray-800";
    }
};
</script>

<template>
    <Head title="Programs" />

    <AuthenticatedLayout>
        <div class="p-8">
            <!-- Header Section -->
            <div class="flex justify-between items-center mb-8 animate-fade-in">
                <h1 class="text-3xl font-bold text-gray-900">
                    Programs Management
                </h1>
                <div class="flex gap-3">
                    <button
                        @click="addNewProgram"
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
                        Add New Program
                    </button>
                </div>
            </div>

            <!-- Filters and Search Section -->
            <div
                class="bg-white rounded-lg shadow-sm border p-6 mb-6 animate-fade-in"
            >
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">
                        All Programs ({{ filteredPrograms.length }})
                    </h2>
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search programs..."
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
            </div>

            <!-- Programs Table -->
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
                                    Name
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Trainers
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Duration
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Enrollment Fee
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Enrollments
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Status
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
                                v-for="program in filteredPrograms"
                                :key="program.id"
                                class="hover:bg-gray-50"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{ program.name }}
                                        </div>
                                        <div
                                            class="text-sm text-gray-500 max-w-xs truncate"
                                        >
                                            {{
                                                program.description ||
                                                "No description"
                                            }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <div
                                            v-if="
                                                program.assigned_trainers &&
                                                program.assigned_trainers
                                                    .length > 0
                                            "
                                        >
                                            {{
                                                getAssignedTrainers(
                                                    program.assigned_trainers
                                                )
                                            }}
                                        </div>
                                        <button
                                            v-else
                                            @click="assignTrainers(program)"
                                            class="px-3 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors"
                                            title="Assign Trainers"
                                        >
                                            Assign
                                        </button>
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    {{ program.duration }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    <span
                                        :class="{
                                            'text-green-600 font-medium':
                                                program.enrollment_fee === 0,
                                            'text-gray-900':
                                                program.enrollment_fee > 0,
                                        }"
                                    >
                                        {{
                                            formatCurrency(
                                                program.enrollment_fee
                                            )
                                        }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        {{ program.enrollments || 0 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="getStatusColor(program.status)"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{
                                            program.status
                                                ? program.status
                                                      .charAt(0)
                                                      .toUpperCase() +
                                                  program.status.slice(1)
                                                : "Active"
                                        }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium"
                                >
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="viewProgram(program)"
                                            class="text-blue-600 hover:text-blue-900 p-1 rounded"
                                            title="View Details"
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
                                            @click="editProgram(program)"
                                            class="text-yellow-600 hover:text-yellow-900 p-1 rounded"
                                            title="Edit Program"
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
                                            @click="deleteProgram(program)"
                                            class="text-red-600 hover:text-red-900 p-1 rounded"
                                            title="Delete Program"
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
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div
                    v-if="filteredPrograms.length === 0"
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
                            d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"
                        />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">
                        No programs found
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{
                            searchQuery
                                ? "Try adjusting your search terms."
                                : "Get started by creating a new program."
                        }}
                    </p>
                    <div class="mt-6" v-if="!searchQuery">
                        <button
                            @click="addNewProgram"
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
                            Add Program
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <ProgramRegistrationModal
            :show="showRegistrationModal"
            @close="closeRegistrationModal"
            @submitted="onProgramSubmitted"
        />

        <ProgramDetailsModal
            :show="showDetailsModal"
            :program="selectedProgram"
            :trainers="trainers"
            @close="closeDetailsModal"
            @edit="handleEditFromDetails"
        />

        <DeleteConfirmationModal
            :show="showDeleteModal"
            title="Delete Program"
            :message="`Are you sure you want to delete the program '${selectedProgram?.name}'? This action cannot be undone.`"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
            :processing="processing"
        />

        <TrainerAssignmentModal
            :show="showAssignmentModal"
            :program="selectedProgram"
            :trainers="trainers"
            @close="closeAssignmentModal"
            @assigned="onTrainersAssigned"
        />
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
