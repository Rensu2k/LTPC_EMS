<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";

const props = defineProps({
    reportData: Object,
    flash: Object,
});

const selectedReportType = ref("overview");
const selectedDateRange = ref("month");
const customStartDate = ref("");
const customEndDate = ref("");
const isGenerating = ref(false);

const form = useForm({
    report_type: "overview",
    date_range: "month",
    start_date: "",
    end_date: "",
    format: "pdf",
});

// Updated mock data to match simplified template
const mockReportData = ref({
    overview: {
        totalTrainees: 156,
        activeTrainees: 124,
        completedCourses: 89,
        totalRevenue: 45250,
        employmentRate: 78,
    },
    financial: {
        monthlyRevenue: [
            3200, 4100, 3800, 4500, 3900, 4200, 4800, 4300, 3700, 4600, 4100,
            4400,
        ],
        paymentStatus: {
            paid: 145,
            pending: 23,
            overdue: 8,
        },
    },
    employment: {
        placementRate: 78,
        avgSalary: 25000,
        topEmployers: [
            { name: "Tech Solutions Inc.", placements: 15 },
            { name: "Industrial Corp.", placements: 12 },
            { name: "Service Company", placements: 10 },
        ],
    },
});

const reportTypes = [
    {
        value: "overview",
        label: "System Overview Report",
        icon: "📊",
        description: "Complete system monitoring summary",
    },
    {
        value: "enrollment",
        label: "Enrollment Monitoring",
        icon: "👥",
        description: "Regular vs Scholar trainees tracking",
    },
    {
        value: "course_progress",
        label: "Course Progress Report",
        icon: "📈",
        description: "Course progress monitoring (25 per batch)",
    },
    {
        value: "training_results",
        label: "Training Results Report",
        icon: "🎓",
        description: "Complete vs Incomplete status",
    },
    {
        value: "assessment_results",
        label: "Assessment Results Report",
        icon: "📋",
        description: "Competent vs Not Yet Competent vs No Assessment",
    },
    {
        value: "payment_status",
        label: "Payment Status Report",
        icon: "💰",
        description: "Training & Assessment payments (Regular trainees)",
    },
    {
        value: "employment",
        label: "Employment Endorsements",
        icon: "💼",
        description: "Employment referrals and placements",
    },
    {
        value: "officer_activities",
        label: "Officer Activities Report",
        icon: "👨‍💼",
        description: "Enrollment Officer and Cashier activities tracking",
    },
];

const dateRanges = [
    { value: "week", label: "Last 7 Days" },
    { value: "month", label: "Last 30 Days" },
    { value: "quarter", label: "Last 3 Months" },
    { value: "year", label: "Last 12 Months" },
    { value: "custom", label: "Custom Range" },
];

const generateReport = () => {
    isGenerating.value = true;

    form.report_type = selectedReportType.value;
    form.date_range = selectedDateRange.value;
    form.start_date = customStartDate.value;
    form.end_date = customEndDate.value;

    form.post("/admin/reports/generate", {
        onSuccess: () => {
            isGenerating.value = false;
        },
        onError: () => {
            isGenerating.value = false;
        },
    });
};

const exportReport = (format) => {
    const exportForm = useForm({
        report_type: selectedReportType.value,
        date_range: selectedDateRange.value,
        start_date: customStartDate.value,
        end_date: customEndDate.value,
        format: format,
    });

    exportForm.post("/admin/reports/export", {
        onSuccess: () => {
            // Handle success - file download should start automatically
        },
    });
};

const getPercentageChange = (current, previous) => {
    if (!previous) return 0;
    return Math.round(((current - previous) / previous) * 100);
};
</script>

