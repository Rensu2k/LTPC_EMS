<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router, usePage } from "@inertia.js/vue3";
import { ref, computed } from "vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    courses: Array,
    flash: Object,
});

const user = computed(() => usePage().props.auth.user);
const isOfficer = computed(() => user.value?.role === "officer");
const isAdmin = computed(() => user.value?.role === "admin");

// Determine the correct API endpoint based on user role
const apiEndpoint = computed(() => {
    if (isOfficer.value) {
        return "/officer/courses";
    } else if (isAdmin.value) {
        return "/admin/courses";
    }
    return "/admin/courses"; // fallback
});

const showModal = ref(false);
const showDeleteModal = ref(false);
const editingCourse = ref(null);
const deletingCourse = ref(null);
const searchQuery = ref("");

const form = useForm({
    course_id: "",
    name: "",
    description: "",
    duration: "",
    prerequisites: "",
    max_students: "",
    enrollment_fee: "",
    status: "active",
});

const filteredCourses = computed(() => {
    let filtered = props.courses || [];

    if (searchQuery.value) {
        filtered = filtered.filter((course) =>
            course.name?.toLowerCase().includes(searchQuery.value.toLowerCase())
        );
    }

    return filtered;
});

const openCreateModal = () => {
    if (!isOfficer.value && !isAdmin.value) return;
    form.reset();
    editingCourse.value = null;
    showModal.value = true;
};

const openEditModal = (course) => {
    if (!isOfficer.value && !isAdmin.value) return;
    editingCourse.value = course;
    form.course_id = course.course_id;
    form.name = course.name;
    form.description = course.description;
    form.duration = course.duration;
    form.prerequisites = course.prerequisites;
    form.max_students = course.max_students;
    form.enrollment_fee = course.enrollment_fee;
    showModal.value = true;
};

const openDeleteModal = (course) => {
    if (!isOfficer.value && !isAdmin.value) return;
    deletingCourse.value = course;
    showDeleteModal.value = true;
};

