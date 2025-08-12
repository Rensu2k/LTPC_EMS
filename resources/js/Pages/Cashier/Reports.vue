<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    summaryStats: { type: Object, required: true },
    collectionsByProgram: { type: Array, required: true },
});

const summary = computed(
    () =>
        props.summaryStats || {
            totalCollections: { amount: 0 },
            thisMonth: { amount: 0 },
            outstandingBalance: { amount: 0 },
        }
);

const collections = computed(() => props.collectionsByProgram || []);

const formatCurrency = (value) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(Number(value || 0));
</script>

<template>
    <Head title="Reports" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Reports
                </h2>
                <div class="text-sm text-gray-500">Summary & Collections</div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div
                        class="bg-white rounded-xl shadow-sm p-6 border border-gray-100"
                    >
                        <div class="mb-4">
                            <h3 class="text-sm font-medium text-gray-600">
                                Total Collections
                            </h3>
                            <p class="text-3xl font-bold text-blue-600">
                                {{
                                    formatCurrency(
                                        summary.totalCollections.amount
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                    <div
                        class="bg-white rounded-xl shadow-sm p-6 border border-gray-100"
                    >
                        <div class="mb-4">
                            <h3 class="text-sm font-medium text-gray-600">
                                This Month
                            </h3>
                            <p class="text-3xl font-bold text-blue-600">
                                {{ formatCurrency(summary.thisMonth.amount) }}
                            </p>
                        </div>
                    </div>
                    <div
                        class="bg-white rounded-xl shadow-sm p-6 border border-gray-100"
                    >
                        <div class="mb-4">
                            <h3 class="text-sm font-medium text-gray-600">
                                Outstanding Balance
                            </h3>
                            <p class="text-3xl font-bold text-red-600">
                                {{
                                    formatCurrency(
                                        summary.outstandingBalance.amount
                                    )
                                }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Collections by Program -->
                <div
                    class="bg-white rounded-xl shadow-sm border border-gray-100"
                >
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Collections by Program
                        </h2>
                    </div>
                    <div
                        v-if="collections.length === 0"
                        class="p-12 text-center"
                    >
                        <svg
                            class="mx-auto h-12 w-12 text-gray-400 mb-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                            />
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            No program data available
                        </h3>
                        <p class="text-gray-500">
                            Program collection information will appear here when
                            data is available.
                        </p>
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Program
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Total Payments
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Fully Paid
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Partially Paid
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Unpaid
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                    >
                                        Collection Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr
                                    v-for="program in collections"
                                    :key="program.program"
                                    class="hover:bg-gray-50"
                                >
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                    >
                                        {{ program.program }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ program.totalTrainees }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ program.fullyPaid }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ program.partiallyPaid }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"
                                    >
                                        {{ program.unpaid }}
                                    </td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                    >
                                        {{
                                            formatCurrency(
                                                program.collectionAmount
                                            )
                                        }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
