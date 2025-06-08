<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref } from "vue";

// Sample data - replace with real data from backend
const searchQuery = ref("");
const selectedCourse = ref("All Courses");

const courses = [
    "All Courses",
    "Computer Basics",
    "Welding",
    "Cooking",
    "Automotive",
    "Electronics",
];

const trainees = ref([
    {
        id: "T-2023-0124",
        name: "Juan Dela Cruz",
        trainer: "Not assigned",
        enrollmentDate: "5/10/2023",
        status: "Active",
        payment: "Paid",
        avatar: "JD",
    },
    {
        id: "T-2023-0125",
        name: "Maria Santos",
        trainer: "Not assigned",
        enrollmentDate: "5/15/2023",
        status: "Active",
        payment: "Paid",
        avatar: "MS",
    },
    {
        id: "T-2023-0126",
        name: "Pedro Reyes",
        trainer: "Not assigned",
        enrollmentDate: "5/20/2023",
        status: "Active",
        payment: "Unpaid",
        avatar: "PR",
    },
    {
        id: "T-2023-0127",
        name: "Ana Lim",
        trainer: "Not assigned",
        enrollmentDate: "6/1/2023",
        status: "Active",
        payment: "Unpaid",
        avatar: "AL",
    },
    {
        id: "T-2023-0128",
        name: "Roberto Aquino",
        trainer: "Not assigned",
        enrollmentDate: "6/10/2023",
        status: "Active",
        payment: "Unpaid",
        avatar: "RA",
    },
    {
        id: "T-2023-0129",
        name: "Elena Torres",
        trainer: "Not assigned",
        enrollmentDate: "6/15/2023",
        status: "Completed",
        payment: "Paid",
        avatar: "ET",
    },
]);

const exportData = () => {
    // Export functionality
    console.log("Exporting data...");
};

const registerTrainee = () => {
    // Register new trainee functionality
    console.log("Register new trainee...");
};

const viewTrainee = (trainee) => {
    console.log("View trainee:", trainee);
};

const editTrainee = (trainee) => {
    console.log("Edit trainee:", trainee);
};

const deleteTrainee = (trainee) => {
    console.log("Delete trainee:", trainee);
};
</script>

<template>
    <Head title="Trainees" />

    <AuthenticatedLayout>
        <div class="p-8">
            <!-- Header Section -->
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900">
                    Trainees Management
                </h1>
                <div class="flex gap-3">
                    <button
                        @click="exportData"
                        class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors"
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
                                d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"
                            />
                        </svg>
                        Export
                    </button>
                    <button
                        @click="registerTrainee"
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
                        Register Trainee
                    </button>
                </div>
            </div>

            <!-- Filters and Search Section -->
            <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-900">
                        All Trainees
                    </h2>
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Search trainees..."
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

                <div class="flex items-center gap-4">
                    <span class="text-sm font-medium text-gray-700"
                        >Filter by Course:</span
                    >
                    <select
                        v-model="selectedCourse"
                        class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option
                            v-for="course in courses"
                            :key="course"
                            :value="course"
                        >
                            {{ course }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Trainees Table -->
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
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
                                    Trainer
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Enrollment Date
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Payment
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
                                v-for="trainee in trainees"
                                :key="trainee.id"
                                class="hover:bg-gray-50"
                            >
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                >
                                    {{ trainee.id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div
                                            class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center text-white font-semibold mr-3"
                                        >
                                            {{ trainee.avatar }}
                                        </div>
                                        <div
                                            class="text-sm font-medium text-gray-900"
                                        >
                                            {{ trainee.name }}
                                        </div>
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                >
                                    {{ trainee.trainer }}
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                >
                                    {{ trainee.enrollmentDate }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="{
                                            'bg-green-100 text-green-800':
                                                trainee.status === 'Active',
                                            'bg-blue-100 text-blue-800':
                                                trainee.status === 'Completed',
                                        }"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{ trainee.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="{
                                            'bg-green-100 text-green-800':
                                                trainee.payment === 'Paid',
                                            'bg-red-100 text-red-800':
                                                trainee.payment === 'Unpaid',
                                        }"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{ trainee.payment }}
                                    </span>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-sm font-medium"
                                >
                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="viewTrainee(trainee)"
                                            class="text-blue-600 hover:text-blue-900 p-1 rounded"
                                            title="View"
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
                                            @click="editTrainee(trainee)"
                                            class="text-yellow-600 hover:text-yellow-900 p-1 rounded"
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
                                            @click="deleteTrainee(trainee)"
                                            class="text-orange-600 hover:text-orange-900 p-1 rounded"
                                            title="Archive"
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
                                                    d="M5 8l4 4 4-4m5-4v18l-5-4-5 4V4z"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
