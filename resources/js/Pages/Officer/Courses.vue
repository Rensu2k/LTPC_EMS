<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import CourseRegistrationModal from "@/Components/CourseRegistrationModal.vue";
import CourseDetailsModal from "@/Components/CourseDetailsModal.vue";
import DeleteConfirmationModal from "@/Components/DeleteConfirmationModal.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    courses: Array,
    trainers: Array,
});

const searchQuery = ref("");
const showRegistrationModal = ref(false);
const showDetailsModal = ref(false);
const showDeleteModal = ref(false);
const selectedCourse = ref(null);
const processing = ref(false);

// Process courses data to match the expected format
const coursesList = ref(
    props.courses?.map((course) => ({
        id: course.id,
        name: course.name,
        description: course.description,
        duration: course.duration,
        status: course.status,
        enrollments: course.enrollments || 0,
        max_enrollments: course.max_enrollments,
        assigned_trainers: course.assigned_trainers || [],
        start_date: course.start_date,
        end_date: course.end_date,
    })) || []
);

const addNewCourse = () => {
    showRegistrationModal.value = true;
};

const closeRegistrationModal = () => {
    showRegistrationModal.value = false;
};

const onCourseSubmitted = () => {
    // Refresh the page to show the new course
    window.location.reload();
};

const viewCourse = (course) => {
    selectedCourse.value = course;
    showDetailsModal.value = true;
};

const editCourse = (course) => {
    router.visit(`/officer/courses/${course.id}/edit`);
};

const deleteCourse = (course) => {
    selectedCourse.value = course;
    showDeleteModal.value = true;
};

const closeDetailsModal = () => {
    showDetailsModal.value = false;
    selectedCourse.value = null;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedCourse.value = null;
};

const handleEditFromDetails = (course) => {
    closeDetailsModal();
    editCourse(course);
};

const confirmDelete = () => {
    if (!selectedCourse.value) return;

    processing.value = true;

    router.delete(route("officer.courses.destroy", selectedCourse.value.id), {
        onSuccess: () => {
            processing.value = false;
            closeDeleteModal();
            // Refresh to show updated list
            window.location.reload();
        },
        onError: () => {
            processing.value = false;
            alert("Failed to delete course. Please try again.");
        },
    });
};

const getAssignedTrainers = (assignedTrainerIds) => {
    if (!assignedTrainerIds || assignedTrainerIds.length === 0) {
        return "No trainers assigned";
    }

    const assignedTrainers = props.trainers.filter((trainer) =>
        assignedTrainerIds.includes(trainer.id)
    );

    if (assignedTrainers.length === 0) {
        return "No trainers assigned";
    }

    if (assignedTrainers.length === 1) {
        return assignedTrainers[0].name;
    }

    return `${assignedTrainers[0].name} +${assignedTrainers.length - 1} more`;
};

const filteredCourses = ref(
    coursesList.value.filter(
        (course) =>
            course.name
                .toLowerCase()
                .includes(searchQuery.value.toLowerCase()) ||
            course.description
                .toLowerCase()
                .includes(searchQuery.value.toLowerCase())
    )
);

// Update filtered courses when search query changes
const updateFilter = () => {
    filteredCourses.value = coursesList.value.filter(
        (course) =>
            course.name
                .toLowerCase()
                .includes(searchQuery.value.toLowerCase()) ||
            course.description
                .toLowerCase()
                .includes(searchQuery.value.toLowerCase())
    );
};
</script>

