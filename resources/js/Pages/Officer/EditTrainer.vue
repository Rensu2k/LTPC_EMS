<script setup>
import { useForm, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    trainer: Object,
    programs: Array,
});

const days = [
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
    "Sunday",
];

// Initialize form with existing trainer data
const form = useForm({
    full_name: props.trainer?.full_name || "",
    expertise: props.trainer?.expertise || [],
    email: props.trainer?.email || "",
    phone: props.trainer?.phone || "",
    biography: props.trainer?.biography || "",
    status: props.trainer?.status || "active",
    availability_schedule:
        props.trainer?.availability_schedule ||
        days.map((day) => ({
            day: day,
            available: false,
            start_time: "08:00",
            end_time: "17:00",
        })),
});

const submitForm = () => {
    form.put(route("officer.trainers.update", props.trainer.id), {
        onSuccess: () => {
            router.visit("/officer/trainers");
        },
    });
};

const goBack = () => {
    router.visit("/officer/trainers");
};
</script>

<template>
    <Head title="Edit Trainer" />

    <AuthenticatedLayout>
        <div class="p-8 max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        Edit Trainer
                    </h1>
                    <p class="text-gray-600 mt-1">
                        Update trainer information and availability schedule
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-gray-500">ID:</span>
                    <span class="font-semibold"
                        >TR{{ String(trainer?.id).padStart(3, "0") }}</span
                    >
                </div>
            </div>

            <form @submit.prevent="submitForm" class="space-y-8">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Left Column: Basic Information -->
                    <div class="space-y-6">
                        <!-- Personal Information -->
                        <div class="bg-white rounded-lg shadow-sm border p-6">
                            <h3
                                class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2"
                            >
                                <svg
                                    class="w-5 h-5 text-blue-600"
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

                            <div class="space-y-4">
                                <!-- Full Name -->
                                <div>
                                    <InputLabel
                                        for="full_name"
                                        value="Full Name"
                                    />
                                    <TextInput
                                        id="full_name"
                                        v-model="form.full_name"
                                        type="text"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError
                                        :message="form.errors.full_name"
                                        class="mt-2"
                                    />
                                </div>

                                <!-- Expertise -->
                                <div>
                                    <InputLabel
                                        for="expertise"
                                        value="Expertise Areas"
                                    />
                                    <div
                                        class="mt-2 space-y-2 max-h-48 overflow-y-auto border border-gray-300 rounded-md p-3"
                                    >
                                        <div
                                            v-if="programs.length === 0"
                                            class="text-gray-500 text-sm"
                                        >
                                            No programs available
                                        </div>
                                        <div
                                            v-for="program in programs"
                                            :key="program.program_id"
                                            class="flex items-center"
                                        >
                                            <input
                                                :id="`expertise-${program.program_id}`"
                                                v-model="form.expertise"
                                                :value="program.name"
                                                type="checkbox"
                                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                            />
                                            <label
                                                :for="`expertise-${program.program_id}`"
                                                class="ml-2 text-sm text-gray-900 cursor-pointer flex-1"
                                            >
                                                {{ program.name }}
                                                <span
                                                    v-if="program.duration"
                                                    class="text-gray-500 text-xs"
                                                >
                                                    ({{ program.duration }})
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500">
                                        Select one or more areas of expertise
                                        for this trainer
                                    </p>
                                    <InputError
                                        :message="form.errors.expertise"
                                        class="mt-2"
                                    />
                                </div>

                                <!-- Status -->
                                <div>
                                    <InputLabel for="status" value="Status" />
                                    <select
                                        id="status"
                                        v-model="form.status"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    >
                                        <option value="active">Active</option>
                                        <option value="inactive">
                                            Inactive
                                        </option>
                                    </select>
                                    <InputError
                                        :message="form.errors.status"
                                        class="mt-2"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div class="bg-white rounded-lg shadow-sm border p-6">
                            <h3
                                class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2"
                            >
                                <svg
                                    class="w-5 h-5 text-green-600"
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

                            <div class="space-y-4">
                                <!-- Email -->
                                <div>
                                    <InputLabel for="email" value="Email" />
                                    <TextInput
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError
                                        :message="form.errors.email"
                                        class="mt-2"
                                    />
                                </div>

                                <!-- Phone -->
                                <div>
                                    <InputLabel for="phone" value="Phone" />
                                    <TextInput
                                        id="phone"
                                        v-model="form.phone"
                                        type="text"
                                        class="mt-1 block w-full"
                                        required
                                    />
                                    <InputError
                                        :message="form.errors.phone"
                                        class="mt-2"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Biography -->
                        <div class="bg-white rounded-lg shadow-sm border p-6">
                            <h3
                                class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2"
                            >
                                <svg
                                    class="w-5 h-5 text-purple-600"
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

                            <div>
                                <textarea
                                    id="biography"
                                    v-model="form.biography"
                                    rows="4"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    placeholder="Enter trainer biography"
                                ></textarea>
                                <InputError
                                    :message="form.errors.biography"
                                    class="mt-2"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Availability Schedule -->
                    <div class="space-y-6">
                        <div class="bg-white rounded-lg shadow-sm border p-6">
                            <div class="flex items-center gap-2 mb-4">
                                <svg
                                    class="w-5 h-5 text-blue-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>
                                <h3 class="text-lg font-semibold text-gray-900">
                                    Availability Schedule
                                </h3>
                            </div>

                            <p class="text-gray-600 text-sm mb-4">
                                Set the trainer's weekly availability for
                                classes and training sessions.
                            </p>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 mb-3">
                                    Weekly Schedule
                                </h4>
                                <div
                                    class="flex items-center gap-4 text-sm mb-4"
                                >
                                    <div class="flex items-center gap-1">
                                        <div
                                            class="w-3 h-3 bg-green-500 rounded-full"
                                        ></div>
                                        <span>Available</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <div
                                            class="w-3 h-3 bg-gray-400 rounded-full"
                                        ></div>
                                        <span>Unavailable</span>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <div
                                        v-for="(
                                            schedule, index
                                        ) in form.availability_schedule"
                                        :key="schedule.day"
                                        class="flex items-center justify-between p-3 bg-white rounded-lg border"
                                    >
                                        <div class="flex items-center gap-3">
                                            <svg
                                                class="w-4 h-4 text-gray-400"
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
                                            <span
                                                class="font-medium text-gray-900 w-20"
                                                >{{ schedule.day }}</span
                                            >
                                        </div>

                                        <div class="flex items-center gap-4">
                                            <!-- Time inputs (only show when available) -->
                                            <template v-if="schedule.available">
                                                <input
                                                    v-model="
                                                        schedule.start_time
                                                    "
                                                    type="time"
                                                    class="text-sm border-gray-300 rounded px-2 py-1"
                                                />
                                                <span class="text-gray-500"
                                                    >to</span
                                                >
                                                <input
                                                    v-model="schedule.end_time"
                                                    type="time"
                                                    class="text-sm border-gray-300 rounded px-2 py-1"
                                                />
                                            </template>

                                            <!-- Toggle Switch -->
                                            <label
                                                class="relative inline-flex items-center cursor-pointer"
                                            >
                                                <input
                                                    v-model="schedule.available"
                                                    type="checkbox"
                                                    class="sr-only peer"
                                                />
                                                <div
                                                    class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"
                                                ></div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-3 pt-6 border-t">
                    <SecondaryButton @click="goBack" type="button">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton
                        type="submit"
                        :disabled="form.processing"
                        class="bg-blue-600 hover:bg-blue-700"
                    >
                        {{ form.processing ? "Updating..." : "Update Trainer" }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
