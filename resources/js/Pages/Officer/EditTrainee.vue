<template>
    <Head title="Edit Trainee" />

    <AuthenticatedLayout>
        <div class="p-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        Edit Trainee
                    </h1>
                    <p class="text-gray-600 mt-2">Update trainee information</p>
                </div>
                <SecondaryButton @click="goBack">
                    Back to Trainees
                </SecondaryButton>
            </div>

            <div class="bg-white rounded-lg shadow-sm border p-6">
                <form @submit.prevent="submitForm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <InputLabel for="last_name" value="Last Name" />
                            <TextInput
                                id="last_name"
                                v-model="form.last_name"
                                type="text"
                                class="mt-1 block w-full"
                            />
                        </div>
                        <div>
                            <InputLabel for="first_name" value="First Name" />
                            <TextInput
                                id="first_name"
                                v-model="form.first_name"
                                type="text"
                                class="mt-1 block w-full"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <InputLabel
                                for="course_qualification"
                                value="Course"
                            />
                            <select
                                id="course_qualification"
                                v-model="form.course_qualification"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            >
                                <option value="">Select a course...</option>
                                <option
                                    v-for="course in courses"
                                    :key="course.id"
                                    :value="course.name"
                                >
                                    {{ course.name
                                    }}{{
                                        course.duration
                                            ? ` (${course.duration})`
                                            : ""
                                    }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="status" value="Status" />
                            <select
                                id="status"
                                v-model="form.status"
                                class="mt-1 block w-full border-gray-300 rounded-md"
                            >
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                                <option value="dropped">Dropped</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-4 pt-6">
                        <SecondaryButton @click="goBack" type="button">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton
                            type="submit"
                            :disabled="form.processing"
                        >
                            Update Trainee
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
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const props = defineProps({
    trainee: Object,
    courses: Array,
});

const form = useForm({
    last_name: props.trainee?.last_name || "",
    first_name: props.trainee?.first_name || "",
    course_qualification: props.trainee?.course_qualification || "",
    status: props.trainee?.status || "active",
});

const submitForm = () => {
    form.put(route("officer.trainees.update", props.trainee.id), {
        onSuccess: () => {
            router.visit("/officer/trainees");
        },
    });
};

const goBack = () => {
    router.visit("/officer/trainees");
};
</script>