<template>
    <Head title="Reports & Analytics" />

    <AuthenticatedLayout>
        <div class="py-8 px-8 bg-gray-50 min-h-screen">
            <!-- Report Controls -->
            <div class="bg-white rounded-xl shadow-sm mb-8">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        Generate Reports
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Select report type and date range to generate
                        comprehensive analytics
                    </p>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <!-- Report Type Selection -->
                        <div class="md:col-span-2">
                            <InputLabel value="Report Type" class="mb-3" />
                            <div class="grid grid-cols-1 gap-3">
                                <div
                                    v-for="type in reportTypes"
                                    :key="type.value"
                                    @click="selectedReportType = type.value"
                                    :class="
                                        selectedReportType === type.value
                                            ? 'bg-indigo-50 border-indigo-500 text-indigo-700'
                                            : 'bg-white border-gray-300 text-gray-700'
                                    "
                                    class="border-2 rounded-lg p-4 cursor-pointer hover:border-indigo-300 transition-colors"
                                >
                                    <div class="flex items-start">
                                        <span class="text-2xl mr-3">{{
                                            type.icon
                                        }}</span>
                                        <div>
                                            <div class="font-medium">
                                                {{ type.label }}
                                            </div>
                                            <div
                                                class="text-sm text-gray-500 mt-1"
                                            >
                                                {{ type.description }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Date Range Selection -->
                        <div>
                            <InputLabel for="date-range" value="Date Range" />
                            <select
                                id="date-range"
                                v-model="selectedDateRange"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option
                                    v-for="range in dateRanges"
                                    :key="range.value"
                                    :value="range.value"
                                >
                                    {{ range.label }}
                                </option>
                            </select>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col justify-end space-y-3">
                            <PrimaryButton
                                @click="generateReport"
                                :disabled="isGenerating"
                                class="w-full"
                            >
                                {{
                                    isGenerating
                                        ? "Generating..."
                                        : "Generate Report"
                                }}
                            </PrimaryButton>

                            <div class="flex space-x-2">
                                <SecondaryButton
                                    @click="exportReport('pdf')"
                                    class="flex-1 text-xs"
                                >
                                    Export PDF
                                </SecondaryButton>
                                <SecondaryButton
                                    @click="exportReport('excel')"
                                    class="flex-1 text-xs"
                                >
                                    Export Excel
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>

                    <!-- Custom Date Range -->
                    <div
                        v-if="selectedDateRange === 'custom'"
                        class="mt-6 grid grid-cols-2 gap-4"
                    >
                        <div>
                            <InputLabel for="start-date" value="Start Date" />
                            <TextInput
                                id="start-date"
                                v-model="customStartDate"
                                type="date"
                                class="mt-1 block w-full"
                            />
                        </div>
                        <div>
                            <InputLabel for="end-date" value="End Date" />
                            <TextInput
                                id="end-date"
                                v-model="customEndDate"
                                type="date"
                                class="mt-1 block w-full"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Content Based on Selected Type -->
            <div class="space-y-8">
                <!-- Overview Report -->
                <div v-if="selectedReportType === 'overview'">
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">
                                System Overview
                            </h3>
                        </div>

                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <div
                                        class="text-sm font-medium text-blue-600"
                                    >
                                        Total Trainees
                                    </div>
                                    <div
                                        class="text-2xl font-bold text-blue-900"
                                    >
                                        {{
                                            mockReportData.overview
                                                .totalTrainees
                                        }}
                                    </div>
                                    <div class="text-xs text-blue-600 mt-1">
                                        +12% from last month
                                    </div>
                                </div>
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <div
                                        class="text-sm font-medium text-green-600"
                                    >
                                        Active Trainees
                                    </div>
                                    <div
                                        class="text-2xl font-bold text-green-900"
                                    >
                                        {{
                                            mockReportData.overview
                                                .activeTrainees
                                        }}
                                    </div>
                                    <div class="text-xs text-green-600 mt-1">
                                        +8% from last month
                                    </div>
                                </div>
                                <div class="bg-purple-50 p-4 rounded-lg">
                                    <div
                                        class="text-sm font-medium text-purple-600"
                                    >
                                        Completed Courses
                                    </div>
                                    <div
                                        class="text-2xl font-bold text-purple-900"
                                    >
                                        {{
                                            mockReportData.overview
                                                .completedCourses
                                        }}
                                    </div>
                                    <div class="text-xs text-purple-600 mt-1">
                                        +15% from last month
                                    </div>
                                </div>
                                <div class="bg-yellow-50 p-4 rounded-lg">
                                    <div
                                        class="text-sm font-medium text-yellow-600"
                                    >
                                        Total Revenue
                                    </div>
                                    <div
                                        class="text-2xl font-bold text-yellow-900"
                                    >
                                        ₱{{
                                            mockReportData.overview.totalRevenue.toLocaleString()
                                        }}
                                    </div>
                                    <div class="text-xs text-yellow-600 mt-1">
                                        +22% from last month
                                    </div>
                                </div>
                                <div class="bg-orange-50 p-4 rounded-lg">
                                    <div
                                        class="text-sm font-medium text-orange-600"
                                    >
                                        Employment Rate
                                    </div>
                                    <div
                                        class="text-2xl font-bold text-orange-900"
                                    >
                                        {{
                                            mockReportData.overview
                                                .employmentRate
                                        }}%
                                    </div>
                                    <div class="text-xs text-orange-600 mt-1">
                                        +5% from last month
                                    </div>
                                </div>
                            </div>

                            <!-- Course Performance -->
                            <div class="mt-8">
                                <div class="bg-gray-50 rounded-lg p-6">
                                    <h4
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Top Performing Courses
                                    </h4>
                                    <div class="space-y-3">
                                        <div
                                            class="flex items-center justify-between p-3 bg-white rounded-lg"
                                        >
                                            <span class="text-sm font-medium"
                                                >Cookery NC II</span
                                            >
                                            <span
                                                class="text-sm text-green-600 font-semibold"
                                                >95% completion</span
                                            >
                                        </div>
                                        <div
                                            class="flex items-center justify-between p-3 bg-white rounded-lg"
                                        >
                                            <span class="text-sm font-medium"
                                                >Driving NC II</span
                                            >
                                            <span
                                                class="text-sm text-green-600 font-semibold"
                                                >88% completion</span
                                            >
                                        </div>
                                        <div
                                            class="flex items-center justify-between p-3 bg-white rounded-lg"
                                        >
                                            <span class="text-sm font-medium"
                                                >Basic Micro Computer
                                                Servicing</span
                                            >
                                            <span
                                                class="text-sm text-green-600 font-semibold"
                                                >82% completion</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Report -->
                <div v-if="selectedReportType === 'financial'">
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">
                                Financial Analytics
                            </h3>
                        </div>

                        <div class="p-6">
                            <div
                                class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8"
                            >
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <div
                                        class="text-sm font-medium text-green-600"
                                    >
                                        Total Revenue
                                    </div>
                                    <div
                                        class="text-2xl font-bold text-green-900"
                                    >
                                        ₱45,250
                                    </div>
                                    <div class="text-xs text-green-600 mt-1">
                                        +18% from last period
                                    </div>
                                </div>
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <div
                                        class="text-sm font-medium text-blue-600"
                                    >
                                        Payments Collected
                                    </div>
                                    <div
                                        class="text-2xl font-bold text-blue-900"
                                    >
                                        145
                                    </div>
                                    <div class="text-xs text-blue-600 mt-1">
                                        82% collection rate
                                    </div>
                                </div>
                                <div class="bg-red-50 p-4 rounded-lg">
                                    <div
                                        class="text-sm font-medium text-red-600"
                                    >
                                        Outstanding Amount
                                    </div>
                                    <div
                                        class="text-2xl font-bold text-red-900"
                                    >
                                        ₱8,750
                                    </div>
                                    <div class="text-xs text-red-600 mt-1">
                                        31 pending payments
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <!-- Revenue Chart Placeholder -->
                                <div class="bg-gray-50 rounded-lg p-6">
                                    <h4
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Monthly Revenue Trend
                                    </h4>
                                    <div
                                        class="h-48 flex items-center justify-center bg-white rounded border-2 border-dashed border-gray-300"
                                    >
                                        <div class="text-center">
                                            <svg
                                                class="mx-auto h-12 w-12 text-gray-400"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"
                                                />
                                            </svg>
                                            <p
                                                class="mt-2 text-sm text-gray-500"
                                            >
                                                Revenue chart will be rendered
                                                here
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Payment Status -->
                                <div class="bg-gray-50 rounded-lg p-6">
                                    <h4
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Payment Status Distribution
                                    </h4>
                                    <div class="space-y-4">
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <span class="text-sm text-gray-600"
                                                >Paid</span
                                            >
                                            <span
                                                class="text-sm font-medium text-green-600"
                                                >145 payments</span
                                            >
                                        </div>
                                        <div
                                            class="w-full bg-gray-200 rounded-full h-2"
                                        >
                                            <div
                                                class="bg-green-600 h-2 rounded-full"
                                                style="width: 82%"
                                            ></div>
                                        </div>

                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <span class="text-sm text-gray-600"
                                                >Pending</span
                                            >
                                            <span
                                                class="text-sm font-medium text-yellow-600"
                                                >23 payments</span
                                            >
                                        </div>
                                        <div
                                            class="w-full bg-gray-200 rounded-full h-2"
                                        >
                                            <div
                                                class="bg-yellow-600 h-2 rounded-full"
                                                style="width: 13%"
                                            ></div>
                                        </div>

                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <span class="text-sm text-gray-600"
                                                >Overdue</span
                                            >
                                            <span
                                                class="text-sm font-medium text-red-600"
                                                >8 payments</span
                                            >
                                        </div>
                                        <div
                                            class="w-full bg-gray-200 rounded-full h-2"
                                        >
                                            <div
                                                class="bg-red-600 h-2 rounded-full"
                                                style="width: 5%"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employment Report -->
                <div v-if="selectedReportType === 'employment'">
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">
                                Employment Analytics
                            </h3>
                        </div>

                        <div class="p-6">
                            <div
                                class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8"
                            >
                                <div class="bg-purple-50 p-4 rounded-lg">
                                    <div
                                        class="text-sm font-medium text-purple-600"
                                    >
                                        Placement Rate
                                    </div>
                                    <div
                                        class="text-2xl font-bold text-purple-900"
                                    >
                                        78%
                                    </div>
                                    <div class="text-xs text-purple-600 mt-1">
                                        89 of 114 graduates
                                    </div>
                                </div>
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <div
                                        class="text-sm font-medium text-green-600"
                                    >
                                        Average Salary
                                    </div>
                                    <div
                                        class="text-2xl font-bold text-green-900"
                                    >
                                        ₱25,000
                                    </div>
                                    <div class="text-xs text-green-600 mt-1">
                                        +12% from last year
                                    </div>
                                </div>
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <div
                                        class="text-sm font-medium text-blue-600"
                                    >
                                        Partner Companies
                                    </div>
                                    <div
                                        class="text-2xl font-bold text-blue-900"
                                    >
                                        45
                                    </div>
                                    <div class="text-xs text-blue-600 mt-1">
                                        15 new partnerships
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                                <!-- Top Employers -->
                                <div class="bg-gray-50 rounded-lg p-6">
                                    <h4
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Top Employer Partners
                                    </h4>
                                    <div class="space-y-3">
                                        <div
                                            v-for="employer in mockReportData
                                                .employment.topEmployers"
                                            :key="employer.name"
                                            class="flex items-center justify-between p-3 bg-white rounded-lg"
                                        >
                                            <span class="text-sm font-medium">{{
                                                employer.name
                                            }}</span>
                                            <span
                                                class="text-sm text-blue-600 font-semibold"
                                                >{{
                                                    employer.placements
                                                }}
                                                hires</span
                                            >
                                        </div>
                                    </div>
                                </div>

                                <!-- Employment by Course -->
                                <div class="bg-gray-50 rounded-lg p-6">
                                    <h4
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Employment by Course
                                    </h4>
                                    <div class="space-y-4">
                                        <div>
                                            <div
                                                class="flex justify-between text-sm mb-1"
                                            >
                                                <span>Welding Technology</span>
                                                <span class="font-medium"
                                                    >95%</span
                                                >
                                            </div>
                                            <div
                                                class="w-full bg-gray-200 rounded-full h-2"
                                            >
                                                <div
                                                    class="bg-green-600 h-2 rounded-full"
                                                    style="width: 95%"
                                                ></div>
                                            </div>
                                        </div>
                                        <div>
                                            <div
                                                class="flex justify-between text-sm mb-1"
                                            >
                                                <span>Automotive Repair</span>
                                                <span class="font-medium"
                                                    >82%</span
                                                >
                                            </div>
                                            <div
                                                class="w-full bg-gray-200 rounded-full h-2"
                                            >
                                                <div
                                                    class="bg-blue-600 h-2 rounded-full"
                                                    style="width: 82%"
                                                ></div>
                                            </div>
                                        </div>
                                        <div>
                                            <div
                                                class="flex justify-between text-sm mb-1"
                                            >
                                                <span
                                                    >Computer Programming</span
                                                >
                                                <span class="font-medium"
                                                    >76%</span
                                                >
                                            </div>
                                            <div
                                                class="w-full bg-gray-200 rounded-full h-2"
                                            >
                                                <div
                                                    class="bg-purple-600 h-2 rounded-full"
                                                    style="width: 76%"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Other report types placeholder -->
                <div
                    v-if="
                        !['overview', 'financial', 'employment'].includes(
                            selectedReportType
                        )
                    "
                >
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">
                                {{
                                    reportTypes.find(
                                        (t) => t.value === selectedReportType
                                    )?.label
                                }}
                                Report
                            </h3>
                        </div>

                        <div class="p-6">
                            <div class="text-center py-12">
                                <svg
                                    class="mx-auto h-12 w-12 text-gray-400"
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
                                <h3
                                    class="mt-2 text-sm font-medium text-gray-900"
                                >
                                    Report Not Available
                                </h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    This report type is currently under
                                    development.
                                </p>
                                <div class="mt-6">
                                    <PrimaryButton @click="generateReport">
                                        Generate Report
                                    </PrimaryButton>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
