<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    programs: Array,
    trainers: Array,
    flash: Object,
});

const user = computed(() => usePage().props.auth.user);
const isOfficer = computed(() => user.value?.role === "officer");
const isAdmin = computed(() => user.value?.role === "admin");

// Determine the correct API endpoint based on user role
const apiEndpoint = computed(() => {
    if (isOfficer.value) {
        return "/officer/programs";
    } else if (isAdmin.value) {
        return "/admin/programs";
    }
    return "/admin/programs"; // fallback
});

const showModal = ref(false);
const showDeleteModal = ref(false);
const editingProgram = ref(null);
const deletingProgram = ref(null);
const searchQuery = ref("");

const form = useForm({
    program_id: "",
    name: "",
    description: "",
    duration: "",
    prerequisites: "",
    max_students: "",
    enrollment_fee: "",
    status: "active",
});

const filteredPrograms = computed(() => {
    let filtered = props.programs || [];

    if (searchQuery.value) {
        filtered = filtered.filter(
            (program) =>
                program.name
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                getAssignedTrainers(program.assigned_trainers)
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase())
        );
    }

    return filtered;
});

const openCreateModal = () => {
    if (!isOfficer.value && !isAdmin.value) return;
    form.reset();
    editingProgram.value = null;
    showModal.value = true;
};

const openEditModal = (program) => {
    if (!isOfficer.value && !isAdmin.value) return;
    editingProgram.value = program;
    form.program_id = program.program_id;
    form.name = program.name;
    form.description = program.description;
    form.duration = program.duration;
    form.prerequisites = program.prerequisites;
    form.max_students = program.max_students;
    form.enrollment_fee = program.enrollment_fee;
    showModal.value = true;
};

const openDeleteModal = (program) => {
    if (!isOfficer.value && !isAdmin.value) return;
    deletingProgram.value = program;
    showDeleteModal.value = true;
};