<template>
    <Head title="Courses" />

    <AuthenticatedLayout>
        <div class="p-8">
            <!-- Header Section -->
            <div class="flex justify-between items-center mb-8 animate-fade-in">
                <h1 class="text-3xl font-bold text-gray-900">
                    Courses Management
                </h1>
                <div>
                    <button
                        @click="addNewCourse"
                        class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                            />
                        </svg>
                        Add New Course
                    </button>
                </div>
            </div>

            <!-- Search Section -->
            <div
                class="bg-white rounded-lg shadow-sm border p-6 mb-6 animate-fade-in"
            >
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900">
                        All Courses ({{ filteredCourses.length }})
                    </h2>
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            @input="updateFilter"
                            type="text"
                            placeholder="Search courses..."
                            class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-80"
                        />
                        <svg
                            class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                            />
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Courses Table -->
            <div
                class="bg-white rounded-lg shadow-sm border overflow-hidden animate-fade-in"
            >
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    ID
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Name
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Description
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Duration
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Trainers
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Enrollments
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="course in filteredCourses"
                                :key="course.id"
                                class="hover:bg-gray-50"
                            >
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                >
                                    C{{ String(course.id).padStart(3, "0") }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                >
                                    {{ course.name }}
                                </td>
                                <td
                                    class="px-6 py-4 text-sm text-gray-500 max-w-xs"
                                >
                                    <div
                                        class="truncate"
                                        :title="course.description"
                                    >
                                        {{
                                            course.description ||
                                            "No description"
                                        }}
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    {{ course.duration }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                >
                                    {{
                                        getAssignedTrainers(
                                            course.assigned_trainers
                                        )
                                    }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="{
                                            'bg-green-100 text-green-800':
                                                course.status === 'active',
                                            'bg-gray-100 text-gray-800':
                                                course.status === 'inactive',
                                        }"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{
                                            course.status
                                                ?.charAt(0)
                                                .toUpperCase() +
                                                course.status?.slice(1) ||
                                            "Active"
                                        }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-center"
                                >
                                    <div class="flex flex-col items-center">
                                        <span class="font-semibold">{{
                                            course.enrollments
                                        }}</span>
                                        <span class="text-xs text-gray-500"
                                            >/
                                            {{ course.max_enrollments }}</span
                                        >
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium"
                                >
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="viewCourse(course)"
                                            class="text-green-600 hover:text-green-900 p-1 rounded"
                                            title="View Details"
                                        >
                                            <svg
                                                class="h-5 w-5"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                />
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                                />
                                            </svg>
                                        </button>
                                        <button
                                            @click="editCourse(course)"
                                            class="text-blue-600 hover:text-blue-900 p-1 rounded"
                                            title="Edit"
                                        >
                                            <svg
                                                class="h-5 w-5"
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
                                        </button>
                                        <button
                                            @click="deleteCourse(course)"
                                            class="text-red-600 hover:text-red-900 p-1 rounded"
                                            title="Delete"
                                            :disabled="processing"
                                        >
                                            <svg
                                                class="h-5 w-5"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div
                    v-if="filteredCourses.length === 0"
                    class="text-center py-12"
                >
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
                            d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"
                        />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">
                        No courses found
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        {{
                            searchQuery
                                ? "Try adjusting your search terms."
                                : "Get started by creating a new course."
                        }}
                    </p>
                    <div class="mt-6" v-if="!searchQuery">
                        <button
                            @click="addNewCourse"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                        >
                            <svg
                                class="-ml-1 mr-2 h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                />
                            </svg>
                            Add New Course
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Registration Modal -->
        <CourseRegistrationModal
            :show="showRegistrationModal"
            :trainers="trainers"
            @close="closeRegistrationModal"
            @submitted="onCourseSubmitted"
        />

        <!-- Course Details Modal -->
        <CourseDetailsModal
            :show="showDetailsModal"
            :course="selectedCourse"
            :trainers="trainers"
            @close="closeDetailsModal"
            @edit="handleEditFromDetails"
        />

        <!-- Delete Confirmation Modal -->
        <DeleteConfirmationModal
            :show="showDeleteModal"
            :item="selectedCourse"
            item-type="course"
            title="Delete Course"
            :message="`Are you sure you want to permanently delete this course? This action cannot be undone.`"
            @close="closeDeleteModal"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
