<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    assessments: Array,
    flash: Object,
});

const showModal = ref(false);
const showDeleteModal = ref(false);
const showResultsModal = ref(false);
const editingAssessment = ref(null);
const deletingAssessment = ref(null);
const viewingAssessment = ref(null);
const searchQuery = ref("");
const selectedCourse = ref("");
const selectedStatus = ref("");

const form = useForm({
    title: "",
    description: "",
    course_id: "",
    trainer_id: "",
    assessment_date: "",
    duration_minutes: "",
    total_marks: "",
    passing_marks: "",
    assessment_type: "written",
    status: "scheduled",
});

const filteredAssessments = computed(() => {
    let filtered = props.assessments || [];

    if (searchQuery.value) {
        filtered = filtered.filter(
            (assessment) =>
                assessment.title
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                assessment.course?.name
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                assessment.trainer?.first_name
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                assessment.trainer?.last_name
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase())
        );
    }

    if (selectedCourse.value) {
        filtered = filtered.filter(
            (assessment) => assessment.course_id == selectedCourse.value
        );
    }

    if (selectedStatus.value) {
        filtered = filtered.filter(
            (assessment) => assessment.status === selectedStatus.value
        );
    }

    return filtered;
});

const courses = computed(() => {
    const uniqueCourses = [
        ...new Set(
            props.assessments
                ?.map((assessment) => assessment.course)
                .filter(Boolean)
        ),
    ];
    return uniqueCourses;
});

const openCreateModal = () => {
    form.reset();
    form.assessment_date = new Date().toISOString().split("T")[0];
    editingAssessment.value = null;
    showModal.value = true;
};

const openEditModal = (assessment) => {
    editingAssessment.value = assessment;
    form.title = assessment.title;
    form.description = assessment.description;
    form.course_id = assessment.course_id;
    form.trainer_id = assessment.trainer_id;
    form.assessment_date = assessment.assessment_date;
    form.duration_minutes = assessment.duration_minutes;
    form.total_marks = assessment.total_marks;
    form.passing_marks = assessment.passing_marks;
    form.assessment_type = assessment.assessment_type;
    form.status = assessment.status;
    showModal.value = true;
};

const openResultsModal = (assessment) => {
    viewingAssessment.value = assessment;
    showResultsModal.value = true;
};

const openDeleteModal = (assessment) => {
    deletingAssessment.value = assessment;
    showDeleteModal.value = true;
};

const submitForm = () => {
    if (editingAssessment.value) {
        form.put(`/admin/assessments/${editingAssessment.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post("/admin/assessments", {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const deleteAssessment = () => {
    router.delete(`/admin/assessments/${deletingAssessment.value.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
            deletingAssessment.value = null;
        },
    });
};

