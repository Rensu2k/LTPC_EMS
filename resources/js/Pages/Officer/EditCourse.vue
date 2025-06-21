<template>
    <Head title="Edit Course" />

    <AuthenticatedLayout>
        <div class="p-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        Edit Course
                    </h1>
                    <p class="text-gray-600 mt-2">
                        Update course information and settings
                    </p>
                </div>
                <SecondaryButton @click="goBack">
                    Back to Courses
                </SecondaryButton>
            </div>

            <div class="bg-white rounded-lg shadow-sm border p-6">
                <form @submit.prevent="submitForm">
                    <!-- Course Basic Information -->
                    <div class="border-b pb-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Course Information
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <InputLabel for="name" value="Course Name *" />
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
                                placeholder="Brief description of the course..."
                            ></textarea>
                            <InputError
                                :message="form.errors.description"
                                class="mt-2"
                            />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                                    for="max_enrollments"
                                    value="Max Enrollments"
                                />
                                <TextInput
                                    id="max_enrollments"
                                    v-model.number="form.max_enrollments"
                                    type="number"
                                    class="mt-1 block w-full"
                                    min="1"
                                    max="100"
                                    placeholder="e.g., 30"
                                />
                                <InputError
                                    :message="form.errors.max_enrollments"
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

                    <!-- Course Schedule -->
                    <div class="border-b pb-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Course Schedule (Optional)
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
                                        Trainers can only be assigned to courses
                                        after the course has been created. This
                                        helps ensure proper course setup and
                                        trainer matching.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Assign Trainers
                        </h3>
                        <p class="text-sm text-gray-600 mb-2">
                            Select trainers who will be responsible for this
                            course
                        </p>
                        <p class="text-xs text-blue-600 mb-4">
                            Only showing trainers whose program matches "{{
                                form.name
                            }}"
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            Available trainers:
                            <span class="font-medium text-green-600">
                                {{
                                    filteredTrainers.length
                                }}
                            </span>
                        </p>
                    </div>

                    <div class="space-y-2 max-h-40 overflow-y-auto">
                        <div
                            v-for="trainer in filteredTrainers"
                            :key="trainer.id"
                            class="flex items-center justify-between p-2 bg-gray-50 rounded-lg"
                        >
                            <div class="flex items-center space-x-3">
                                <div class="h-8 w-8 rounded-full bg-green-600 flex items-center justify-center text-white text-xs font-semibold">
                                    {{
                                        trainer.full_name
                                            ?.split(" ")
                                            .map((n) => n[0])
                                            .join("") || ""
                                    }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ trainer.full_name }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{
                                            trainer.program ||
                                                "General Training"
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="filteredTrainers.length === 0" class="text-center py-4">
                        <p class="text-sm text-gray-500">
                            No trainers available with matching program
                        </p>
                        <p class="text-xs text-gray-400 mt-1">
                            Only trainers whose program matches "{{
                                form.name
                            }}" are shown. Add a new trainer with matching
                            program or check existing trainer
                        </p>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end gap-4 pt-6 border-t">
                        <SecondaryButton @click="goBack" type="button">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton
                            type="submit"
                            :disabled="form.processing"
                        >
                            <span v-if="form.processing">Updating...</span>
                            <span v-else>Update Course</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { useForm, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const props = defineProps({
    course: Object,
    trainers: Array,
});

const form = useForm({
    name: props.course?.name || "",
    description: props.course?.description || "",
    duration: props.course?.duration || "",
    status: props.course?.status || "active",
    max_enrollments: props.course?.max_enrollments || null,
    enrollment_fee: props.course?.enrollment_fee || null,
    start_date: props.course?.start_date || "",
    end_date: props.course?.end_date || "",
    assigned_trainers: props.course?.assigned_trainers || [],
});

const getTrainerInitials = (name) => {
    return name
        .split(" ")
        .map((n) => n[0])
        .join("")
        .toUpperCase();
};

const submitForm = () => {
    form.put(route("officer.courses.update", props.course.id), {
        onSuccess: () => {
            router.visit(route("officer.courses"));
        },
    });
};

const goBack = () => {
    router.visit(route("officer.courses"));
};
</script>
