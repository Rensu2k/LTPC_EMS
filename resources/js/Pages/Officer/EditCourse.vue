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
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Assign Trainers
                        </h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Select trainers who will be responsible for this
                            course
                        </p>

                        <div v-if="trainers.length > 0" class="space-y-3">
                            <div
                                v-for="trainer in trainers"
                                :key="trainer.id"
                                class="flex items-center p-4 border rounded-lg hover:bg-gray-50"
                            >
                                <input
                                    :id="`trainer-${trainer.id}`"
                                    v-model="form.assigned_trainers"
                                    :value="trainer.id"
                                    type="checkbox"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded mr-4"
                                />
                                <label
                                    :for="`trainer-${trainer.id}`"
                                    class="flex-1 cursor-pointer"
                                >
                                    <div class="flex items-center">
                                        <div
                                            class="bg-indigo-100 rounded-full p-2 mr-4"
                                        >
                                            <span
                                                class="text-indigo-700 font-semibold text-sm"
                                            >
                                                {{
                                                    getTrainerInitials(
                                                        trainer.name
                                                    )
                                                }}
                                            </span>
                                        </div>
                                        <div>
                                            <p
                                                class="font-medium text-gray-900"
                                            >
                                                {{ trainer.name }}
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                {{
                                                    trainer.expertise ||
                                                    "General Training"
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div v-else class="text-center py-6 border rounded-lg">
                            <svg
                                class="mx-auto h-12 w-12 text-gray-400"
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
                            <p class="mt-2 text-sm text-gray-500">
                                No trainers available
                            </p>
                            <p class="text-xs text-gray-400">
                                Add trainers to assign them to courses
                            </p>
                        </div>
                        <InputError
                            :message="form.errors.assigned_trainers"
                            class="mt-2"
                        />
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
            router.visit("/officer/courses");
        },
    });
};

const goBack = () => {
    router.visit("/officer/courses");
};
</script>
