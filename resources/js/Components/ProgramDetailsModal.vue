<template>
    <Modal :show="show" @close="closeModal" custom-width="80vw">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">
                    Program Details
                </h2>
                <button
                    @click="closeModal"
                    class="text-gray-400 hover:text-gray-600"
                >
                    <svg
                        class="w-6 h-6"
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
                </button>
            </div>

            <div v-if="program" class="space-y-6">
                <!-- Program Header -->
                <div
                    class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6"
                >
                    <div class="flex justify-between items-start">
                        <div>
                            <h3
                                class="text-xl font-semibold text-gray-900 mb-2"
                            >
                                {{ program.name }}
                            </h3>
                            <p class="text-gray-600 mb-4">
                                {{
                                    program.description ||
                                    "No description available"
                                }}
                            </p>
                            <div class="flex items-center gap-4">
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                    :class="{
                                        'bg-green-100 text-green-800':
                                            program.status === 'active',
                                        'bg-gray-100 text-gray-800':
                                            program.status === 'inactive',
                                    }"
                                >
                                    {{
                                        program.status
                                            ?.charAt(0)
                                            .toUpperCase() +
                                            program.status?.slice(1) || "Active"
                                    }}
                                </span>
                                <span class="text-sm text-gray-500">
                                    Program ID: {{ program.id }}
                                </span>
                            </div>
                        </div>
                        <button
                            @click="editProgram"
                            class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                        >
                            <svg
                                class="h-4 w-4 mr-2"
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
                            Edit Program
                        </button>
                    </div>
                </div>

                <!-- Program Information Grid -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Duration -->
                    <div class="bg-white border rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="bg-blue-50 rounded-full p-2 mr-3">
                                <svg
                                    class="h-5 w-5 text-blue-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Duration</p>
                                <p class="text-lg font-semibold text-gray-900">
                                    {{ program.duration }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Enrollments -->
                    <div class="bg-white border rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="bg-green-50 rounded-full p-2 mr-3">
                                <svg
                                    class="h-5 w-5 text-green-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">
                                    Total Enrollments
                                </p>
                                <p class="text-lg font-semibold text-gray-900">
                                    {{ program.enrollments || 0 }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Trainers Count -->
                    <div class="bg-white border rounded-lg p-4">
                        <div class="flex items-center">
                            <div class="bg-purple-50 rounded-full p-2 mr-3">
                                <svg
                                    class="h-5 w-5 text-purple-600"
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
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">
                                    Assigned Trainers
                                </p>
                                <p class="text-lg font-semibold text-gray-900">
                                    {{ assignedTrainers.length }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assigned Trainers -->
                <div class="bg-white border rounded-lg p-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">
                        Assigned Trainers
                    </h4>
                    <div v-if="assignedTrainers.length > 0" class="space-y-3">
                        <div
                            v-for="trainer in assignedTrainers"
                            :key="trainer.id"
                            class="flex items-center p-3 bg-gray-50 rounded-lg"
                        >
                            <div class="bg-indigo-100 rounded-full p-2 mr-4">
                                <span
                                    class="text-indigo-700 font-semibold text-sm"
                                >
                                    {{ getTrainerInitials(trainer.name) }}
                                </span>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900">
                                    {{ trainer.name }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{
                                        trainer.expertise_string ||
                                        "General Training"
                                    }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-8">
                        <div class="text-gray-400 mb-2">
                            <svg
                                class="mx-auto h-12 w-12"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                />
                            </svg>
                        </div>
                        <p class="text-gray-500">
                            No trainers assigned to this program yet.
                        </p>
                    </div>
                </div>

                <!-- Current Batch Status -->
                <div class="bg-white border rounded-lg p-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">
                        Current Batch Status
                    </h4>
                    <div class="mb-4">
                        <div class="flex justify-between text-sm mb-2">
                            <span
                                >Batch
                                {{ program.current_batch || 1 }} Progress</span
                            >
                            <span
                                >{{
                                    Math.min(
                                        ((program.current_batch_count || 0) /
                                            25) *
                                            100,
                                        100
                                    ).toFixed(0)
                                }}%</span
                            >
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div
                                class="bg-blue-600 h-3 rounded-full transition-all duration-300"
                                :style="{
                                    width:
                                        Math.min(
                                            ((program.current_batch_count ||
                                                0) /
                                                25) *
                                                100,
                                            100
                                        ) + '%',
                                }"
                            ></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            Maximum 25 trainees per batch. New batch
                            automatically created when full.
                        </p>
                    </div>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <div class="text-2xl font-bold text-blue-600">
                                {{ program.current_batch_count || 0 }}
                            </div>
                            <div class="text-sm text-gray-500">
                                Current Batch
                            </div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-gray-600">
                                {{
                                    Math.max(
                                        25 - (program.current_batch_count || 0),
                                        0
                                    )
                                }}
                            </div>
                            <div class="text-sm text-gray-500">Slots Left</div>
                        </div>
                        <div>
                            <div class="text-2xl font-bold text-green-600">
                                {{ formatCurrency(program.enrollment_fee) }}
                            </div>
                            <div class="text-sm text-gray-500">Fee</div>
                        </div>
                    </div>
                </div>

                <!-- Program Dates -->
                <div
                    v-if="program.start_date || program.end_date"
                    class="bg-white border rounded-lg p-6"
                >
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">
                        Program Schedule
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-if="program.start_date">
                            <p class="text-sm text-gray-500">Start Date</p>
                            <p class="text-lg font-semibold text-gray-900">
                                {{ formatDate(program.start_date) }}
                            </p>
                        </div>
                        <div v-if="program.end_date">
                            <p class="text-sm text-gray-500">End Date</p>
                            <p class="text-lg font-semibold text-gray-900">
                                {{ formatDate(program.end_date) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { computed } from "vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    show: Boolean,
    program: Object,
    trainers: Array,
});

const emit = defineEmits(["close", "edit"]);

const closeModal = () => {
    emit("close");
};

const editProgram = () => {
    emit("edit", props.program);
};

const assignedTrainers = computed(() => {
    if (!props.program?.assigned_trainers || !props.trainers) {
        return [];
    }

    return props.trainers.filter((trainer) =>
        props.program.assigned_trainers.includes(trainer.id)
    );
});

const getTrainerInitials = (name) => {
    if (!name) return "";
    return name
        .split(" ")
        .map((n) => n[0])
        .join("")
        .toUpperCase();
};

const formatCurrency = (amount) => {
    if (!amount || amount === 0) return "Free";
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(amount);
};

const formatDate = (date) => {
    if (!date) return "Not set";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
};
</script>
