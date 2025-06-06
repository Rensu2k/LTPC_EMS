<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref } from "vue";

const enrollments = ref([
    {
        id: "enr1",
        name: "John Smith",
        course: "Welding Technology",
        trainer: "Juan Dela Cruz",
        status: "Active",
        payment: "Paid",
    },
    {
        id: "enr2",
        name: "Jane Doe",
        course: "Food Processing",
        trainer: "Maria Santos",
        status: "Active",
        payment: "Partial",
    },
    {
        id: "enr3",
        name: "Carlos Mendoza",
        course: "Automotive Servicing",
        trainer: "Roberto Reyes",
        status: "Active",
        payment: "Unpaid",
    },
    {
        id: "enr4",
        name: "Sofia Garcia",
        course: "Electronics Servicing",
        trainer: "Elena Gomez",
        status: "Completed",
        payment: "Paid",
    },
    {
        id: "enr5",
        name: "Miguel Lopez",
        course: "Carpentry",
        trainer: "Pedro Reyes",
        status: "Dropped",
        payment: "Partial",
    },
]);
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-1">
                <span class="text-lg font-semibold text-gray-400 tracking-wide"
                    >Dashboard</span
                >
            </div>
        </template>

        <div class="py-8 px-8 bg-gray-50 min-h-screen">
            <div class="mb-6">
                <h1 class="text-3xl font-extrabold text-gray-900">
                    Enrollment Officer Dashboard
                </h1>
                <p class="text-base text-gray-500 mt-1">
                    Good evening, Enrollment Officer. Welcome to your enrollment
                    officer dashboard.
                </p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div
                    class="bg-white rounded-xl shadow p-6 flex items-center gap-4"
                >
                    <div class="flex-1">
                        <div class="text-sm text-gray-500 font-medium">
                            Total Enrollments
                        </div>
                        <div class="text-4xl font-bold text-gray-900">756</div>
                        <div class="flex items-center gap-1 mt-1">
                            <span class="text-green-600 text-sm font-semibold"
                                >↑ 12%</span
                            >
                            <span class="text-xs text-gray-400"
                                >vs. last month</span
                            >
                        </div>
                    </div>
                    <div class="bg-blue-50 rounded-full p-3">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-7 w-7 text-blue-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0z"
                            />
                        </svg>
                    </div>
                </div>
                <div
                    class="bg-white rounded-xl shadow p-6 flex items-center gap-4"
                >
                    <div class="flex-1">
                        <div class="text-sm text-gray-500 font-medium">
                            Active Courses
                        </div>
                        <div class="text-4xl font-bold text-gray-900">24</div>
                        <div class="flex items-center gap-1 mt-1">
                            <span class="text-green-600 text-sm font-semibold"
                                >↑ 3%</span
                            >
                            <span class="text-xs text-gray-400"
                                >vs. last month</span
                            >
                        </div>
                    </div>
                    <div class="bg-green-50 rounded-full p-3">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-7 w-7 text-green-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <rect
                                width="18"
                                height="12"
                                x="3"
                                y="4"
                                rx="2"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 20h9"
                            />
                        </svg>
                    </div>
                </div>
                <div
                    class="bg-white rounded-xl shadow p-6 flex items-center gap-4"
                >
                    <div class="flex-1">
                        <div class="text-sm text-gray-500 font-medium">
                            Completed Trainings
                        </div>
                        <div class="text-4xl font-bold text-gray-900">189</div>
                        <div class="flex items-center gap-1 mt-1">
                            <span class="text-green-600 text-sm font-semibold"
                                >↑ 8%</span
                            >
                            <span class="text-xs text-gray-400"
                                >vs. last month</span
                            >
                        </div>
                    </div>
                    <div class="bg-yellow-50 rounded-full p-3">
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            class="h-7 w-7 text-yellow-500"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 14l9-5-9-5-9 5 9 5zm0 0v6m0 0H6m6 0h6"
                            />
                        </svg>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow p-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-gray-900">
                        Recent Enrollments
                    </h3>
                    <a
                        href="#"
                        class="text-blue-600 hover:underline text-base font-semibold"
                        >View All</a
                    >
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                                >
                                    ID
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                                >
                                    Trainee Name
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                                >
                                    Course
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                                >
                                    Trainer
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                                >
                                    Payment
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr
                                v-for="enrollment in enrollments"
                                :key="enrollment.id"
                            >
                                <td
                                    class="px-6 py-4 font-semibold text-gray-700"
                                >
                                    {{ enrollment.id }}
                                </td>
                                <td class="px-6 py-4 flex items-center gap-3">
                                    <span
                                        class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-gray-100 text-xs font-bold text-gray-400 mr-2 border border-gray-200"
                                    >
                                        {{
                                            enrollment.name
                                                .split(" ")
                                                .map((n) => n[0])
                                                .join("")
                                        }}
                                    </span>
                                    <span class="font-medium text-gray-900">{{
                                        enrollment.name
                                    }}</span>
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    {{ enrollment.course }}
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    {{ enrollment.trainer }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        v-if="enrollment.status === 'Active'"
                                        class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold"
                                        >Active</span
                                    >
                                    <span
                                        v-else-if="
                                            enrollment.status === 'Completed'
                                        "
                                        class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-semibold"
                                        >Completed</span
                                    >
                                    <span
                                        v-else-if="
                                            enrollment.status === 'Dropped'
                                        "
                                        class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-semibold"
                                        >Dropped</span
                                    >
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        v-if="enrollment.payment === 'Paid'"
                                        class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-semibold"
                                        >Paid</span
                                    >
                                    <span
                                        v-else-if="
                                            enrollment.payment === 'Partial'
                                        "
                                        class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs font-semibold"
                                        >Partial</span
                                    >
                                    <span
                                        v-else-if="
                                            enrollment.payment === 'Unpaid'
                                        "
                                        class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-semibold"
                                        >Unpaid</span
                                    >
                                </td>
                                <td class="px-6 py-4 flex gap-2">
                                    <button
                                        class="text-gray-500 hover:text-blue-600"
                                        title="View"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                    </button>
                                    <button
                                        class="text-gray-500 hover:text-blue-600"
                                        title="Edit"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="h-5 w-5"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M11 5h2m-1 0v14m-7-7h14"
                                            />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex justify-between items-center mt-6">
                    <div class="text-sm text-gray-500">
                        Showing 1 to 5 of 5 results
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            class="px-3 py-1 rounded border border-gray-300 text-gray-500 bg-white font-semibold"
                            disabled
                        >
                            Previous
                        </button>
                        <span
                            class="px-3 py-1 rounded bg-blue-600 text-white font-semibold"
                            >1</span
                        >
                        <button
                            class="px-3 py-1 rounded border border-gray-300 text-gray-500 bg-white font-semibold"
                            disabled
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
