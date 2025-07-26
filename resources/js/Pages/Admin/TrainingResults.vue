<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    training_results: Array,
    flash: Object,
});

const showModal = ref(false);
const showDeleteModal = ref(false);
const showDetailsModal = ref(false);
const editingResult = ref(null);
const deletingResult = ref(null);
const viewingResult = ref(null);
const searchQuery = ref("");
const selectedStatus = ref("");
const selectedProgram = ref("");

const form = useForm({
    training_id: "",
    trainee_id: "",
    completion_status: "incomplete",
    notes: "",
});

const filteredResults = computed(() => {
    let filtered = props.training_results || [];

    if (searchQuery.value) {
        filtered = filtered.filter(
            (result) =>
                result.trainee?.first_name
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                result.trainee?.last_name
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                result.training?.program?.name
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                result.training?.trainer?.first_name
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                result.training?.trainer?.last_name
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase())
        );
    }

    if (selectedStatus.value) {
        filtered = filtered.filter(
            (result) => result.completion_status === selectedStatus.value
        );
    }

    if (selectedProgram.value) {
        filtered = filtered.filter(
            (result) => result.training?.program_id == selectedProgram.value
        );
    }

    return filtered;
});

const programs = computed(() => {
    const uniquePrograms = [
        ...new Set(
            props.training_results
                ?.map((result) => result.training?.program)
                .filter(Boolean)
        ),
    ];
    return uniquePrograms;
});

const openCreateModal = () => {
    form.reset();
    editingResult.value = null;
    showModal.value = true;
};

const openEditModal = (result) => {
    editingResult.value = result;
    form.training_id = result.training_id;
    form.trainee_id = result.trainee_id;
    form.completion_status = result.completion_status;
    form.notes = result.notes;
    showModal.value = true;
};

const openDetailsModal = (result) => {
    viewingResult.value = result;
    showDetailsModal.value = true;
};

const openDeleteModal = (result) => {
    deletingResult.value = result;
    showDeleteModal.value = true;
};

const submitForm = () => {
    if (editingResult.value) {
        form.put(`/admin/training-results/${editingResult.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post("/admin/training-results", {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const deleteResult = () => {
    router.delete(`/admin/training-results/${deletingResult.value.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
            deletingResult.value = null;
        },
    });
};

