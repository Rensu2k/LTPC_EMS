<script setup>
import { ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    show: Boolean,
    courses: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["close", "submitted"]);

const days = [
    "Monday",
    "Tuesday",
    "Wednesday",
    "Thursday",
    "Friday",
    "Saturday",
    "Sunday",
];

const form = useForm({
    full_name: "",
    expertise: "",
    email: "",
    phone: "",
    biography: "",
    availability_schedule: days.map((day) => ({
        day: day,
        available: false,
        start_time: "08:00",
        end_time: "17:00",
    })),
});

const processing = ref(false);

const closeModal = () => {
    emit("close");
    form.reset();
    form.clearErrors();
};

const submitForm = () => {
    processing.value = true;

    form.post(route("officer.trainers.store"), {
        onSuccess: () => {
            processing.value = false;
            emit("submitted");
            closeModal();
        },
        onError: () => {
            processing.value = false;
        },
    });
};

// Watch for modal show/hide to reset form
watch(
    () => props.show,
    (newValue) => {
        if (!newValue) {
            form.reset();
            form.clearErrors();
        }
    }
);
</script>

<template>
    <Modal :show="show" @close="closeModal" custom-width="80vw">
        <div class="p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-semibold text-gray-900">
                    Add New Trainer
                </h3>
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

            <p class="text-gray-600 mb-6">
                Enter the trainer information below.
            </p>

            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Full Name -->
                <div>
                    <InputLabel for="full_name" value="Full Name" />
                    <TextInput
                        id="full_name"
                        v-model="form.full_name"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="Jester G. Pastor"
                        required
                    />
                    <InputError :message="form.errors.full_name" class="mt-2" />
                </div>

                <!-- Expertise -->
                <div>
                    <InputLabel for="expertise" value="Expertise" />
                    <select
                        id="expertise"
                        v-model="form.expertise"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                        required
                    >
                        <option value="">Select area of expertise...</option>
                        <option
                            v-for="course in courses"
                            :key="course.id"
                            :value="course.name"
                        >
                            {{ course.name
                            }}{{
                                course.duration ? ` (${course.duration})` : ""
                            }}
                        </option>
                    </select>
                    <InputError :message="form.errors.expertise" class="mt-2" />
                </div>

                <!-- Email -->
                <div>
                    <InputLabel for="email" value="Email" />
                    <TextInput
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="mt-1 block w-full"
                        placeholder="jpastor1@ssct.edu.ph"
                        required
                    />
                    <InputError :message="form.errors.email" class="mt-2" />
                </div>

                <!-- Phone -->
                <div>
                    <InputLabel for="phone" value="Phone" />
                    <TextInput
                        id="phone"
                        v-model="form.phone"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="06453467938"
                        required
                    />
                    <InputError :message="form.errors.phone" class="mt-2" />
                </div>

                <!-- Biography -->
                <div>
                    <InputLabel for="biography" value="Biography" />
                    <textarea
                        id="biography"
                        v-model="form.biography"
                        rows="4"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Enter trainer biography"
                    ></textarea>
                    <InputError :message="form.errors.biography" class="mt-2" />
                </div>

                <!-- Availability Schedule -->
                <div class="border-t pt-6">
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
                        <h4 class="text-lg font-semibold text-gray-900">
                            Availability Schedule
                        </h4>
                    </div>

                    <p class="text-gray-600 text-sm mb-4">
                        Set the trainer's weekly availability for classes and
                        training sessions.
                    </p>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <h5 class="font-medium text-gray-900 mb-3">
                            Weekly Schedule
                        </h5>
                        <div class="flex items-center gap-4 text-sm mb-4">
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
                                            v-model="schedule.start_time"
                                            type="time"
                                            class="text-sm border-gray-300 rounded px-2 py-1"
                                        />
                                        <span class="text-gray-500">to</span>
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

                <!-- Action Buttons -->
                <div class="flex justify-end gap-3 pt-6 border-t">
                    <SecondaryButton @click="closeModal" type="button">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton
                        type="submit"
                        :disabled="processing"
                        class="bg-blue-600 hover:bg-blue-700"
                    >
                        {{ processing ? "Adding..." : "Add Trainer" }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
