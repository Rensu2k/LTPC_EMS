<template>
    <Head title="Edit Program" />

    <AuthenticatedLayout>
        <div class="p-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        Edit Program
                    </h1>
                    <p class="text-gray-600 mt-2">
                        Update program information and settings
                    </p>
                </div>
                <SecondaryButton @click="goBack">
                    Back to Programs
                </SecondaryButton>
            </div>

            <div class="bg-white rounded-lg shadow-sm border p-6">
                <form @submit.prevent="submitForm">
                    <!-- Program Basic Information -->
                    <div class="border-b pb-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Program Information
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <InputLabel for="name" value="Program Name *" />
                                <TextInput
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                />
                                <InputError
                                    :message="form.errors.name"
                                    class="mt-2"
                                />
                            </div>
                            <div>
                                <InputLabel for="duration" value="Duration *" />
                                <TextInput
                                    id="duration"
                                    v-model="form.duration"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="e.g., 3 months, 6 weeks"
                                    required
                                />
                                <InputError
                                    :message="form.errors.duration"
                                    class="mt-2"
                                />
                            </div>
                        </div>

                        <div class="mb-4">
                            <InputLabel for="description" value="Description" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                placeholder="Brief description of the program..."
                            ></textarea>
                            <InputError
                                :message="form.errors.description"
                                class="mt-2"
                            />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="status" value="Status" />
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                >
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                <InputError
                                    :message="form.errors.status"
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
                                    v-model.number="form.enrollment_fee"
                                    type="number"
                                    step="0.01"
                                    class="mt-1 block w-full"
                                    min="0"
                                    placeholder="0.00"
                                />
                                <InputError
                                    :message="form.errors.enrollment_fee"
                                    class="mt-2"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Program Schedule -->
                    <div class="border-b pb-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Program Schedule (Optional)
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel
                                    for="start_date"
                                    value="Start Date"
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
                            <div>
                                <InputLabel for="end_date" value="End Date" />
                                <TextInput
                                    id="end_date"
                                    v-model="form.end_date"
                                    type="date"
                                    class="mt-1 block w-full"
                                />
                                <InputError
                                    :message="form.errors.end_date"
                                    class="mt-2"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Assign Trainers -->
                    <div class="mb-6">
                        <div
                            class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4"
                        >
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg
                                        class="h-5 w-5 text-blue-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        ></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h4
                                        class="text-sm font-medium text-blue-800"
                                    >
                                        Trainer Assignment
                                    </h4>
                                    <p class="text-sm text-blue-700 mt-1">
                                        Trainers can only be assigned to
                                        programs after the program has been
                                        created. This allows for better
                                        management of trainer schedules and
                                        program organization.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Assigned Trainers
                        </h3>

                        <div class="mb-4">
                            <InputLabel
                                for="assigned_trainers"
                                value="Select Trainers"
                            />
                            <select
                                id="assigned_trainers"
                                multiple
                                v-model="form.assigned_trainers"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                size="6"
                            >
                                <option
                                    v-for="trainer in trainers"
                                    :key="trainer.id"
                                    :value="trainer.id"
                                >
                                    {{ trainer.name }} -
                                    {{ trainer.expertise_string }}
                                </option>
                            </select>
                            <p class="text-sm text-gray-500 mt-1">
                                Hold Ctrl (or Cmd on Mac) to select multiple
                                trainers. Only trainers with relevant expertise
                                are shown.
                            </p>
                            <InputError
                                :message="form.errors.assigned_trainers"
                                class="mt-2"
                            />
                        </div>

                        <!-- Currently Assigned Trainers Display -->
                        <div
                            v-if="
                                form.assigned_trainers &&
                                form.assigned_trainers.length > 0
                            "
                            class="bg-gray-50 rounded-lg p-4"
                        >
                            <h4 class="text-sm font-medium text-gray-900 mb-2">
                                Currently Assigned Trainers:
                            </h4>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="trainerId in form.assigned_trainers"
                                    :key="trainerId"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800"
                                >
                                    {{
                                        getTrainerName(trainerId) ||
                                        "Unknown Trainer"
                                    }}
                                    <button
                                        type="button"
                                        @click="removeTrainer(trainerId)"
                                        class="ml-2 text-blue-600 hover:text-blue-800"
                                    >
                                        <svg
                                            class="h-4 w-4"
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
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end gap-3">
                        <SecondaryButton type="button" @click="goBack">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton
                            type="submit"
                            :disabled="form.processing"
                            class="bg-blue-600 hover:bg-blue-700"
                        >
                            <span v-if="form.processing">Updating...</span>
                            <span v-else>Update Program</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed } from "vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const props = defineProps({
    program: Object,
    trainers: Array,
});

const form = useForm({
    name: props.program?.name || "",
    description: props.program?.description || "",
    duration: props.program?.duration || "",
    status: props.program?.status || "active",
    enrollment_fee: props.program?.enrollment_fee || 0,
    start_date: props.program?.start_date || "",
    end_date: props.program?.end_date || "",
    assigned_trainers: props.program?.assigned_trainers || [],
});

const goBack = () => {
    router.visit(route("officer.programs"));
};

const submitForm = () => {
    form.put(route("officer.programs.update", props.program.program_id), {
        onSuccess: () => {
            router.visit(route("officer.programs"));
        },
    });
};

const getTrainerName = (trainerId) => {
    const trainer = props.trainers.find((t) => t.id === trainerId);
    return trainer ? trainer.name : null;
};

const removeTrainer = (trainerId) => {
    form.assigned_trainers = form.assigned_trainers.filter(
        (id) => id !== trainerId
    );
};
</script>