const submitForm = () => {
    if (!isOfficer.value && !isAdmin.value) return;
    if (editingCourse.value) {
        form.put(`${apiEndpoint.value}/${editingCourse.value.course_id}`, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post(apiEndpoint.value, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const deleteCourse = () => {
    if (!isOfficer.value && !isAdmin.value) return;
    router.delete(`${apiEndpoint.value}/${deletingCourse.value.course_id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
            deletingCourse.value = null;
        },
    });
};

const formatCurrency = (amount) => {
    if (!amount) return "Free";
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(amount);
};

const exportCourseReport = () => {
    // Implement export functionality
    console.log("Exporting course report...");
};

const getStatusColor = (status) => {
    const colors = {
        active: "bg-green-100 text-green-800",
        completed: "bg-blue-100 text-blue-800",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};
</script>

<template>
    <Head title="Courses Management" />
    <AuthenticatedLayout>
        <div class="py-8 px-8 bg-gray-50 min-h-screen">
            <div
                class="bg-white rounded-xl shadow-sm overflow-hidden animate-fade-in border border-gray-100"
            >
                <!-- Header Section -->
                <div
                    class="p-6 border-b border-gray-200 bg-gradient-to-r from-green-50 to-emerald-50 relative"
                >
                    <div
                        class="absolute bottom-0 left-0 w-full h-0.5 bg-gradient-to-r from-green-500 to-emerald-500"
                    ></div>
                    <div
                        class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                    >
                        <div>
                            <h3
                                class="text-lg font-semibold text-green-900 relative pb-2 after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-20 after:h-0.5 after:bg-gradient-to-r after:rounded"
                            >
                                Course Progress Monitoring
                            </h3>
                            <!-- <p class="text-sm text-gray-500">Monitor course capacity and progress (maximum 25 trainees per course)</p> -->
                        </div>
                        <div class="flex space-x-3">
                            <SecondaryButton
                                v-if="isOfficer || isAdmin"
                                @click="openCreateModal"
                                class="bg-gradient-to-r from-green-600 to-emerald-600 text-white border-none hover:from-green-700 hover:to-emerald-700 transition-all duration-300"
                            >
                                Add New Course
                            </SecondaryButton>
                            <SecondaryButton
                                @click="exportCourseReport"
                                class="bg-gradient-to-r from-green-600 to-emerald-600 text-white border-none hover:from-green-700 hover:to-emerald-700 transition-all duration-300"
                            >
                                📄 Export Course Report
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Filters Section -->
                <div
                    class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 border-b border-gray-200"
                >
                    <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                        <div class="relative">
                            <InputLabel for="search" value="Search Courses" />
                            <TextInput
                                id="search"
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by course name"
                                class="mt-1 block w-full transition-all duration-300 border-2 border-transparent focus:border-green-500 focus:ring-2 focus:ring-green-200 hover:border-green-300"
                            />
                        </div>
                    </div>
                </div>

                <!-- Role-based notification -->
                <div
                    v-if="!isOfficer && !isAdmin"
                    class="p-4 bg-yellow-50 border-l-4 border-yellow-400"
                >
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg
                                class="h-5 w-5 text-yellow-400"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Only officers and admins can add, edit, or
                                delete courses. You have view-only access.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Summary Stats -->
                <div class="p-6 border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                        <div
                            class="bg-white-50 p-4 rounded-lg border border-gray-200"
                        >
                            <div class="text-sm font-medium text-green-600">
                                Total Courses
                            </div>
                            <div class="text-2xl font-bold text-green-900">
                                {{ courses?.length || 0 }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table Section -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Course ID
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Course
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Duration
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Max Students
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Fee
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Status
                                </th>
                                <th
                                    v-if="isOfficer || isAdmin"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="course in filteredCourses"
                                :key="course.course_id"
                                 class="hover:bg-gray-50 transition-colors duration-200 group"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{ course.course_id }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{ course.name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ course.description }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ course.duration }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ course.max_students }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{
                                            formatCurrency(
                                                course.enrollment_fee
                                            )
                                        }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="getStatusColor(course.status)"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{ course.status }}
                                    </span>
                                </td>
                                <td
                                    v-if="isOfficer || isAdmin"
                                    class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                                >
                                    <div class="flex justify-end space-x-2">
                                        <button
                                            @click="openEditModal(course)"
                                            class="text-blue-600 hover:text-blue-800 transition-colors duration-150"
                                        >
                                            ✏️ Edit
                                        </button>
                                        <button
                                            @click="openDeleteModal(course)"
                                            class="text-red-600 hover:text-red-800 transition-colors duration-150"
                                        >
                                            🗑️ Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div
                    v-if="!filteredCourses.length"
                    class="text-center py-12 bg-gray-50"
                >
                    <div class="text-gray-400 text-6xl mb-4">📚</div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                        No courses found
                    </h3>
                    <p class="text-gray-500 mb-4">
                        {{
                            searchQuery
                                ? "Try adjusting your search criteria."
                                : "Get started by adding your first course."
                        }}
                    </p>
                    <PrimaryButton
                        v-if="(isOfficer || isAdmin) && !searchQuery"
                        @click="openCreateModal"
                        class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700"
                    >
                        Add First Course
                    </PrimaryButton>
                </div>
            </div>
        </div>

        <!-- Course Modal -->
        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    {{ editingCourse ? "Edit Course" : "Add New Course" }}
                </h2>

                <form @submit.prevent="submitForm" class="mt-6 space-y-6">
                    <div>
                        <InputLabel for="course_id" value="Course ID" />
                        <TextInput
                            id="course_id"
                            v-model="form.course_id"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Auto-generated if left blank"
                        />
                        <InputError :message="form.errors.course_id" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="name" value="Course Name" />
                        <TextInput
                            id="name"
                            ref="nameInput"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            placeholder="Enter course name"
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="description" value="Description" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="3"
                            class="mt-1 block w-full"
                            placeholder="Enter course description"
                        ></textarea>
                        <InputError
                            :message="form.errors.description"
                            class="mt-2"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="duration" value="Duration" />
                            <TextInput
                                id="duration"
                                v-model="form.duration"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="e.g., 3 months"
                            />
                            <InputError
                                :message="form.errors.duration"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel
                                for="max_students"
                                value="Max Students"
                            />
                            <TextInput
                                id="max_students"
                                v-model="form.max_students"
                                type="number"
                                class="mt-1 block w-full"
                                placeholder="25"
                            />
                            <InputError
                                :message="form.errors.max_students"
                                class="mt-2"
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel
                            for="enrollment_fee"
                            value="Enrollment Fee (PHP)"
                        />
                        <TextInput
                            id="enrollment_fee"
                            v-model="form.enrollment_fee"
                            type="number"
                            step="0.01"
                            class="mt-1 block w-full"
                            placeholder="0.00"
                        />
                        <InputError
                            :message="form.errors.enrollment_fee"
                            class="mt-2"
                        />
                    </div>

                    <div class="flex items-center justify-end mt-6 space-x-3">
                        <SecondaryButton @click="showModal = false">
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                            class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700"
                        >
                            {{
                                editingCourse
                                    ? "Update Course"
                                    : "Create Course"
                            }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">Delete Course</h2>

                <p class="mt-1 text-sm text-gray-600">
                    Are you sure you want to delete this course? This action
                    cannot be undone.
                </p>

                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="showDeleteModal = false">
                        Cancel
                    </SecondaryButton>
                    <DangerButton @click="deleteCourse">
                        Delete Course
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fade-in {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 0.5s ease-out;
}
</style>
