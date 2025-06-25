<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import TrainerRegistrationModal from "@/Components/TrainerRegistrationModal.vue";
import TrainerDetailsModal from "@/Components/TrainerDetailsModal.vue";
import DeleteConfirmationModal from "@/Components/DeleteConfirmationModal.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    trainers: Array,
    courses: Array,
});

const searchQuery = ref("");
const showRegistrationModal = ref(false);
const showDetailsModal = ref(false);
const showDeleteModal = ref(false);
const selectedTrainer = ref(null);
const processing = ref(false);

// Process trainers data to match the expected format
const trainersList = ref(
    props.trainers?.map((trainer) => ({
        id: trainer.id,
        name: trainer.full_name,
        expertise: trainer.expertise,
        expertise_string: trainer.expertise_string,
        email: trainer.email,
        phone: trainer.phone,
        activeCourses: trainer.active_courses_count || 0,
        totalTrainees: trainer.total_trainees_count || 0,
        activeTrainees: trainer.active_trainees_count || 0,
        completedTrainees: trainer.completed_trainees_count || 0,
        avatar:
            trainer.full_name
                ?.split(" ")
                .map((n) => n[0])
                .join("") || "",
        status: trainer.status,
    })) || []
);

// Computed property for filtered trainers - this provides automatic reactivity
const filteredTrainers = computed(() => {
    if (!searchQuery.value) {
        return trainersList.value;
    }

    return trainersList.value.filter(
        (trainer) =>
            trainer.name
                .toLowerCase()
                .includes(searchQuery.value.toLowerCase()) ||
            trainer.expertise_string
                .toLowerCase()
                .includes(searchQuery.value.toLowerCase()) ||
            trainer.email
                .toLowerCase()
                .includes(searchQuery.value.toLowerCase())
    );
});

const addTrainer = () => {
    showRegistrationModal.value = true;
};

const closeRegistrationModal = () => {
    showRegistrationModal.value = false;
};

const onTrainerSubmitted = () => {
    // Refresh the page to show the new trainer
    window.location.reload();
};

const viewTrainer = (trainer) => {
    // Find the actual trainer data from props
    const actualTrainer = props.trainers.find((t) => t.id === trainer.id);
    selectedTrainer.value = actualTrainer;
    showDetailsModal.value = true;
};

const editTrainer = (trainer) => {
    router.visit(`/admin/trainers/${trainer.id}/edit`);
};

const deleteTrainer = (trainer) => {
    selectedTrainer.value = trainer;
    showDeleteModal.value = true;
};

const closeDetailsModal = () => {
    showDetailsModal.value = false;
    selectedTrainer.value = null;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedTrainer.value = null;
};

const confirmDelete = () => {
    if (!selectedTrainer.value) return;

    processing.value = true;

    router.delete(route("admin.trainers.destroy", selectedTrainer.value.id), {
        onSuccess: () => {
            processing.value = false;
            closeDeleteModal();
            // Refresh to show updated list
            window.location.reload();
        },
        onError: () => {
            processing.value = false;
            alert("Failed to delete trainer. Please try again.");
        },
    });
};

const handleEditFromDetails = (trainer) => {
    closeDetailsModal();
    editTrainer(trainer);
};
</script>

<template>
    <Head title="Trainers Management" />

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
                                Trainers Management
                            </h3>
                        </div>
                        <div>
                            <button
                                @click="addTrainer"
                                class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-lg transition-all"
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
                                Add Trainer
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Search Section -->
                <div
                    class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 border-b border-gray-200"
                >
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-green-900">
                            All Trainers ({{ filteredTrainers.length }})
                        </h2>
                        <div class="relative">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search trainers..."
                                class="pl-10 pr-4 py-2 border-2 border-transparent rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200 hover:border-green-300 w-80 transition-all"
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

                <!-- Trainers Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0 z-10 border-b">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"
                                >
                                    Trainer ID
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"
                                >
                                    Trainer
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"
                                >
                                    Expertise
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"
                                >
                                    Contact
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"
                                >
                                    Training Stats
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="trainer in filteredTrainers"
                                :key="trainer.id"
                                class="hover:bg-green-50 transition duration-150"
                            >
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                >
                                    TR{{ String(trainer.id).padStart(3, "0") }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div
                                            class="h-10 w-10 rounded-full bg-green-600 flex items-center justify-center text-white font-semibold mr-3"
                                        >
                                            {{ trainer.avatar }}
                                        </div>
                                        <div
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{ trainer.name }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    {{ trainer.expertise_string }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-1">
                                            <svg
                                                class="h-4 w-4 text-green-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                                />
                                            </svg>
                                            <span class="text-xs">{{
                                                trainer.email
                                            }}</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <svg
                                                class="h-4 w-4 text-green-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                                />
                                            </svg>
                                            <span class="text-xs">{{
                                                trainer.phone
                                            }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-1">
                                            <span class="text-xs text-gray-500"
                                                >Courses:</span
                                            >
                                            <span
                                                class="font-semibold text-emerald-600"
                                                >{{
                                                    trainer.activeCourses
                                                }}</span
                                            >
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <span class="text-xs text-gray-500"
                                                >Trainees:</span
                                            >
                                            <span
                                                class="font-semibold text-green-600"
                                                >{{
                                                    trainer.totalTrainees
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        :class="{
                                            'bg-green-100 text-green-800':
                                                trainer.status === 'active',
                                            'bg-gray-100 text-gray-800':
                                                trainer.status === 'inactive',
                                        }"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{
                                            trainer.status
                                                ?.charAt(0)
                                                .toUpperCase() +
                                                trainer.status?.slice(1) ||
                                            "Active"
                                        }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="viewTrainer(trainer)"
                                            class="text-green-700 hover:text-green-900 p-1 rounded"
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
                                            @click="editTrainer(trainer)"
                                            class="text-green-700 hover:text-green-900 p-1 rounded"
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
                                            @click="deleteTrainer(trainer)"
                                            :disabled="processing"
                                            class="text-red-600 hover:text-red-800 p-1 rounded"
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
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Empty State -->
                    <div
                        v-if="filteredTrainers.length === 0"
                        class="p-8 text-center bg-gradient-to-br from-white to-green-50"
                    >
                        <svg
                            class="mx-auto h-12 w-12 text-green-300 animate-bounce"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.196-2.196m0 0A5.002 5.002 0 0019 15m-3 4h-3a4 4 0 01-4-4v-1m6 5H9a2 2 0 01-2-2v-1"
                            />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">
                            No trainers found
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{
                                searchQuery
                                    ? "Try adjusting your search terms."
                                    : "Get started by adding a new trainer."
                            }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <TrainerRegistrationModal
            :show="showRegistrationModal"
            :courses="courses"
            @close="closeRegistrationModal"
            @submitted="onTrainerSubmitted"
        />

        <TrainerDetailsModal
            :show="showDetailsModal"
            :trainer="selectedTrainer"
            @close="closeDetailsModal"
            @edit="handleEditFromDetails"
        />

        <DeleteConfirmationModal
            :show="showDeleteModal"
            :item="selectedTrainer"
            itemType="trainer"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
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
