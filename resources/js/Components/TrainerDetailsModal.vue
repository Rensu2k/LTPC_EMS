<script setup>
import { computed } from "vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const props = defineProps({
    show: Boolean,
    trainer: Object,
});

const emit = defineEmits(["close", "edit"]);

const closeModal = () => {
    emit("close");
};

const editTrainer = () => {
    emit("edit", props.trainer);
};

const formatTime = (time) => {
    if (!time) return "";
    // Convert 24-hour format to 12-hour format
    const [hours, minutes] = time.split(":");
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? "PM" : "AM";
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
};

const availableSchedule = computed(() => {
    if (!props.trainer?.availability_schedule) return [];
    return props.trainer.availability_schedule.filter((day) => day.available);
});

const unavailableSchedule = computed(() => {
    if (!props.trainer?.availability_schedule) return [];
    return props.trainer.availability_schedule.filter((day) => !day.available);
});
</script>

<template>
    <Modal :show="show" @close="closeModal" custom-width="80vw">
        <div class="p-8">
            <!-- Header -->
            <div class="flex justify-between items-start mb-8">
                <div class="flex items-center gap-4">
                    <div
                        class="h-16 w-16 rounded-full bg-blue-500 flex items-center justify-center text-white text-xl font-bold"
                    >
                        {{
                            trainer?.full_name
                                ?.split(" ")
                                .map((n) => n[0])
                                .join("") || ""
                        }}
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">
                            {{ trainer?.full_name || "Trainer Details" }}
                        </h2>
                        <div class="flex items-center gap-2 mt-1">
                            <span
                                :class="{
                                    'bg-green-100 text-green-800':
                                        trainer?.status === 'active',
                                    'bg-gray-100 text-gray-800':
                                        trainer?.status === 'inactive',
                                }"
                                class="inline-flex px-3 py-1 text-sm font-semibold rounded-full"
                            >
                                {{
                                    trainer?.status?.charAt(0).toUpperCase() +
                                        trainer?.status?.slice(1) || "Active"
                                }}
                            </span>
                            <span class="text-gray-500 text-sm">
                                ID: TR{{ String(trainer?.id).padStart(3, "0") }}
                            </span>
                        </div>
                    </div>
                </div>
                <button
                    @click="closeModal"
                    class="text-gray-400 hover:text-gray-600 p-2"
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

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Left Column: Basic Information -->
                <div class="space-y-6">
                    <!-- Personal Information -->
                    <div class="bg-blue-50 rounded-lg p-6">
                        <h3
                            class="text-lg font-semibold text-blue-900 mb-4 flex items-center gap-2"
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
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                />
                            </svg>
                            Personal Information
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-blue-700 font-medium"
                                    >Full Name:</span
                                >
                                <span class="text-gray-900">{{
                                    trainer?.full_name || "N/A"
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-blue-700 font-medium"
                                    >Expertise:</span
                                >
                                <span class="text-gray-900">{{
                                    trainer?.expertise || "N/A"
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="bg-green-50 rounded-lg p-6">
                        <h3
                            class="text-lg font-semibold text-green-900 mb-4 flex items-center gap-2"
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
                                    d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                />
                            </svg>
                            Contact Information
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-green-700 font-medium"
                                    >Email:</span
                                >
                                <span class="text-gray-900">{{
                                    trainer?.email || "N/A"
                                }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-green-700 font-medium"
                                    >Phone:</span
                                >
                                <span class="text-gray-900">{{
                                    trainer?.phone || "N/A"
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Biography -->
                    <div
                        class="bg-purple-50 rounded-lg p-6"
                        v-if="trainer?.biography"
                    >
                        <h3
                            class="text-lg font-semibold text-purple-900 mb-4 flex items-center gap-2"
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
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            Biography
                        </h3>
                        <p class="text-gray-700 leading-relaxed">
                            {{ trainer.biography }}
                        </p>
                    </div>
                </div>

                <!-- Right Column: Availability Schedule -->
                <div class="space-y-6">
                    <!-- Available Days -->
                    <div class="bg-green-50 rounded-lg p-6">
                        <h3
                            class="text-lg font-semibold text-green-900 mb-4 flex items-center gap-2"
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
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                            Available Schedule
                        </h3>
                        <div class="space-y-3">
                            <div
                                v-for="day in availableSchedule"
                                :key="day.day"
                                class="flex justify-between items-center p-3 bg-white rounded-lg border-l-4 border-green-500"
                            >
                                <span class="font-medium text-gray-900">{{
                                    day.day
                                }}</span>
                                <div
                                    class="flex items-center gap-2 text-sm text-gray-600"
                                >
                                    <span>{{
                                        formatTime(day.start_time)
                                    }}</span>
                                    <span>-</span>
                                    <span>{{ formatTime(day.end_time) }}</span>
                                </div>
                            </div>
                            <div
                                v-if="availableSchedule.length === 0"
                                class="text-center py-4"
                            >
                                <p class="text-gray-500">
                                    No available days set
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Unavailable Days -->
                    <div
                        class="bg-gray-50 rounded-lg p-6"
                        v-if="unavailableSchedule.length > 0"
                    >
                        <h3
                            class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2"
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
                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L5.636 5.636"
                                />
                            </svg>
                            Unavailable Days
                        </h3>
                        <div class="space-y-2">
                            <div
                                v-for="day in unavailableSchedule"
                                :key="day.day"
                                class="flex justify-between items-center p-3 bg-white rounded-lg border-l-4 border-gray-400"
                            >
                                <span class="font-medium text-gray-700">{{
                                    day.day
                                }}</span>
                                <span class="text-sm text-gray-500"
                                    >Not Available</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Training Statistics -->
                    <div class="bg-blue-50 rounded-lg p-6">
                        <h3
                            class="text-lg font-semibold text-blue-900 mb-4 flex items-center gap-2"
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
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                                />
                            </svg>
                            Training Statistics
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-900">
                                    {{ trainer?.active_courses_count || 0 }}
                                </div>
                                <div class="text-sm text-blue-700">
                                    Active Courses
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-900">
                                    {{ trainer?.total_trainees_count || 0 }}
                                </div>
                                <div class="text-sm text-blue-700">
                                    Total Trainees
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-900">
                                    {{ trainer?.active_trainees_count || 0 }}
                                </div>
                                <div class="text-sm text-green-700">
                                    Active Trainees
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-900">
                                    {{ trainer?.completed_trainees_count || 0 }}
                                </div>
                                <div class="text-sm text-purple-700">
                                    Completed
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3 mt-8 pt-6 border-t">
                <SecondaryButton @click="closeModal"> Close </SecondaryButton>
                <PrimaryButton
                    @click="editTrainer"
                    class="bg-blue-600 hover:bg-blue-700"
                >
                    Edit Trainer
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
