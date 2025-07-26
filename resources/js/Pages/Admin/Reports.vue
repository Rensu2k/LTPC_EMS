<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import axios from "axios";

const props = defineProps({
    reportData: Object,
    flash: Object,
});

const selectedReportType = ref("overview");
const selectedDateRange = ref("month");
const customStartDate = ref("");
const customEndDate = ref("");
const isGenerating = ref(false);
const reportData = ref(null);
const reportError = ref(null);

const form = useForm({
    report_type: "overview",
    date_range: "month",
    start_date: "",
    end_date: "",
    format: "pdf",
});

const reportTypes = [
    {
        value: "overview",
        label: "System Overview Report",
        icon: "📊",
        description: "Complete system monitoring summary",
    },
    {
        value: "enrollment_summary",
        label: "Enrollment Summary Report",
        description: "Comprehensive enrollment statistics and trends",
    },
    {
        value: "program_progress",
        label: "Program Progress Report",
        description: "Program progress monitoring (25 per batch)",
    },
    {
        value: "payment_summary",
        label: "Payment Summary Report",
        description: "Payment collection and financial overview",
    },
    {
        value: "trainee_performance",
        label: "Trainee Performance Report",
        description: "Individual trainee progress and assessment results",
    },
    {
        value: "trainer_performance",
        label: "Trainer Performance Report",
        description: "Trainer effectiveness and program assignments",
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

const generateReport = async () => {
    isGenerating.value = true;
    reportError.value = null;

    try {
        const response = await axios.post("/admin/reports/generate", {
            report_type: selectedReportType.value,
            date_range: selectedDateRange.value,
            start_date: customStartDate.value,
            end_date: customEndDate.value,
        });

        if (response.data.success) {
            reportData.value = response.data.data;
        } else {
            reportError.value = "Failed to generate report. Please try again.";
        }
    } catch (error) {
        reportError.value = "Failed to generate report. Please try again.";
        console.error("Report generation error:", error);
    } finally {
        isGenerating.value = false;
    }
};

const exportReport = async (format) => {
    try {
        const response = await axios.post(
            "/admin/reports/export",
            {
                report_type: selectedReportType.value,
                date_range: selectedDateRange.value,
                start_date: customStartDate.value,
                end_date: customEndDate.value,
                format: format,
            },
            {
                responseType: "blob", // For file downloads
            }
        );

        // Create download link for the file
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement("a");
        link.href = url;
        const fileExtension =
            format === "pdf" ? "pdf" : format === "csv" ? "csv" : "xlsx";
        link.setAttribute(
            "download",
            `report_${selectedReportType.value}_${format}.${fileExtension}`
        );
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error("Export error:", error);
        // For now, since export is placeholder, just show the data
        alert(
            "Export functionality is being implemented. Check console for report data."
        );
    }
};

const getPercentageChange = (current, previous) => {
    if (!previous) return 0;
    return Math.round(((current - previous) / previous) * 100);
};

const formatNumber = (value) => {
    if (value === null || value === undefined || isNaN(value)) {
        return "0";
    }
    return Number(value).toLocaleString();
};

const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    return new Date(dateString).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

// Auto-load overview report when component mounts
onMounted(() => {
    generateReport();
});
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
                                <SecondaryButton
                                    @click="exportReport('csv')"
                                    class="flex-1 text-xs"
                                >
                                    Export CSV
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
                            <!-- Error Message -->
                            <div
                                v-if="reportError"
                                class="mb-4 p-4 bg-red-50 border border-red-300 rounded-md"
                            >
                                <div class="text-red-700">
                                    {{ reportError }}
                                </div>
                            </div>

                            <!-- Generate report prompt -->
                            <div v-if="!reportData" class="text-center py-8">
                                <p class="text-gray-500 mb-4">
                                    Click "Generate Report" to view data
                                </p>
                            </div>

                            <!-- Real data display -->
                            <div
                                v-if="reportData && reportData.overview"
                                class="grid grid-cols-1 md:grid-cols-5 gap-6"
                            >
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <div
                                        class="text-sm font-medium text-blue-600"
                                    >
                                        Total Trainees
                                    </div>
                                    <div
                                        class="text-2xl font-bold text-blue-900"
                                    >
                                        {{ reportData.overview.totalTrainees }}
                                    </div>
                                    <div class="text-xs text-blue-600 mt-1">
                                        All registered trainees
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
                                        {{ reportData.overview.activeTrainees }}
                                    </div>
                                    <div class="text-xs text-green-600 mt-1">
                                        Currently enrolled
                                    </div>
                                </div>
                                <div class="bg-purple-50 p-4 rounded-lg">
                                    <div
                                        class="text-sm font-medium text-purple-600"
                                    >
                                        Completed Programs
                                    </div>
                                    <div
                                        class="text-2xl font-bold text-purple-900"
                                    >
                                        {{
                                            reportData.overview
                                                .completedPrograms
                                        }}
                                    </div>
                                    <div class="text-xs text-purple-600 mt-1">
                                        In selected period
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
                                            formatNumber(
                                                reportData.overview.totalRevenue
                                            )
                                        }}
                                    </div>
                                    <div class="text-xs text-yellow-600 mt-1">
                                        In selected period
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
                                            reportData.overview.employmentRate
                                        }}%
                                    </div>
                                    <div class="text-xs text-orange-600 mt-1">
                                        Estimated placement rate
                                    </div>
                                </div>
                            </div>

                            <!-- Program Performance -->
                            <div
                                v-if="reportData && reportData.topPrograms"
                                class="mt-8"
                            >
                                <div class="bg-gray-50 rounded-lg p-6">
                                    <h4
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Top Performing Programs
                                    </h4>
                                    <div
                                        v-if="reportData.topPrograms.length > 0"
                                        class="space-y-3"
                                    >
                                        <div
                                            v-for="program in reportData.topPrograms"
                                            :key="program.name"
                                            class="flex items-center justify-between p-3 bg-white rounded-lg"
                                        >
                                            <span class="text-sm font-medium">{{
                                                program.name
                                            }}</span>
                                            <span
                                                class="text-sm text-green-600 font-semibold"
                                                >{{ program.completion_rate }}%
                                                completion</span
                                            >
                                        </div>
                                    </div>
                                    <div
                                        v-else
                                        class="text-center py-4 text-gray-500"
                                    >
                                        No program data available for selected
                                        period
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enrollment Report -->
                <div v-if="selectedReportType === 'enrollment_summary'">
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">
                                Enrollment Monitoring Report
                            </h3>
                        </div>

                        <div class="p-6">
                            <!-- Error Message -->
                            <div
                                v-if="reportError"
                                class="mb-4 p-4 bg-red-50 border border-red-300 rounded-md"
                            >
                                <div class="text-red-700">
                                    {{ reportError }}
                                </div>
                            </div>

                            <!-- Generate report prompt -->
                            <div v-if="!reportData" class="text-center py-8">
                                <p class="text-gray-500 mb-4">
                                    Click "Generate Report" to view enrollment
                                    data
                                </p>
                            </div>

                            <!-- No Data Message -->
                            <div
                                v-if="
                                    reportData &&
                                    reportData.stats &&
                                    reportData.stats.total_enrollments === 0
                                "
                                class="text-center py-8"
                            >
                                <div class="bg-gray-50 rounded-lg p-6">
                                    <div class="text-gray-400 mb-2">
                                        <svg
                                            class="w-12 h-12 mx-auto"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </div>
                                    <h3
                                        class="text-lg font-medium text-gray-900 mb-2"
                                    >
                                        No Enrollment Data
                                    </h3>
                                    <p class="text-gray-500">
                                        There are no enrollment records in the
                                        system yet.
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="
                                    reportData &&
                                    reportData.stats &&
                                    reportData.stats.total_enrollments > 0
                                "
                            >
                                <!-- Enrollment Stats -->
                                <div
                                    class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8"
                                >
                                    <div class="bg-blue-50 p-4 rounded-lg">
                                        <div
                                            class="text-sm font-medium text-blue-600"
                                        >
                                            Total Enrollments
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-blue-900"
                                        >
                                            {{
                                                reportData.stats
                                                    .total_enrollments
                                            }}
                                        </div>
                                        <div class="text-xs text-blue-600 mt-1">
                                            In selected period
                                        </div>
                                    </div>
                                    <div class="bg-green-50 p-4 rounded-lg">
                                        <div
                                            class="text-sm font-medium text-green-600"
                                        >
                                            Regular Trainees
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-green-900"
                                        >
                                            {{
                                                reportData.stats
                                                    .regular_enrollments
                                            }}
                                        </div>
                                        <div
                                            class="text-xs text-green-600 mt-1"
                                        >
                                            Non-scholarship trainees
                                        </div>
                                    </div>
                                    <div class="bg-purple-50 p-4 rounded-lg">
                                        <div
                                            class="text-sm font-medium text-purple-600"
                                        >
                                            Scholar Trainees
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-purple-900"
                                        >
                                            {{
                                                reportData.stats
                                                    .scholar_enrollments
                                            }}
                                        </div>
                                        <div
                                            class="text-xs text-purple-600 mt-1"
                                        >
                                            Scholarship recipients
                                        </div>
                                    </div>
                                    <div class="bg-yellow-50 p-4 rounded-lg">
                                        <div
                                            class="text-sm font-medium text-yellow-600"
                                        >
                                            Active Enrollments
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-yellow-900"
                                        >
                                            {{
                                                reportData.stats
                                                    .active_enrollments
                                            }}
                                        </div>
                                        <div
                                            class="text-xs text-yellow-600 mt-1"
                                        >
                                            Currently ongoing
                                        </div>
                                    </div>
                                </div>

                                <!-- Program Enrollments -->
                                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                                    <h4
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Enrollments by Program
                                    </h4>
                                    <div
                                        v-if="
                                            reportData.programEnrollments &&
                                            reportData.programEnrollments
                                                .length > 0
                                        "
                                        class="space-y-3"
                                    >
                                        <div
                                            v-for="program in reportData.programEnrollments"
                                            :key="program.title"
                                            class="flex items-center justify-between p-3 bg-white rounded-lg"
                                        >
                                            <span class="text-sm font-medium">{{
                                                program.title
                                            }}</span>
                                            <span
                                                class="text-sm text-blue-600 font-semibold"
                                                >{{
                                                    program.enrollment_count
                                                }}
                                                enrollments</span
                                            >
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-4">
                                        <p class="text-gray-500 text-sm">
                                            No program enrollment data available
                                        </p>
                                    </div>
                                </div>

                                <!-- Recent Enrollments -->
                                <div class="bg-gray-50 rounded-lg p-6">
                                    <h4
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Recent Enrollments
                                    </h4>
                                    <div
                                        v-if="
                                            reportData.recentEnrollments &&
                                            reportData.recentEnrollments
                                                .length > 0
                                        "
                                        class="space-y-3"
                                    >
                                        <div
                                            v-for="enrollment in reportData.recentEnrollments"
                                            :key="`${enrollment.first_name}-${enrollment.last_name}-${enrollment.enrollment_date}`"
                                            class="flex items-center justify-between p-3 bg-white rounded-lg"
                                        >
                                            <div class="flex-1">
                                                <div
                                                    class="text-sm font-medium text-gray-900"
                                                >
                                                    {{ enrollment.first_name }}
                                                    {{ enrollment.last_name }}
                                                </div>
                                                <div
                                                    class="text-xs text-gray-500"
                                                >
                                                    {{
                                                        enrollment.program_name
                                                    }}
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div
                                                    class="text-xs text-gray-500"
                                                >
                                                    {{
                                                        formatDate(
                                                            enrollment.enrollment_date
                                                        )
                                                    }}
                                                </div>
                                                <div
                                                    class="flex items-center gap-2"
                                                >
                                                    <span
                                                        class="px-2 py-1 rounded-full text-xs font-medium"
                                                        :class="{
                                                            'bg-green-100 text-green-800':
                                                                enrollment.status ===
                                                                'active',
                                                            'bg-blue-100 text-blue-800':
                                                                enrollment.status ===
                                                                'completed',
                                                            'bg-yellow-100 text-yellow-800':
                                                                enrollment.status ===
                                                                'pending',
                                                            'bg-red-100 text-red-800':
                                                                enrollment.status ===
                                                                'dropped',
                                                        }"
                                                    >
                                                        {{ enrollment.status }}
                                                    </span>
                                                    <span
                                                        v-if="
                                                            enrollment.scholarship_package
                                                        "
                                                        class="px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800"
                                                    >
                                                        Scholar
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-4">
                                        <p class="text-gray-500 text-sm">
                                            No recent enrollment activity
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Status Report -->
                <div v-if="selectedReportType === 'payment_summary'">
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">
                                Payment Summary Report
                            </h3>
                        </div>

                        <div class="p-6">
                            <!-- Error Message -->
                            <div
                                v-if="reportError"
                                class="mb-4 p-4 bg-red-50 border border-red-300 rounded-md"
                            >
                                <div class="text-red-700">
                                    {{ reportError }}
                                </div>
                            </div>

                            <!-- Generate report prompt -->
                            <div v-if="!reportData" class="text-center py-8">
                                <p class="text-gray-500 mb-4">
                                    Click "Generate Report" to view payment data
                                </p>
                            </div>

                            <div v-if="reportData && reportData.stats">
                                <!-- Payment Stats -->
                                <div
                                    class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8"
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
                                            ₱{{
                                                formatNumber(
                                                    reportData.stats
                                                        .total_revenue
                                                )
                                            }}
                                        </div>
                                        <div
                                            class="text-xs text-green-600 mt-1"
                                        >
                                            Confirmed payments
                                        </div>
                                    </div>
                                    <div class="bg-blue-50 p-4 rounded-lg">
                                        <div
                                            class="text-sm font-medium text-blue-600"
                                        >
                                            Confirmed Payments
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-blue-900"
                                        >
                                            {{
                                                reportData.stats
                                                    .confirmed_payments
                                            }}
                                        </div>
                                        <div class="text-xs text-blue-600 mt-1">
                                            Successfully processed
                                        </div>
                                    </div>
                                    <div class="bg-yellow-50 p-4 rounded-lg">
                                        <div
                                            class="text-sm font-medium text-yellow-600"
                                        >
                                            Pending Payments
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-yellow-900"
                                        >
                                            {{
                                                reportData.stats
                                                    .pending_payments
                                            }}
                                        </div>
                                        <div
                                            class="text-xs text-yellow-600 mt-1"
                                        >
                                            ₱{{
                                                formatNumber(
                                                    reportData.stats
                                                        .pending_amount
                                                )
                                            }}
                                        </div>
                                    </div>
                                    <div class="bg-red-50 p-4 rounded-lg">
                                        <div
                                            class="text-sm font-medium text-red-600"
                                        >
                                            Cancelled Payments
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-red-900"
                                        >
                                            {{
                                                reportData.stats
                                                    .cancelled_payments
                                            }}
                                        </div>
                                        <div class="text-xs text-red-600 mt-1">
                                            Cancelled transactions
                                        </div>
                                    </div>
                                </div>

                                <!-- Revenue by Type -->
                                <div
                                    v-if="reportData.revenueByType"
                                    class="bg-gray-50 rounded-lg p-6"
                                >
                                    <h4
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Revenue by Registration Type
                                    </h4>
                                    <div class="space-y-3">
                                        <div
                                            v-for="type in reportData.revenueByType"
                                            :key="type.registration_type"
                                            class="flex items-center justify-between p-3 bg-white rounded-lg"
                                        >
                                            <span class="text-sm font-medium">{{
                                                type.registration_type ||
                                                "Not specified"
                                            }}</span>
                                            <div class="text-right">
                                                <span
                                                    class="text-sm text-green-600 font-semibold"
                                                    >₱{{
                                                        formatNumber(
                                                            type.total_amount
                                                        )
                                                    }}</span
                                                >
                                                <div
                                                    class="text-xs text-gray-500"
                                                >
                                                    {{ type.count }} payments
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assessment Results Report -->
                <div v-if="selectedReportType === 'trainee_performance'">
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">
                                Trainee Performance Report
                            </h3>
                        </div>

                        <div class="p-6">
                            <!-- Error Message -->
                            <div
                                v-if="reportError"
                                class="mb-4 p-4 bg-red-50 border border-red-300 rounded-md"
                            >
                                <div class="text-red-700">
                                    {{ reportError }}
                                </div>
                            </div>

                            <!-- Generate report prompt -->
                            <div v-if="!reportData" class="text-center py-8">
                                <p class="text-gray-500 mb-4">
                                    Click "Generate Report" to view assessment
                                    data
                                </p>
                            </div>

                            <div v-if="reportData && reportData.stats">
                                <!-- Assessment Stats -->
                                <div
                                    class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8"
                                >
                                    <div class="bg-blue-50 p-4 rounded-lg">
                                        <div
                                            class="text-sm font-medium text-blue-600"
                                        >
                                            Total Assessments
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-blue-900"
                                        >
                                            {{
                                                reportData.stats
                                                    .total_assessments
                                            }}
                                        </div>
                                        <div class="text-xs text-blue-600 mt-1">
                                            In selected period
                                        </div>
                                    </div>
                                    <div class="bg-green-50 p-4 rounded-lg">
                                        <div
                                            class="text-sm font-medium text-green-600"
                                        >
                                            Competent
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-green-900"
                                        >
                                            {{
                                                reportData.stats.competent_count
                                            }}
                                        </div>
                                        <div
                                            class="text-xs text-green-600 mt-1"
                                        >
                                            Passed assessments
                                        </div>
                                    </div>
                                    <div class="bg-red-50 p-4 rounded-lg">
                                        <div
                                            class="text-sm font-medium text-red-600"
                                        >
                                            Not Yet Competent
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-red-900"
                                        >
                                            {{
                                                reportData.stats
                                                    .not_competent_count
                                            }}
                                        </div>
                                        <div class="text-xs text-red-600 mt-1">
                                            Failed assessments
                                        </div>
                                    </div>
                                    <div class="bg-yellow-50 p-4 rounded-lg">
                                        <div
                                            class="text-sm font-medium text-yellow-600"
                                        >
                                            Reassessments
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-yellow-900"
                                        >
                                            {{
                                                reportData.stats
                                                    .reassessment_count
                                            }}
                                        </div>
                                        <div
                                            class="text-xs text-yellow-600 mt-1"
                                        >
                                            Second attempts
                                        </div>
                                    </div>
                                </div>

                                <!-- Program Results -->
                                <div
                                    v-if="reportData.programResults"
                                    class="bg-gray-50 rounded-lg p-6"
                                >
                                    <h4
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Assessment Results by Program
                                    </h4>
                                    <div class="space-y-3">
                                        <div
                                            v-for="program in reportData.programResults"
                                            :key="program.title"
                                            class="flex items-center justify-between p-3 bg-white rounded-lg"
                                        >
                                            <span class="text-sm font-medium">{{
                                                program.title
                                            }}</span>
                                            <div class="text-right">
                                                <span
                                                    class="text-sm text-green-600 font-semibold"
                                                    >{{ program.success_rate }}%
                                                    success rate</span
                                                >
                                                <div
                                                    class="text-xs text-gray-500"
                                                >
                                                    {{
                                                        program.total_assessments
                                                    }}
                                                    assessments
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Program Progress Report -->
                <div v-if="selectedReportType === 'program_progress'">
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">
                                Program Progress Report
                            </h3>
                        </div>

                        <div class="p-6">
                            <!-- Error Message -->
                            <div
                                v-if="reportError"
                                class="mb-4 p-4 bg-red-50 border border-red-300 rounded-md"
                            >
                                <div class="text-red-700">
                                    {{ reportError }}
                                </div>
                            </div>

                            <!-- Generate report prompt -->
                            <div v-if="!reportData" class="text-center py-8">
                                <p class="text-gray-500 mb-4">
                                    Click "Generate Report" to view program
                                    progress data
                                </p>
                            </div>

                            <div
                                v-if="reportData && reportData.programProgress"
                            >
                                <!-- Program Progress -->
                                <div class="bg-gray-50 rounded-lg p-6">
                                    <h4
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Program Progress by Batch
                                    </h4>
                                    <div class="space-y-4">
                                        <div
                                            v-for="program in reportData.programProgress"
                                            :key="`${program.title}-${program.batch_number}`"
                                            class="p-4 bg-white rounded-lg"
                                        >
                                            <div
                                                class="flex justify-between items-start mb-2"
                                            >
                                                <div>
                                                    <h5
                                                        class="font-medium text-gray-900"
                                                    >
                                                        {{ program.title }}
                                                    </h5>
                                                    <p
                                                        class="text-sm text-gray-600"
                                                    >
                                                        Batch
                                                        {{
                                                            program.batch_number
                                                        }}
                                                    </p>
                                                </div>
                                                <div class="text-right">
                                                    <span
                                                        class="text-sm font-medium text-blue-600"
                                                        >{{
                                                            program.current_enrollments
                                                        }}/{{
                                                            program.max_enrollments
                                                        }}</span
                                                    >
                                                    <p
                                                        class="text-xs text-gray-500"
                                                    >
                                                        {{
                                                            program.capacity_utilization
                                                        }}% capacity
                                                    </p>
                                                </div>
                                            </div>
                                            <div
                                                class="grid grid-cols-3 gap-4 text-sm"
                                            >
                                                <div>
                                                    <span class="text-gray-600"
                                                        >Active:</span
                                                    >
                                                    <span
                                                        class="ml-1 font-medium text-green-600"
                                                        >{{
                                                            program.active_count
                                                        }}</span
                                                    >
                                                </div>
                                                <div>
                                                    <span class="text-gray-600"
                                                        >Completed:</span
                                                    >
                                                    <span
                                                        class="ml-1 font-medium text-blue-600"
                                                        >{{
                                                            program.completed_count
                                                        }}</span
                                                    >
                                                </div>
                                                <div>
                                                    <span class="text-gray-600"
                                                        >Completion Rate:</span
                                                    >
                                                    <span
                                                        class="ml-1 font-medium"
                                                        >{{
                                                            program.completion_rate
                                                        }}%</span
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Training Results Report -->
                <div v-if="selectedReportType === 'training_results'">
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">
                                Training Results Report
                            </h3>
                        </div>

                        <div class="p-6">
                            <!-- Error Message -->
                            <div
                                v-if="reportError"
                                class="mb-4 p-4 bg-red-50 border border-red-300 rounded-md"
                            >
                                <div class="text-red-700">
                                    {{ reportError }}
                                </div>
                            </div>

                            <!-- Generate report prompt -->
                            <div v-if="!reportData" class="text-center py-8">
                                <p class="text-gray-500 mb-4">
                                    Click "Generate Report" to view training
                                    results data
                                </p>
                            </div>

                            <div v-if="reportData && reportData.stats">
                                <!-- Training Stats -->
                                <div
                                    class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8"
                                >
                                    <div class="bg-blue-50 p-4 rounded-lg">
                                        <div
                                            class="text-sm font-medium text-blue-600"
                                        >
                                            Total Trainings
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-blue-900"
                                        >
                                            {{
                                                reportData.stats.total_trainings
                                            }}
                                        </div>
                                        <div class="text-xs text-blue-600 mt-1">
                                            In selected period
                                        </div>
                                    </div>
                                    <div class="bg-green-50 p-4 rounded-lg">
                                        <div
                                            class="text-sm font-medium text-green-600"
                                        >
                                            Completed
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-green-900"
                                        >
                                            {{
                                                reportData.stats.completed_count
                                            }}
                                        </div>
                                        <div
                                            class="text-xs text-green-600 mt-1"
                                        >
                                            Successfully finished
                                        </div>
                                    </div>
                                    <div class="bg-yellow-50 p-4 rounded-lg">
                                        <div
                                            class="text-sm font-medium text-yellow-600"
                                        >
                                            Ongoing
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-yellow-900"
                                        >
                                            {{ reportData.stats.ongoing_count }}
                                        </div>
                                        <div
                                            class="text-xs text-yellow-600 mt-1"
                                        >
                                            Currently active
                                        </div>
                                    </div>
                                    <div class="bg-red-50 p-4 rounded-lg">
                                        <div
                                            class="text-sm font-medium text-red-600"
                                        >
                                            Incomplete
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-red-900"
                                        >
                                            {{
                                                reportData.stats
                                                    .incomplete_count
                                            }}
                                        </div>
                                        <div class="text-xs text-red-600 mt-1">
                                            Dropped out
                                        </div>
                                    </div>
                                </div>

                                <!-- Program Results -->
                                <div
                                    v-if="reportData.programResults"
                                    class="bg-gray-50 rounded-lg p-6"
                                >
                                    <h4
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Training Results by Program
                                    </h4>
                                    <div class="space-y-3">
                                        <div
                                            v-for="program in reportData.programResults"
                                            :key="program.title"
                                            class="flex items-center justify-between p-3 bg-white rounded-lg"
                                        >
                                            <span class="text-sm font-medium">{{
                                                program.title
                                            }}</span>
                                            <div class="text-right">
                                                <span
                                                    class="text-sm text-green-600 font-semibold"
                                                    >{{
                                                        program.completion_rate
                                                    }}% completion</span
                                                >
                                                <div
                                                    class="text-xs text-gray-500"
                                                >
                                                    {{ program.total_trainees }}
                                                    trainees
                                                </div>
                                            </div>
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
                                Employment Endorsements Report
                            </h3>
                        </div>

                        <div class="p-6">
                            <!-- Error Message -->
                            <div
                                v-if="reportError"
                                class="mb-4 p-4 bg-red-50 border border-red-300 rounded-md"
                            >
                                <div class="text-red-700">
                                    {{ reportError }}
                                </div>
                            </div>

                            <!-- Generate report prompt -->
                            <div v-if="!reportData" class="text-center py-8">
                                <p class="text-gray-500 mb-4">
                                    Click "Generate Report" to view employment
                                    data
                                </p>
                            </div>

                            <div v-if="reportData && reportData.stats">
                                <!-- Employment Stats -->
                                <div
                                    class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8"
                                >
                                    <div class="bg-purple-50 p-4 rounded-lg">
                                        <div
                                            class="text-sm font-medium text-purple-600"
                                        >
                                            Total Graduates
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-purple-900"
                                        >
                                            {{
                                                reportData.stats.total_graduates
                                            }}
                                        </div>
                                        <div
                                            class="text-xs text-purple-600 mt-1"
                                        >
                                            In selected period
                                        </div>
                                    </div>
                                    <div class="bg-green-50 p-4 rounded-lg">
                                        <div
                                            class="text-sm font-medium text-green-600"
                                        >
                                            Employed
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-green-900"
                                        >
                                            {{
                                                reportData.stats.employed_count
                                            }}
                                        </div>
                                        <div
                                            class="text-xs text-green-600 mt-1"
                                        >
                                            Successfully placed
                                        </div>
                                    </div>
                                    <div class="bg-blue-50 p-4 rounded-lg">
                                        <div
                                            class="text-sm font-medium text-blue-600"
                                        >
                                            Placement Rate
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-blue-900"
                                        >
                                            {{
                                                reportData.stats.placement_rate
                                            }}%
                                        </div>
                                        <div class="text-xs text-blue-600 mt-1">
                                            Employment success
                                        </div>
                                    </div>
                                    <div class="bg-yellow-50 p-4 rounded-lg">
                                        <div
                                            class="text-sm font-medium text-yellow-600"
                                        >
                                            Average Salary
                                        </div>
                                        <div
                                            class="text-2xl font-bold text-yellow-900"
                                        >
                                            ₱{{
                                                formatNumber(
                                                    reportData.stats
                                                        .average_salary
                                                )
                                            }}
                                        </div>
                                        <div
                                            class="text-xs text-yellow-600 mt-1"
                                        >
                                            Estimated average
                                        </div>
                                    </div>
                                </div>

                                <!-- Top Employers -->
                                <div class="bg-gray-50 rounded-lg p-6">
                                    <h4
                                        class="text-lg font-medium text-gray-900 mb-4"
                                    >
                                        Top Employer Partners
                                    </h4>
                                    <div class="space-y-3">
                                        <div
                                            v-for="employer in reportData.stats
                                                .top_employers"
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
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Officer Activities Report -->
                <div v-if="selectedReportType === 'officer_activities'">
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">
                                Officer Activities Report
                            </h3>
                        </div>

                        <div class="p-6">
                            <!-- Error Message -->
                            <div
                                v-if="reportError"
                                class="mb-4 p-4 bg-red-50 border border-red-300 rounded-md"
                            >
                                <div class="text-red-700">
                                    {{ reportError }}
                                </div>
                            </div>

                            <!-- Generate report prompt -->
                            <div v-if="!reportData" class="text-center py-8">
                                <p class="text-gray-500 mb-4">
                                    Click "Generate Report" to view officer
                                    activities data
                                </p>
                            </div>

                            <div v-if="reportData">
                                <div
                                    class="grid grid-cols-1 lg:grid-cols-2 gap-8"
                                >
                                    <!-- Enrollment Activities -->
                                    <div
                                        v-if="reportData.enrollmentActivities"
                                        class="bg-gray-50 rounded-lg p-6"
                                    >
                                        <h4
                                            class="text-lg font-medium text-gray-900 mb-4"
                                        >
                                            Enrollment Officer Activities
                                        </h4>
                                        <div class="space-y-3">
                                            <div
                                                v-for="activity in reportData.enrollmentActivities"
                                                :key="`${activity.officer_name}-${activity.date}`"
                                                class="flex items-center justify-between p-3 bg-white rounded-lg"
                                            >
                                                <div>
                                                    <span
                                                        class="text-sm font-medium"
                                                        >{{
                                                            activity.officer_name
                                                        }}</span
                                                    >
                                                    <div
                                                        class="text-xs text-gray-500"
                                                    >
                                                        {{ activity.date }}
                                                    </div>
                                                </div>
                                                <span
                                                    class="text-sm text-blue-600 font-semibold"
                                                    >{{
                                                        activity.enrollments_processed
                                                    }}
                                                    enrollments</span
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Payment Activities -->
                                    <div
                                        v-if="reportData.paymentActivities"
                                        class="bg-gray-50 rounded-lg p-6"
                                    >
                                        <h4
                                            class="text-lg font-medium text-gray-900 mb-4"
                                        >
                                            Cashier Activities
                                        </h4>
                                        <div class="space-y-3">
                                            <div
                                                v-for="activity in reportData.paymentActivities"
                                                :key="`${activity.cashier_name}-${activity.date}`"
                                                class="flex items-center justify-between p-3 bg-white rounded-lg"
                                            >
                                                <div>
                                                    <span
                                                        class="text-sm font-medium"
                                                        >{{
                                                            activity.cashier_name
                                                        }}</span
                                                    >
                                                    <div
                                                        class="text-xs text-gray-500"
                                                    >
                                                        {{ activity.date }}
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <span
                                                        class="text-sm text-green-600 font-semibold"
                                                        >₱{{
                                                            formatNumber(
                                                                activity.amount_collected
                                                            )
                                                        }}</span
                                                    >
                                                    <div
                                                        class="text-xs text-gray-500"
                                                    >
                                                        {{
                                                            activity.receipts_processed
                                                        }}
                                                        receipts
                                                    </div>
                                                </div>
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