const submitForm = () => {
    if (!isOfficer.value && !isAdmin.value) return;
    if (editingProgram.value) {
        form.put(`${apiEndpoint.value}/${editingProgram.value.program_id}`, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post(apiEndpoint.value, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const deleteProgram = () => {
    if (!isOfficer.value && !isAdmin.value) return;
    router.delete(`${apiEndpoint.value}/${deletingProgram.value.program_id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
            deletingProgram.value = null;
        },
    });
};

const formatCurrency = (amount) => {
    if (!amount) return "Free";
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(amount);
};

const getStatusColor = (status) => {
    const colors = {
        active: "bg-green-100 text-green-800",
        completed: "bg-blue-100 text-blue-800",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};

const viewEnrollments = (program) => {
    router.visit(`/admin/programs/${program.program_id}/enrollments`);
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
</script>

<template>
    <Head title="Programs Management" />
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
                                Program Progress Monitoring
                            </h3>
                            <!-- <p class="text-sm text-gray-500">Monitor program capacity and progress (maximum 25 trainees per program)</p> -->
                        </div>
                        <div class="flex space-x-3">
                            <SecondaryButton
                                v-if="isOfficer"
                                @click="openCreateModal"
                                class="bg-gradient-to-r from-green-600 to-emerald-600 text-white border-none hover:from-green-700 hover:to-emerald-700 transition-all duration-300"
                            >
                                Add New Program
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Filters Section -->
                <div
                    class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 border-b border-gray-200"
                >
                    <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                        <div class="relative">
                            <InputLabel for="search" value="Search Programs" />
                            <TextInput
                                id="search"
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by program name"
                                class="mt-1 block w-full transition-all duration-300 border-2 border-transparent focus:border-green-500 focus:ring-2 focus:ring-green-200 hover:border-green-300"
                            />
                        </div>
                    </div>
                </div>

                <!-- Role-based notification -->
                <div
                    v-if="!isOfficer && !isAdmin"
                    class="bg-yellow-50 border-l-4 border-yellow-400 p-4 m-6 rounded-lg"
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
                                You don't have permission to manage programs.
                                Only Officers can add, edit, or delete programs.
                                Admins have read-only access.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Programs Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Program
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Trainers
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Duration
                                </th>
                                <th
                                    scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Enrollment Fee
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
                                    Total Enrollments
                                </th>
                                <th
                                    v-if="isOfficer || isAdmin"
                                    scope="col"
                                    class="relative px-6 py-3"
                                >
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="program in filteredPrograms"
                                :key="program.program_id"
                                class="hover:bg-gray-50 transition-colors duration-200"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
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
                                        <div
                                            v-else
                                            class="text-gray-500 italic"
                                        >
                                            No trainer assigned
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ program.duration }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{
                                            formatCurrency(
                                                program.enrollment_fee
                                            )
                                        }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                        :class="getStatusColor(program.status)"
                                    >
                                        {{ program.status }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    <button
                                        @click="viewEnrollments(program)"
                                        class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-900 transition-colors duration-200"
                                        :title="`View ${
                                            program.enrollments || 0
                                        } enrollments`"
                                    >
                                        <svg
                                            class="w-5 h-5"
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
                                        <span class="text-xs font-medium">{{
                                            program.enrollments || 0
                                        }}</span>
                                    </button>
                                </td>
                                <td
                                    v-if="isOfficer"
                                    class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2"
                                >
                                    <button
                                        @click="openEditModal(program)"
                                        class="text-indigo-600 hover:text-indigo-900 transition-colors duration-200"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        @click="openDeleteModal(program)"
                                        class="text-red-600 hover:text-red-900 transition-colors duration-200"
                                    >
                                        Delete
                                    </button>
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
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">
                        No programs found
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Get started by creating a new program.
                    </p>
                    <div class="mt-6">
                        <SecondaryButton
                            v-if="isOfficer || isAdmin"
                            @click="openCreateModal"
                            class="bg-gradient-to-r from-green-600 to-emerald-600 text-white border-none hover:from-green-700 hover:to-emerald-700"
                        >
                            Add New Program
                        </SecondaryButton>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ editingProgram ? "Edit Program" : "Create New Program" }}
                </h2>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <InputLabel for="program_id" value="Program ID" />
                        <TextInput
                            id="program_id"
                            v-model="form.program_id"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Auto-generated if left blank"
                        />
                        <InputError
                            :message="form.errors.program_id"
                            class="mt-2"
                        />
                    </div>

                    <div>
                        <InputLabel for="name" value="Program Name" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="description" value="Description" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            rows="3"
                        ></textarea>
                        <InputError
                            :message="form.errors.description"
                            class="mt-2"
                        />
                    </div>

                    <div>
                        <InputLabel for="duration" value="Duration" />
                        <TextInput
                            id="duration"
                            v-model="form.duration"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="e.g., 8 weeks"
                            required
                        />
                        <InputError
                            :message="form.errors.duration"
                            class="mt-2"
                        />
                    </div>

                    <div>
                        <InputLabel for="prerequisites" value="Prerequisites" />
                        <textarea
                            id="prerequisites"
                            v-model="form.prerequisites"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            rows="2"
                        ></textarea>
                        <InputError
                            :message="form.errors.prerequisites"
                            class="mt-2"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel
                                for="max_students"
                                value="Max Students"
                            />
                            <TextInput
                                id="max_students"
                                v-model="form.max_students"
                                type="number"
                                class="mt-1 block w-full"
                                min="1"
                                max="100"
                            />
                            <InputError
                                :message="form.errors.max_students"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel
                                for="enrollment_fee"
                                value="Enrollment Fee"
                            />
                            <TextInput
                                id="enrollment_fee"
                                v-model="form.enrollment_fee"
                                type="number"
                                step="0.01"
                                class="mt-1 block w-full"
                                min="0"
                            />
                            <InputError
                                :message="form.errors.enrollment_fee"
                                class="mt-2"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <SecondaryButton @click="showModal = false">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton
                            type="submit"
                            :disabled="form.processing"
                            class="bg-green-600 hover:bg-green-700"
                        >
                            {{ editingProgram ? "Update" : "Create" }} Program
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg
                            class="h-6 w-6 text-red-600"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"
                            />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-lg font-medium text-gray-900">
                            Delete Program
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Are you sure you want to delete "{{
                                    deletingProgram?.name
                                }}"? This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="showDeleteModal = false">
                        Cancel
                    </SecondaryButton>
                    <DangerButton
                        @click="deleteProgram"
                        :disabled="form.processing"
                    >
                        Delete Program
                    </DangerButton>
                </div>
            </div>
        </Modal>
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