const getStatusColor = (status) => {
    const colors = {
        competent: "bg-green-100 text-green-800",
        incompetent: "bg-red-100 text-red-800",
        no_assessment: "bg-gray-100 text-gray-800",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};

const getTypeColor = (type) => {
    const colors = {
        written: "bg-purple-100 text-purple-800",
        practical: "bg-orange-100 text-orange-800",
        oral: "bg-green-100 text-green-800",
        project: "bg-blue-100 text-blue-800",
    };
    return colors[type] || "bg-gray-100 text-gray-800";
};

const getAssessmentIcon = (type) => {
    const icons = {
        written: "📝",
        practical: "🔧",
        oral: "🗣️",
        project: "📋",
    };
    return icons[type] || "📊";
};

const formatDuration = (minutes) => {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    if (hours > 0) {
        return `${hours}h ${mins}m`;
    }
    return `${mins}m`;
};

const getPassingPercentage = (assessment) => {
    if (!assessment.total_marks || !assessment.passing_marks) return 0;
    return Math.round(
        (assessment.passing_marks / assessment.total_marks) * 100
    );
};

const exportAssessmentResults = () => {
    console.log("Exporting assessment results...");
};
</script>

<template>
    <Head title="Assessment Management" />
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
                                class="text-lg font-semibold text-green-900 relative pb-2 after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-16 after:h-0.5 after:bg-gradient-to-r after:rounded"
                            >
                                Assessment Results Monitoring
                            </h3>
                        </div>
                        <div class="flex space-x-3">
                            <SecondaryButton
                                @click="exportAssessmentResults"
                                class="bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200 hover:border-gray-400 transition-all duration-300"
                            >
                                📄 Export PDF Report
                            </SecondaryButton>
                        </div>
                    </div>
                </div>

                <!-- Filters Section -->
                <div
                    class="p-6 bg-gradient-to-br from-gray-50 to-gray-100 border-b border-gray-200"
                >
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="relative">
                            <InputLabel
                                for="search"
                                value="Search Assessments"
                            />
                            <TextInput
                                id="search"
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by trainer name"
                                class="mt-1 block w-full transition-all duration-300 border-2 border-transparent focus:border-green-500 focus:ring-2 focus:ring-green-200 hover:border-green-300"
                            />
                        </div>
                        <div>
                            <InputLabel
                                for="course-filter"
                                value="Filter by Course"
                            />
                            <select
                                id="course-filter"
                                v-model="selectedCourse"
                                class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            >
                                <option value="">All Courses</option>
                                <option
                                    v-for="course in courses"
                                    :key="course.course_id"
                                    :value="course.course_id"
                                >
                                    {{ course.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel
                                for="status-filter"
                                value="Filter by Status"
                            />
                            <select
                                id="status-filter"
                                v-model="selectedStatus"
                                class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            >
                                <option value="">All Statuses</option>
                                <option value="competent">Competent</option>
                                <option value="incompetent">
                                    Not Yet Competent
                                </option>
                                <option value="no_assessment">
                                    No Assessment
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="p-6 border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div
                            class="bg-green-50 p-4 rounded-lg border border-green-200"
                        >
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center"
                                    >
                                        <span class="text-white text-xs"
                                            >✓</span
                                        >
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p
                                        class="text-xs font-medium text-green-600"
                                    >
                                        Competent
                                    </p>
                                    <p
                                        class="text-lg font-semibold text-green-900"
                                    >
                                        {{
                                            filteredAssessments.filter(
                                                (a) => a.status === "competent"
                                            ).length
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-red-50 p-4 rounded-lg border border-red-200"
                        >
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center"
                                    >
                                        <span class="text-white text-xs"
                                            >✗</span
                                        >
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p class="text-xs font-medium text-red-600">
                                        Not Yet Competent
                                    </p>
                                    <p
                                        class="text-lg font-semibold text-red-900"
                                    >
                                        {{
                                            filteredAssessments.filter(
                                                (a) =>
                                                    a.status === "incompetent"
                                            ).length
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-gray-50 p-4 rounded-lg border border-gray-200"
                        >
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-8 h-8 bg-gray-500 rounded-full flex items-center justify-center"
                                    >
                                        <span class="text-white text-xs"
                                            >—</span
                                        >
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p
                                        class="text-xs font-medium text-gray-600"
                                    >
                                        No Assessment
                                    </p>
                                    <p
                                        class="text-lg font-semibold text-gray-900"
                                    >
                                        {{
                                            filteredAssessments.filter(
                                                (a) =>
                                                    a.status === "no_assessment"
                                            ).length
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-blue-50 p-4 rounded-lg border border-blue-200"
                        >
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center"
                                    >
                                        <span class="text-white text-xs"
                                            >📊</span
                                        >
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p
                                        class="text-xs font-medium text-blue-600"
                                    >
                                        Total Assessments
                                    </p>
                                    <p
                                        class="text-lg font-semibold text-blue-900"
                                    >
                                        {{ filteredAssessments.length }}
                                    </p>
                                </div>
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
                                    Assessment Details
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Course/Trainer
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Type
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Duration
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="assessment in filteredAssessments"
                                :key="assessment.id"
                                class="hover:bg-gray-50 transition-colors duration-200 group"
                            >
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div
                                                class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-medium relative overflow-hidden"
                                            >
                                                <span class="relative z-10">
                                                    {{
                                                        getAssessmentIcon(
                                                            assessment.assessment_type
                                                        )
                                                    }}
                                                </span>
                                                <div
                                                    class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-500"
                                                ></div>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div
                                                class="text-sm font-medium text-gray-900 group-hover:text-green-600 transition-colors duration-200"
                                            >
                                                {{ assessment.title }}
                                            </div>
                                            <div
                                                class="text-sm text-gray-500 group-hover:text-green-500 transition-colors duration-200"
                                            >
                                                {{ assessment.assessment_date }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ assessment.course?.name || "N/A" }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ assessment.trainer?.first_name }}
                                        {{ assessment.trainer?.last_name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="
                                            getTypeColor(
                                                assessment.assessment_type
                                            )
                                        "
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full border"
                                    >
                                        {{ assessment.assessment_type }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    {{
                                        formatDuration(
                                            assessment.duration_minutes
                                        )
                                    }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="
                                            getStatusColor(assessment.status)
                                        "
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full border"
                                    >
                                        {{
                                            assessment.status === "competent"
                                                ? "Competent"
                                                : assessment.status ===
                                                  "incompetent"
                                                ? "Not Yet Competent"
                                                : "No Assessment"
                                        }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                                >
                                    <div class="flex justify-end space-x-2">
                                        <button
                                            @click="
                                                openResultsModal(assessment)
                                            "
                                            class="px-3 py-1 text-blue-600 hover:text-white hover:bg-blue-600 border border-blue-600 rounded transition-all duration-300 font-medium"
                                        >
                                            Results
                                        </button>
                                        <button
                                            @click="openEditModal(assessment)"
                                            class="px-3 py-1 text-green-600 hover:text-white hover:bg-green-600 border border-green-600 rounded transition-all duration-300 font-medium"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click="openDeleteModal(assessment)"
                                            class="px-3 py-1 text-red-600 hover:text-white hover:bg-red-600 border border-red-600 rounded transition-all duration-300 font-medium"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div
                        v-if="filteredAssessments.length === 0"
                        class="p-8 text-center bg-gradient-to-br from-white to-green-50"
                    >
                        <div class="text-gray-500">
                            <svg
                                class="mx-auto h-12 w-12 text-gray-400 animate-bounce"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">
                                No assessments found
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{
                                    searchQuery
                                        ? "Try adjusting your filters."
                                        : "No assessments have been created yet."
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <Modal
            :show="showModal"
            @close="showModal = false"
            max-width="lg"
            :close-on-click-outside="false"
        >
            <div
                class="p-6 bg-white rounded-xl shadow-2xl border border-gray-100"
            >
                <div class="border-b border-gray-200 pb-4 mb-6 relative">
                    <div
                        class="absolute bottom-0 left-0 w-20 h-0.5 bg-gradient-to-r from-green-500 to-emerald-500 rounded"
                    ></div>
                    <h3 class="text-lg font-semibold text-green-900">
                        {{
                            editingAssessment
                                ? "Edit Assessment"
                                : "Create Assessment"
                        }}
                    </h3>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div>
                        <InputLabel for="title" value="Assessment Title *" />
                        <TextInput
                            id="title"
                            v-model="form.title"
                            type="text"
                            class="mt-1 block w-full border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            required
                        />
                        <InputError :message="form.errors.title" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="description" value="Description" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            rows="3"
                            placeholder="Assessment description..."
                        ></textarea>
                        <InputError
                            :message="form.errors.description"
                            class="mt-2"
                        />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="course_id" value="Course *" />
                            <select
                                id="course_id"
                                v-model="form.course_id"
                                class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                                required
                            >
                                <option value="">Select Course</option>
                                <option
                                    v-for="course in courses"
                                    :key="course.course_id"
                                    :value="course.course_id"
                                >
                                    {{ course.name }}
                                </option>
                            </select>
                            <InputError
                                :message="form.errors.course_id"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel
                                for="assessment_type"
                                value="Assessment Type *"
                            />
                            <select
                                id="assessment_type"
                                v-model="form.assessment_type"
                                class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                                required
                            >
                                <option value="written">Written</option>
                                <option value="practical">Practical</option>
                                <option value="oral">Oral</option>
                                <option value="project">Project</option>
                            </select>
                            <InputError
                                :message="form.errors.assessment_type"
                                class="mt-2"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel
                                for="assessment_date"
                                value="Assessment Date *"
                            />
                            <TextInput
                                id="assessment_date"
                                v-model="form.assessment_date"
                                type="date"
                                class="mt-1 block w-full border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                                required
                            />
                            <InputError
                                :message="form.errors.assessment_date"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel
                                for="duration_minutes"
                                value="Duration (minutes) *"
                            />
                            <TextInput
                                id="duration_minutes"
                                v-model="form.duration_minutes"
                                type="number"
                                class="mt-1 block w-full border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                                required
                            />
                            <InputError
                                :message="form.errors.duration_minutes"
                                class="mt-2"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel
                                for="total_marks"
                                value="Total Marks *"
                            />
                            <TextInput
                                id="total_marks"
                                v-model="form.total_marks"
                                type="number"
                                class="mt-1 block w-full border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                                required
                            />
                            <InputError
                                :message="form.errors.total_marks"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel
                                for="passing_marks"
                                value="Passing Marks *"
                            />
                            <TextInput
                                id="passing_marks"
                                v-model="form.passing_marks"
                                type="number"
                                class="mt-1 block w-full border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                                required
                            />
                            <InputError
                                :message="form.errors.passing_marks"
                                class="mt-2"
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="status" value="Status *" />
                        <select
                            id="status"
                            v-model="form.status"
                            class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            required
                        >
                            <option value="scheduled">Scheduled</option>
                            <option value="competent">Competent</option>
                            <option value="incompetent">
                                Not Yet Competent
                            </option>
                            <option value="no_assessment">No Assessment</option>
                        </select>
                        <InputError
                            :message="form.errors.status"
                            class="mt-2"
                        />
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <SecondaryButton
                            @click="showModal = false"
                            class="border-2 border-gray-300 hover:border-green-500 hover:text-green-600 transition-all duration-300"
                        >
                            Cancel
                        </SecondaryButton>
                        <PrimaryButton
                            :disabled="form.processing"
                            class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 transition-all duration-300"
                        >
                            {{
                                form.processing
                                    ? "Saving..."
                                    : editingAssessment
                                    ? "Update"
                                    : "Create"
                            }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Results Modal -->
        <Modal
            :show="showResultsModal"
            @close="showResultsModal = false"
            max-width="xl"
        >
            <div
                class="p-6 bg-white rounded-xl shadow-2xl border border-gray-100"
            >
                <div class="border-b border-gray-200 pb-4 mb-6 relative">
                    <div
                        class="absolute bottom-0 left-0 w-20 h-0.5 bg-gradient-to-r from-blue-500 to-indigo-500 rounded"
                    ></div>
                    <h3 class="text-lg font-semibold text-blue-900">
                        Assessment Results
                    </h3>
                </div>

                <div v-if="viewingAssessment" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">
                                Assessment Information
                            </h4>
                            <div class="space-y-2">
                                <p class="text-sm">
                                    <span class="font-medium">Title:</span>
                                    {{ viewingAssessment.title }}
                                </p>
                                <p class="text-sm">
                                    <span class="font-medium">Type:</span>
                                    {{ viewingAssessment.assessment_type }}
                                </p>
                                <p class="text-sm">
                                    <span class="font-medium">Date:</span>
                                    {{ viewingAssessment.assessment_date }}
                                </p>
                                <p class="text-sm">
                                    <span class="font-medium">Duration:</span>
                                    {{
                                        formatDuration(
                                            viewingAssessment.duration_minutes
                                        )
                                    }}
                                </p>
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">
                                Course & Trainer
                            </h4>
                            <div class="space-y-2">
                                <p class="text-sm">
                                    <span class="font-medium">Course:</span>
                                    {{
                                        viewingAssessment.course?.name || "N/A"
                                    }}
                                </p>
                                <p class="text-sm">
                                    <span class="font-medium">Trainer:</span>
                                    {{ viewingAssessment.trainer?.first_name }}
                                    {{ viewingAssessment.trainer?.last_name }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">
                            Scoring Information
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600">
                                    {{ viewingAssessment.total_marks }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    Total Marks
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600">
                                    {{ viewingAssessment.passing_marks }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    Passing Marks
                                </div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-600">
                                    {{
                                        getPassingPercentage(viewingAssessment)
                                    }}%
                                </div>
                                <div class="text-sm text-gray-500">
                                    Pass Percentage
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="viewingAssessment.description"
                        class="bg-gray-50 p-4 rounded-lg"
                    >
                        <h4 class="text-sm font-medium text-gray-900 mb-2">
                            Description
                        </h4>
                        <p class="text-sm text-gray-700">
                            {{ viewingAssessment.description }}
                        </p>
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <SecondaryButton
                        @click="showResultsModal = false"
                        class="border-2 border-gray-300 hover:border-blue-500 hover:text-blue-600 transition-all duration-300"
                    >
                        Close
                    </SecondaryButton>
                </div>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false">
            <div
                class="p-6 bg-white rounded-xl shadow-2xl border border-gray-100"
            >
                <div class="border-b border-gray-200 pb-4 mb-6 relative">
                    <div
                        class="absolute bottom-0 left-0 w-20 h-0.5 bg-gradient-to-r from-red-500 to-pink-500 rounded"
                    ></div>
                    <h3 class="text-lg font-semibold text-red-900">
                        Delete Assessment
                    </h3>
                </div>
                <p class="text-sm text-gray-500 mb-4">
                    Are you sure you want to delete this assessment? This action
                    cannot be undone.
                </p>
                <div class="flex justify-end space-x-3">
                    <SecondaryButton
                        @click="showDeleteModal = false"
                        class="border-2 border-gray-300 hover:border-red-500 hover:text-red-600 transition-all duration-300"
                    >
                        Cancel
                    </SecondaryButton>
                    <DangerButton
                        @click="deleteAssessment"
                        class="bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 transition-all duration-300"
                    >
                        Delete
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
    animation: fade-in 0.6s ease-out;
}
</style>