const getStatusColor = (status) => {
    const colors = {
        complete: "bg-green-100 text-green-800",
        incomplete: "bg-red-100 text-red-800",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};

const getScoreColor = (score) => {
    if (score >= 90) return "text-green-600 font-semibold";
    if (score >= 80) return "text-blue-600 font-semibold";
    if (score >= 70) return "text-yellow-600 font-semibold";
    if (score >= 60) return "text-orange-600 font-semibold";
    return "text-red-600 font-semibold";
};

const getPerformanceRating = (score) => {
    if (score >= 90) return "Excellent";
    if (score >= 80) return "Good";
    if (score >= 70) return "Satisfactory";
    if (score >= 60) return "Needs Improvement";
    return "Unsatisfactory";
};

const calculateFinalScore = () => {
    const practical = parseFloat(form.practical_score) || 0;
    const theory = parseFloat(form.theory_score) || 0;
    // 60% practical, 40% theory
    form.final_score = (practical * 0.6 + theory * 0.4).toFixed(2);
};

const exportResults = () => {
    // TODO: Implement export functionality
};
</script>

<template>
    <Head title="Training Results Management" />
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
                                class="text-lg font-semibold text-green-900 relative pb-2 after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-16 after:h-0.5 after:bg-gradient-to-r after:rounded"
                            >
                                Training Results Monitoring
                            </h3>
                            <!-- <p class="text-sm text-gray-500">Monitor training completion status and track progress</p> -->
                        </div>
                        <div class="flex space-x-3">
                            <SecondaryButton
                                @click="exportResults"
                                class="bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200 hover:border-gray-400 transition-all duration-300"
                            >
                                📄 Export PDF Report
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
                            <InputLabel for="search" value="Search Results" />
                            <TextInput
                                id="search"
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by trainee name"
                                class="mt-1 block w-full transition-all duration-300 border-2 border-transparent focus:border-green-500 focus:ring-2 focus:ring-green-200 hover:border-green-300"
                            />
                        </div>
                        <div>
                            <InputLabel
                                for="status-filter"
                                value="Filter by Status"
                            />
                            <select
                                id="status-filter"
                                v-model="selectedStatus"
                                class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            >
                                <option value="">All Statuses</option>
                                <option value="complete">Complete</option>
                                <option value="incomplete">Incomplete</option>
                            </select>
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
                                    :key="program.id"
                                    :value="program.id"
                                >
                                    {{ program.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="p-6 border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div
                            class="bg-green-50 p-4 rounded-lg border border-green-200"
                        >
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center"
                                    >
                                        <span class="text-white text-xs"
                                            >✓</span
                                        >
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p
                                        class="text-xs font-medium text-green-600"
                                    >
                                        Complete
                                    </p>
                                    <p
                                        class="text-lg font-semibold text-green-900"
                                    >
                                        {{
                                            filteredResults.filter(
                                                (r) =>
                                                    r.completion_status ===
                                                    "complete"
                                            ).length
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-red-50 p-4 rounded-lg border border-red-200"
                        >
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center"
                                    >
                                        <span class="text-white text-xs"
                                            >✗</span
                                        >
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p class="text-xs font-medium text-red-600">
                                        Incomplete
                                    </p>
                                    <p
                                        class="text-lg font-semibold text-red-900"
                                    >
                                        {{
                                            filteredResults.filter(
                                                (r) =>
                                                    r.completion_status ===
                                                    "incomplete"
                                            ).length
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-blue-50 p-4 rounded-lg border border-blue-200"
                        >
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center"
                                    >
                                        <span class="text-white text-xs"
                                            >📊</span
                                        >
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p
                                        class="text-xs font-medium text-blue-600"
                                    >
                                        Total Results
                                    </p>
                                    <p
                                        class="text-lg font-semibold text-blue-900"
                                    >
                                        {{ filteredResults.length }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-purple-50 p-4 rounded-lg border border-purple-200"
                        >
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center"
                                    >
                                        <span class="text-white text-xs"
                                            >%</span
                                        >
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p
                                        class="text-xs font-medium text-purple-600"
                                    >
                                        Completion Rate
                                    </p>
                                    <p
                                        class="text-lg font-semibold text-purple-900"
                                    >
                                        {{
                                            filteredResults.length > 0
                                                ? Math.round(
                                                      (filteredResults.filter(
                                                          (r) =>
                                                              r.completion_status ===
                                                              "complete"
                                                      ).length /
                                                          filteredResults.length) *
                                                          100
                                                  )
                                                : 0
                                        }}%
                                    </p>
                                </div>
                            </div>
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
                                    Trainee
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
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Date Completed
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="result in filteredResults"
                                :key="result.id"
                                class="hover:bg-gray-50 transition-colors duration-200 group"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div
                                                class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-medium relative overflow-hidden"
                                            >
                                                <span class="relative z-10">
                                                    {{
                                                        result.trainee?.first_name?.charAt(
                                                            0
                                                        )
                                                    }}{{
                                                        result.trainee?.last_name?.charAt(
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
                                                {{ result.trainee?.first_name }}
                                                {{ result.trainee?.last_name }}
                                            </div>
                                            <div
                                                class="text-sm text-gray-500 group-hover:text-green-500 transition-colors duration-200"
                                            >
                                                ID: {{ result.trainee?.id }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{
                                            result.training?.program?.name ||
                                            "N/A"
                                        }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        Training ID: {{ result.training_id }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{
                                            result.training?.trainer?.first_name
                                        }}
                                        {{
                                            result.training?.trainer?.last_name
                                        }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="
                                            getStatusColor(
                                                result.completion_status
                                            )
                                        "
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full border"
                                    >
                                        {{
                                            result.completion_status ===
                                            "complete"
                                                ? "Complete"
                                                : "Incomplete"
                                        }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    {{
                                        result.date_completed || "Not completed"
                                    }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                                >
                                    <div class="flex justify-end space-x-2">
                                        <button
                                            @click="openDetailsModal(result)"
                                            class="px-3 py-1 text-blue-600 hover:text-white hover:bg-blue-600 border border-blue-600 rounded transition-all duration-300 font-medium"
                                        >
                                            View
                                        </button>
                                        <button
                                            @click="openEditModal(result)"
                                            class="px-3 py-1 text-green-600 hover:text-white hover:bg-green-600 border border-green-600 rounded transition-all duration-300 font-medium"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click="openDeleteModal(result)"
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
                        v-if="filteredResults.length === 0"
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
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">
                                No training results found
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{
                                    searchQuery
                                        ? "Try adjusting your filters."
                                        : "No training results have been recorded yet."
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
            max-width="lg"
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
                            editingResult
                                ? "Edit Training Result"
                                : "Add Training Result"
                        }}
                    </h3>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel
                                for="training_id"
                                value="Training ID *"
                            />
                            <TextInput
                                id="training_id"
                                v-model="form.training_id"
                                type="text"
                                class="mt-1 block w-full border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                                required
                            />
                            <InputError
                                :message="form.errors.training_id"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel for="trainee_id" value="Trainee ID *" />
                            <TextInput
                                id="trainee_id"
                                v-model="form.trainee_id"
                                type="text"
                                class="mt-1 block w-full border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                                required
                            />
                            <InputError
                                :message="form.errors.trainee_id"
                                class="mt-2"
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel
                            for="completion_status"
                            value="Completion Status *"
                        />
                        <select
                            id="completion_status"
                            v-model="form.completion_status"
                            class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            required
                        >
                            <option value="incomplete">Incomplete</option>
                            <option value="complete">Complete</option>
                        </select>
                        <InputError
                            :message="form.errors.completion_status"
                            class="mt-2"
                        />
                    </div>

                    <div>
                        <InputLabel for="notes" value="Notes" />
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            rows="3"
                            placeholder="Additional notes about the training result..."
                        ></textarea>
                        <InputError :message="form.errors.notes" class="mt-2" />
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
                                    : editingResult
                                    ? "Update"
                                    : "Create"
                            }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Details Modal -->
        <Modal
            :show="showDetailsModal"
            @close="showDetailsModal = false"
            max-width="xl"
        >
            <div
                class="p-6 bg-white rounded-xl shadow-2xl border border-gray-100"
            >
                <div class="border-b border-gray-200 pb-4 mb-6 relative">
                    <div
                        class="absolute bottom-0 left-0 w-20 h-0.5 bg-gradient-to-r from-blue-500 to-indigo-500 rounded"
                    ></div>
                    <h3 class="text-lg font-semibold text-blue-900">
                        Training Result Details
                    </h3>
                </div>

                <div v-if="viewingResult" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">
                                Trainee Information
                            </h4>
                            <div class="space-y-2">
                                <p class="text-sm">
                                    <span class="font-medium">Name:</span>
                                    {{ viewingResult.trainee?.first_name }}
                                    {{ viewingResult.trainee?.last_name }}
                                </p>
                                <p class="text-sm">
                                    <span class="font-medium">ID:</span>
                                    {{ viewingResult.trainee?.id }}
                                </p>
                                <p class="text-sm">
                                    <span class="font-medium">Email:</span>
                                    {{ viewingResult.trainee?.email || "N/A" }}
                                </p>
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">
                                Training Information
                            </h4>
                            <div class="space-y-2">
                                <p class="text-sm">
                                    <span class="font-medium">Program:</span>
                                    {{
                                        viewingResult.training?.program?.name ||
                                        "N/A"
                                    }}
                                </p>
                                <p class="text-sm">
                                    <span class="font-medium">Trainer:</span>
                                    {{
                                        viewingResult.training?.trainer
                                            ?.first_name
                                    }}
                                    {{
                                        viewingResult.training?.trainer
                                            ?.last_name
                                    }}
                                </p>
                                <p class="text-sm">
                                    <span class="font-medium"
                                        >Training ID:</span
                                    >
                                    {{ viewingResult.training_id }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">
                            Result Details
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm font-medium">Status:</span>
                                <span
                                    :class="
                                        getStatusColor(
                                            viewingResult.completion_status
                                        )
                                    "
                                    class="ml-2 inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                >
                                    {{
                                        viewingResult.completion_status ===
                                        "complete"
                                            ? "Complete"
                                            : "Incomplete"
                                    }}
                                </span>
                            </div>
                            <div>
                                <span class="text-sm font-medium"
                                    >Date Completed:</span
                                >
                                <span class="ml-2 text-sm">{{
                                    viewingResult.date_completed ||
                                    "Not completed"
                                }}</span>
                            </div>
                        </div>
                        <div v-if="viewingResult.notes" class="mt-4">
                            <span class="text-sm font-medium">Notes:</span>
                            <p class="mt-1 text-sm text-gray-700">
                                {{ viewingResult.notes }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <SecondaryButton
                        @click="showDetailsModal = false"
                        class="border-2 border-gray-300 hover:border-blue-500 hover:text-blue-600 transition-all duration-300"
                    >
                        Close
                    </SecondaryButton>
                </div>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false">
            <div
                class="p-6 bg-white rounded-xl shadow-2xl border border-gray-100"
            >
                <div class="border-b border-gray-200 pb-4 mb-6 relative">
                    <div
                        class="absolute bottom-0 left-0 w-20 h-0.5 bg-gradient-to-r from-red-500 to-pink-500 rounded"
                    ></div>
                    <h3 class="text-lg font-semibold text-red-900">
                        Delete Training Result
                    </h3>
                </div>
                <p class="text-sm text-gray-500 mb-4">
                    Are you sure you want to delete this training result? This
                    action cannot be undone.
                </p>
                <div class="flex justify-end space-x-3">
                    <SecondaryButton
                        @click="showDeleteModal = false"
                        class="border-2 border-gray-300 hover:border-red-500 hover:text-red-600 transition-all duration-300"
                    >
                        Cancel
                    </SecondaryButton>
                    <DangerButton
                        @click="deleteResult"
                        class="bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 transition-all duration-300"
                    >
                        Delete
                    </DangerButton>
                </div>
            </div>
        </Modal>
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
