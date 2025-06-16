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
    trainers: Array,
    flash: Object,
});

const showModal = ref(false);
const showDeleteModal = ref(false);
const editingTrainer = ref(null);
const deletingTrainer = ref(null);
const searchQuery = ref("");
const selectedProgram = ref("");

const form = useForm({
    first_name: "",
    last_name: "",
    email: "",
    phone: "",
    address: "",
    program: "",
    availability_schedule: "",
});

const filteredTrainers = computed(() => {
    let filtered = props.trainers || [];

    if (searchQuery.value) {
        filtered = filtered.filter(
            (trainer) =>
                trainer.first_name
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                trainer.last_name
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                trainer.email
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                trainer.program
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase())
        );
    }

    if (selectedProgram.value) {
        filtered = filtered.filter((trainer) =>
            trainer.program
                ?.toLowerCase()
                .includes(selectedProgram.value.toLowerCase())
        );
    }

    return filtered;
});

const programs = computed(() => {
    const uniquePrograms = [
        ...new Set(props.trainers?.map((t) => t.program).filter(Boolean)),
    ];
    return uniquePrograms;
});

const openEditModal = (trainer) => {
    editingTrainer.value = trainer;
    form.first_name = trainer.first_name;
    form.last_name = trainer.last_name;
    form.email = trainer.email;
    form.phone = trainer.phone;
    form.address = trainer.address;
    form.program = trainer.program;
    form.availability_schedule = trainer.availability_schedule;
    showModal.value = true;
};

const openDeleteModal = (trainer) => {
    deletingTrainer.value = trainer;
    showDeleteModal.value = true;
};

const submitForm = () => {
    if (editingTrainer.value) {
        form.put(`/admin/trainers/${editingTrainer.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const deleteTrainer = () => {
    router.delete(`/admin/trainers/${deletingTrainer.value.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
            deletingTrainer.value = null;
        },
    });
};

const getTraineeCount = (trainer) => {
    return trainer.trainees_count || 0;
};

const getTraineeCountColor = (count) => {
    if (count >= 20) return "bg-red-100 text-red-800";
    if (count >= 10) return "bg-yellow-100 text-yellow-800";
    if (count >= 5) return "bg-green-100 text-green-800";
    return "bg-gray-100 text-gray-800";
};

const exportTrainersReport = () => {
    console.log("Exporting trainers report...");
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
                                class="text-lg font-semibold text-green-900 relative pb-2 after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-16 after:h-0.5 after:bg-gradient-to-r after:from-green-600 after:to-emerald-600 after:rounded"
                            >
                                Trainers
                            </h3>
                            <!-- <p class="text-sm text-gray-500">Manage trainer profiles and assignments</p> -->
                        </div>
                        <div class="flex space-x-3">
                            <SecondaryButton
                                @click="exportTrainersReport"
                                class="bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200 hover:border-gray-400 transition-all duration-300"
                            >
                                📄 Export Report
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Filters Section -->
                <div
                    class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 border-b border-gray-200"
                >
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="relative">
                            <InputLabel for="search" value="Search Trainers" />
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
                                for="course-filter"
                                value="Filter by Program"
                            />
                            <select
                                id="course-filter"
                                v-model="selectedProgram"
                                class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            >
                                <option value="">All Programs</option>
                                <option
                                    v-for="program in programs"
                                    :key="program"
                                    :value="program"
                                >
                                    {{ program }}
                                </option>
                            </select>
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
                                    Trainer
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Contact
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Program
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Trainees
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Schedule
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
                                v-for="trainer in filteredTrainers"
                                :key="trainer.id"
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
                                                        trainer.first_name?.charAt(
                                                            0
                                                        )
                                                    }}{{
                                                        trainer.last_name?.charAt(
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
                                                {{ trainer.first_name }}
                                                {{ trainer.last_name }}
                                            </div>
                                            <div
                                                class="text-sm text-gray-500 group-hover:text-green-500 transition-colors duration-200"
                                            >
                                                ID: {{ trainer.id }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ trainer.email }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ trainer.phone || "No phone" }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ trainer.program || "Not assigned" }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="
                                            getTraineeCountColor(
                                                getTraineeCount(trainer)
                                            )
                                        "
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full border"
                                    >
                                        {{ getTraineeCount(trainer) }} trainees
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{
                                            trainer.availability_schedule ||
                                            "Not set"
                                        }}
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                                >
                                    <div class="flex justify-end space-x-2">
                                        <button
                                            @click="openEditModal(trainer)"
                                            class="px-3 py-1 text-green-600 hover:text-white hover:bg-green-600 border border-green-600 rounded transition-all duration-300 font-medium"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click="openDeleteModal(trainer)"
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
                        v-if="filteredTrainers.length === 0"
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
                                    d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">
                                No trainers found
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{
                                    searchQuery
                                        ? "Try adjusting your filters."
                                        : "Get started by adding your first trainer."
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal :show="showModal" @close="showModal = false" max-width="lg">
            <div
                class="p-6 bg-white rounded-xl shadow-2xl border border-gray-100"
            >
                <div class="border-b border-gray-200 pb-4 mb-6 relative">
                    <div
                        class="absolute bottom-0 left-0 w-20 h-0.5 bg-gradient-to-r from-green-500 to-emerald-500 rounded"
                    ></div>
                    <h3 class="text-lg font-semibold text-green-900">
                        Edit Trainer
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

                    <div>
                        <InputLabel for="phone" value="Phone" />
                        <TextInput
                            id="phone"
                            v-model="form.phone"
                            type="text"
                            class="mt-1 block w-full border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                        />
                        <InputError :message="form.errors.phone" class="mt-2" />
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

                    <div>
                        <InputLabel
                            for="program"
                            value="Program/Specialization"
                        />
                        <TextInput
                            id="program"
                            v-model="form.program"
                            type="text"
                            class="mt-1 block w-full border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            placeholder="e.g., Welding, Carpentry, Computer Servicing"
                        />
                        <InputError
                            :message="form.errors.program"
                            class="mt-2"
                        />
                    </div>

                    <div>
                        <InputLabel
                            for="availability_schedule"
                            value="Availability Schedule"
                        />
                        <textarea
                            id="availability_schedule"
                            v-model="form.availability_schedule"
                            class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            rows="3"
                            placeholder="e.g., Monday-Friday 8AM-5PM, Weekends available"
                        ></textarea>
                        <InputError
                            :message="form.errors.availability_schedule"
                            class="mt-2"
                        />
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
                            {{ form.processing ? "Updating..." : "Update" }}
                        </PrimaryButton>
                    </div>
                </form>
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
                        Delete Trainer
                    </h3>
                </div>
                <p class="text-sm text-gray-500 mb-4">
                    Are you sure you want to delete trainer "{{
                        deletingTrainer?.first_name
                    }}
                    {{ deletingTrainer?.last_name }}"? This action cannot be
                    undone.
                </p>
                <div class="flex justify-end space-x-3">
                    <SecondaryButton
                        @click="showDeleteModal = false"
                        class="border-2 border-gray-300 hover:border-red-500 hover:text-red-600 transition-all duration-300"
                    >
                        Cancel
                    </SecondaryButton>
                    <DangerButton
                        @click="deleteTrainer"
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
