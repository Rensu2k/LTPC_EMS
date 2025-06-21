<template>
    <Modal :show="show" @close="closeModal" custom-width="80vw">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Course Details</h2>
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

            <div v-if="course" class="space-y-6">
                <!-- Course Header -->
                <div
                    class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6"
                >
                    <div class="flex justify-between items-start">
                        <div>
                            <h3
                                class="text-xl font-semibold text-gray-900 mb-2"
                            >
                                {{ course.name }}
                            </h3>
                            <p class="text-gray-600 mb-4">
                                {{
                                    course.description ||
                                    "No description available"
                                }}
                            </p>
                            <div class="flex items-center gap-4">
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                    :class="{
                                        'bg-green-100 text-green-800':
                                            course.status === 'active',
                                        'bg-gray-100 text-gray-800':
                                            course.status === 'inactive',
                                    }"
                                >
                                    {{
                                        course.status?.charAt(0).toUpperCase() +
                                            course.status?.slice(1) || "Active"
                                    }}
                                </span>
                                <span class="text-sm text-gray-500">
                                    Course ID: {{ course.course_id }}
                                </span>
                            </div>
                        </div>
                        <button
                            @click="editCourse"
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
                            Edit Course
                        </button>
                    </div>
                </div>

                <!-- Course Information Grid -->
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
                                    {{ course.duration }}
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
                                <p class="text-sm text-gray-500">Enrollments</p>
                                <p class="text-lg font-semibold text-gray-900">
                                    {{ course.enrollments || 0 }} /
                                    {{ course.max_enrollments || "Unlimited" }}
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
                                        trainer.program || "General Training"
                                    }}
                                </p>
                            </div>
                            <div class="flex items-center">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                >
                                    Active
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-6">
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
                            No trainers assigned to this course
                        </p>
                        <button
                            @click="editCourse"
                            class="mt-2 text-sm text-blue-600 hover:text-blue-700"
                        >
                            Assign trainers
                        </button>
                    </div>
                </div>

                <!-- Course Schedule (if available) -->
                <div
                    v-if="course.start_date || course.end_date"
                    class="bg-white border rounded-lg p-6"
                >
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">
                        Course Schedule
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-if="course.start_date">
                            <p class="text-sm text-gray-500">Start Date</p>
                            <p class="text-lg font-medium text-gray-900">
                                {{ formatDate(course.start_date) }}
                            </p>
                        </div>
                        <div v-if="course.end_date">
                            <p class="text-sm text-gray-500">End Date</p>
                            <p class="text-lg font-medium text-gray-900">
                                {{ formatDate(course.end_date) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="bg-white border rounded-lg p-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4">
                        Additional Information
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Created</p>
                            <p class="font-medium text-gray-900">
                                {{ formatDate(course.created_at) }}
                            </p>
                        </div>
                        <div v-if="course.updated_at">
                            <p class="text-gray-500">Last Updated</p>
                            <p class="font-medium text-gray-900">
                                {{ formatDate(course.updated_at) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Course Selected -->
            <div v-else class="text-center py-12">
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
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    />
                </svg>
                <p class="mt-2 text-sm text-gray-500">No course selected</p>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end gap-3 pt-6 border-t">
                <SecondaryButton @click="closeModal"> Close </SecondaryButton>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { computed } from "vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    course: {
        type: Object,
        default: null,
    },
    trainers: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["close", "edit"]);

const assignedTrainers = computed(() => {
    if (!props.course?.assigned_trainers || !props.trainers) {
        return [];
    }

    return props.trainers.filter((trainer) =>
        props.course.assigned_trainers.includes(trainer.id)
    );
});

const getTrainerInitials = (name) => {
    return name
        .split(" ")
        .map((n) => n[0])
        .join("")
        .toUpperCase();
};

const formatDate = (date) => {
    if (!date) return "N/A";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
};

const closeModal = () => {
    emit("close");
};

const editCourse = () => {
    emit("edit", props.course);
};
</script>
