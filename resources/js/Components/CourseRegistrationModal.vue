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
    trainers: Array,
});

const emit = defineEmits(["close", "submitted"]);

const form = useForm({
    name: "",
    description: "",
    duration: "",
    assigned_trainers: [],
    max_enrollments: 30,
    start_date: "",
    end_date: "",
});

const processing = ref(false);

const closeModal = () => {
    emit("close");
    form.reset();
    form.clearErrors();
};

const submitForm = () => {
    processing.value = true;

    form.post(route("officer.courses.store"), {
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

const toggleTrainer = (trainerId) => {
    const index = form.assigned_trainers.indexOf(trainerId);
    if (index > -1) {
        form.assigned_trainers.splice(index, 1);
    } else {
        form.assigned_trainers.push(trainerId);
    }
};

const isTrainerSelected = (trainerId) => {
    return form.assigned_trainers.includes(trainerId);
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
                    Add New Course
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
                Create a new training course in the system.
            </p>

            <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Course Name -->
                <div>
                    <InputLabel for="name" value="Course Name" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="Cookery"
                        required
                    />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <!-- Description -->
                <div>
                    <InputLabel for="description" value="Description" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Enter course description"
                    ></textarea>
                    <InputError
                        :message="form.errors.description"
                        class="mt-2"
                    />
                </div>

                <!-- Duration -->
                <div>
                    <InputLabel for="duration" value="Duration" />
                    <TextInput
                        id="duration"
                        v-model="form.duration"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="e.g. 8 weeks"
                        required
                    />
                    <InputError :message="form.errors.duration" class="mt-2" />
                </div>

                <!-- Course Settings -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Max Enrollments -->
                    <div>
                        <InputLabel
                            for="max_enrollments"
                            value="Max Enrollments"
                        />
                        <TextInput
                            id="max_enrollments"
                            v-model="form.max_enrollments"
                            type="number"
                            class="mt-1 block w-full"
                            min="1"
                            max="100"
                        />
                        <InputError
                            :message="form.errors.max_enrollments"
                            class="mt-2"
                        />
                    </div>

                    <!-- Start Date -->
                    <div>
                        <InputLabel
                            for="start_date"
                            value="Start Date (Optional)"
                        />
                        <TextInput
                            id="start_date"
                            v-model="form.start_date"
                            type="date"
                            class="mt-1 block w-full"
                        />
                        <InputError
                            :message="form.errors.start_date"
                            class="mt-2"
                        />
                    </div>
                </div>

                <!-- Assign Trainers -->
                <div>
                    <InputLabel value="Assign Trainers" />
                    <p class="text-sm text-gray-500 mb-3">
                        Select one or more trainers to assign to this course.
                    </p>

                    <div
                        class="max-h-48 overflow-y-auto border border-gray-200 rounded-lg"
                    >
                        <div
                            v-if="trainers && trainers.length > 0"
                            class="space-y-1 p-3"
                        >
                            <div
                                v-for="trainer in trainers"
                                :key="trainer.id"
                                class="flex items-center p-3 hover:bg-gray-50 rounded-lg cursor-pointer"
                                @click="toggleTrainer(trainer.id)"
                            >
                                <input
                                    type="checkbox"
                                    :checked="isTrainerSelected(trainer.id)"
                                    class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                    @click.stop="toggleTrainer(trainer.id)"
                                />
                                <div
                                    class="ml-3 flex items-center gap-3 flex-1"
                                >
                                    <div
                                        class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center text-white text-sm font-semibold"
                                    >
                                        {{
                                            trainer.name
                                                .split(" ")
                                                .map((n) => n[0])
                                                .join("")
                                        }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">
                                            {{ trainer.name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ trainer.expertise }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="p-6 text-center text-gray-500">
                            <p>No active trainers available</p>
                            <p class="text-sm">
                                Please add trainers first to assign them to
                                courses.
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="form.assigned_trainers.length > 0"
                        class="mt-2 text-sm text-gray-600"
                    >
                        {{ form.assigned_trainers.length }} trainer{{
                            form.assigned_trainers.length === 1 ? "" : "s"
                        }}
                        selected
                    </div>

                    <InputError
                        :message="form.errors.assigned_trainers"
                        class="mt-2"
                    />
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
                        {{ processing ? "Creating..." : "Create Course" }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
