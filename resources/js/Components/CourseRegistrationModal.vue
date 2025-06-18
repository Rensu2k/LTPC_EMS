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
});

const emit = defineEmits(["close", "submitted"]);

const form = useForm({
    name: "",
    description: "",
    duration: "",
    enrollment_fee: "",
    max_enrollments: "30",
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
    <Modal
        :show="show"
        @close="closeModal"
        custom-width="80vw"
        :close-on-click-outside="false"
    >
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

            <div class="border-b pb-4 mb-6">
                <h3 class="text-lg font-semibold text-gray-900">
                    Create New Course
                </h3>
                <p class="text-sm text-gray-600 mt-2">
                    Create a new course. You can assign trainers to this course
                    after it's created by editing the course.
                </p>
            </div>

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

                <!-- Enrollment Fee -->
                <div>
                    <InputLabel for="enrollment_fee" value="Enrollment Fee" />
                    <TextInput
                        id="enrollment_fee"
                        v-model="form.enrollment_fee"
                        type="number"
                        step="0.01"
                        class="mt-1 block w-full"
                        placeholder="0.00"
                    />
                    <p class="text-sm text-gray-500 mt-1">
                        Leave blank or set to 0 for free courses
                    </p>
                    <InputError
                        :message="form.errors.enrollment_fee"
                        class="mt-2"
                    />
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
