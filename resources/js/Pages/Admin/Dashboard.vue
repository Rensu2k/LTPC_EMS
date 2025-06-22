<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const props = defineProps({
    stats: Object,
    recent_activities: Array,
    program_progress: Array,
    payment_summary: Object,
    flash: Object,
});

const dashboardStats = computed(
    () =>
        props.stats || {
            total_enrollments: 0,
            active_programs: 0,
            regular_trainees: 0,
            scholar_trainees: 0,
            pending_payments: 0,
            employment_endorsements: 0,
            employment_rate: 0,
        }
);

const recentActivities = computed(() => props.recent_activities || []);

const programProgressData = computed(
    () =>
        props.program_progress || [
            {
                name: "Welding",
                enrolled: 22,
                max: 25,
                progress: 88,
            },
            {
                name: "Carpentry",
                enrolled: 15,
                max: 25,
                progress: 60,
            },
            {
                name: "Computer Systems Servicing",
                enrolled: 25,
                max: 25,
                progress: 100,
            },
        ]
);

const paymentSummary = computed(
    () =>
        props.payment_summary || {
            regular_trainees: 0,
            scholar_trainees: 0,
            paid_training: 0,
            paid_assessment: 0,
            pending_training: 0,
            pending_assessment: 0,
        }
);

const quickStats = computed(() => [
    {
        title: "Total Enrollments",
        value: dashboardStats.value.total_enrollments,
        icon: "📊",
        color: "bg-blue-500",
        bgColor: "bg-blue-50",
        textColor: "text-blue-900",
        subColor: "text-blue-600",
        link: "/admin/trainees",
        subtitle: "Active enrollments",
    },
    {
        title: "Program Progress",
        value: `${dashboardStats.value.active_programs} Active`,
        icon: "📈",
        color: "bg-green-500",
        bgColor: "bg-green-50",
        textColor: "text-green-900",
        subColor: "text-green-600",
        link: "/admin/programs",
        subtitle: "Max 25 per program",
    },
    {
        title: "Payment Status",
        value: `${dashboardStats.value.pending_payments} Pending`,
        icon: "💵",
        color: "bg-yellow-500",
        bgColor: "bg-yellow-50",
        textColor: "text-yellow-900",
        subColor: "text-yellow-600",
        link: "/admin/payments",
        subtitle: "Regular trainees only",
    },
    {
        title: "Employment Rate",
        value: `${dashboardStats.value.employment_rate}%`,
        icon: "💼",
        color: "bg-pink-500",
        bgColor: "bg-pink-50",
        textColor: "text-pink-900",
        subColor: "text-pink-600",
        link: "/admin/employments",
        subtitle: `${dashboardStats.value.employment_endorsements} endorsed`,
    },
]);

const getActivityIcon = (type) => {
    const icons = {
        enrollment: "📝",
        payment: "💰",
        completion: "✅",
        assessment: "🏆",
        trainer: "👨‍🏫",
        default: "📋",
    };
    return icons[type] || icons.default;
};

const getActivityTypeColor = (type) => {
    const colors = {
        enrollment: "bg-blue-100 text-blue-800",
        payment: "bg-green-100 text-green-800",
        completion: "bg-purple-100 text-purple-800",
        assessment: "bg-indigo-100 text-indigo-800",
        trainer: "bg-yellow-100 text-yellow-800",
        default: "bg-gray-100 text-gray-800",
    };
    return colors[type] || colors.default;
};

const getActivityTypeLabel = (type) => {
    const labels = {
        enrollment: "Enrollment",
        payment: "Payment",
        completion: "Completion",
        assessment: "Assessment",
        trainer: "Trainer",
        default: "Activity",
    };
    return labels[type] || labels.default;
};
</script>

<template>
    <Head title="Admin Dashboard - LTPC EMS" />
    <AuthenticatedLayout>
        <div class="py-8 px-8 bg-gray-50 min-h-screen">
            <!-- Welcome Section -->
            <div class="mb-8 animate-fade-in">
                <div
                    class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl shadow-lg p-8 text-white relative overflow-hidden"
                >
                    <div class="relative z-10">
                        <h1 class="text-3xl font-bold mb-2">
                            LTPC EMTS Admin Monitoring
                        </h1>
                        <p class="text-indigo-100 text-lg">
                            Monitor enrollment activities, track training
                            progress, and oversee payment transactions handled
                            by officers and cashiers.
                        </p>
                        <div class="mt-6 flex space-x-4">
                            <Link
                                :href="route('admin.reports')"
                                class="bg-white text-indigo-600 px-6 py-2 rounded-lg font-semibold hover:bg-indigo-50 transition-colors"
                            >
                                Generate Reports
                            </Link>
                            <Link
                                :href="route('admin.trainees')"
                                class="border border-white text-white px-6 py-2 rounded-lg font-semibold hover:bg-white hover:text-indigo-600 transition-colors"
                            >
                                Monitor Enrollments
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monitoring Overview -->
            <div class="mb-8 animate-fade-in">
                <h3
                    class="text-lg font-semibold text-gray-800 mb-4 relative pb-2 after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-12 after:h-0.5 after:bg-gradient-to-r after:rounded"
                >
                    System Monitoring Overview
                </h3>
                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                >
                    <Link
                        v-for="stat in quickStats"
                        :key="stat.title"
                        :href="stat.link"
                        :class="stat.bgColor"
                        class="p-6 rounded-xl shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer border border-gray-100 hover:scale-105"
                    >
                        <div class="flex items-center justify-between">
                            <div>
                                <p
                                    :class="stat.subColor"
                                    class="text-sm font-medium"
                                >
                                    {{ stat.title }}
                                </p>
                                <p
                                    :class="stat.textColor"
                                    class="text-3xl font-bold mt-1"
                                >
                                    {{ stat.value }}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ stat.subtitle }}
                                </p>
                            </div>
                            <div class="text-4xl animate-bounce">
                                {{ stat.icon }}
                            </div>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column -->
                <div class="lg:col-span-2">
                    <!-- Program Progress Monitoring -->
                    <div
                        class="bg-white rounded-xl shadow-sm overflow-hidden mb-8 animate-fade-in border border-gray-100"
                    >
                        <div class="p-6 border-b border-gray-200">
                            <h3
                                class="text-lg font-semibold text-gray-900 relative pb-2 after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-20 after:h-0.5 after:bg-gradient-to-r after:rounded"
                            >
                                Program Capacity Monitoring
                            </h3>
                            <p class="text-sm text-gray-500">
                                Maximum 25 trainees per program
                            </p>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div
                                    v-for="program in programProgressData"
                                    :key="program.name"
                                    class="bg-gradient-to-br from-gray-50 to-gray-100 p-4 rounded-lg border border-gray-200"
                                >
                                    <div
                                        class="flex items-center justify-between mb-2"
                                    >
                                        <span
                                            class="font-medium text-gray-900"
                                            >{{ program.name }}</span
                                        >
                                        <span class="text-sm text-gray-600"
                                            >{{ program.enrolled }}/{{
                                                program.max
                                            }}</span
                                        >
                                    </div>
                                    <div
                                        class="w-full bg-gray-200 rounded-full h-2"
                                    >
                                        <div
                                            :class="
                                                program.enrolled >= 25
                                                    ? 'bg-red-500'
                                                    : program.enrolled >= 20
                                                    ? 'bg-yellow-500'
                                                    : 'bg-green-500'
                                            "
                                            class="h-2 rounded-full transition-all duration-500"
                                            :style="`width: ${program.progress}%`"
                                        ></div>
                                    </div>
                                    <div
                                        class="mt-1 text-xs"
                                        :class="
                                            program.enrolled >= 25
                                                ? 'text-red-600'
                                                : program.enrolled >= 20
                                                ? 'text-yellow-600'
                                                : 'text-green-600'
                                        "
                                    >
                                        {{
                                            program.enrolled >= 25
                                                ? "Full Capacity"
                                                : program.enrolled >= 20
                                                ? "Near Capacity"
                                                : "Available Slots"
                                        }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Officer & Cashier Activities -->
                    <div
                        class="bg-white rounded-xl shadow-sm overflow-hidden animate-fade-in border border-gray-100"
                    >
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3
                                        class="text-lg font-semibold text-gray-900 relative pb-2 after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-24 after:h-0.5 after:bg-gradient-to-r afte:rounded"
                                    >
                                        Recent Officer & Cashier Activities
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        Latest actions performed by enrollment
                                        officers and cashiers
                                    </p>
                                </div>
                                <Link
                                    :href="route('admin.reports')"
                                    class="text-indigo-600 hover:text-indigo-700 text-sm font-medium transition-colors"
                                >
                                    View All Activities
                                </Link>
                            </div>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <div
                                v-for="activity in recentActivities"
                                :key="activity.id"
                                class="p-4 hover:bg-gray-50 transition-colors"
                            >
                                <div class="flex items-start space-x-4">
                                    <!-- Activity Icon -->
                                    <div class="flex-shrink-0">
                                        <div
                                            class="h-10 w-10 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center border-2 border-gray-300"
                                        >
                                            <span class="text-xl">{{
                                                getActivityIcon(activity.type)
                                            }}</span>
                                        </div>
                                    </div>

                                    <!-- Activity Content -->
                                    <div class="flex-1 min-w-0">
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <div
                                                class="flex items-center space-x-2"
                                            >
                                                <span
                                                    :class="
                                                        getActivityTypeColor(
                                                            activity.type
                                                        )
                                                    "
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                                                >
                                                    {{
                                                        getActivityTypeLabel(
                                                            activity.type
                                                        )
                                                    }}
                                                </span>
                                                <span
                                                    v-if="activity.officer"
                                                    class="text-sm font-medium text-gray-900"
                                                >
                                                    {{ activity.officer }}
                                                </span>
                                            </div>
                                            <span
                                                class="text-sm text-gray-500"
                                                >{{ activity.time }}</span
                                            >
                                        </div>
                                        <p class="mt-1 text-sm text-gray-900">
                                            {{ activity.message }}
                                        </p>
                                        <div
                                            v-if="activity.details"
                                            class="mt-2 text-sm text-gray-500"
                                        >
                                            {{ activity.details }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div
                                v-if="!recentActivities.length"
                                class="p-6 text-center"
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
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    <h3
                                        class="mt-2 text-sm font-medium text-gray-900"
                                    >
                                        No recent activities
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-500">
                                        Activities performed by officers and
                                        cashiers will appear here.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Monitoring Summaries -->
                <div class="space-y-6">
                    <!-- Trainee Status Summary -->
                    <div
                        class="bg-white rounded-xl shadow-sm overflow-hidden animate-fade-in border border-gray-100"
                    >
                        <div class="p-6 border-b border-gray-200">
                            <h3
                                class="text-lg font-semibold text-gray-900 relative pb-2 after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-16 after:h-0.5 after:bg-gradient-to-r after:rounded"
                            >
                                Trainee Status
                            </h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div
                                class="flex items-center justify-between p-3 bg-green-50 rounded-lg border border-green-200"
                            >
                                <div>
                                    <div class="font-medium text-green-900">
                                        Regular Trainees
                                    </div>
                                    <div class="text-sm text-green-600">
                                        Pay for training & assessment
                                    </div>
                                </div>
                                <div class="text-2xl font-bold text-green-900">
                                    {{ paymentSummary.regular_trainees }}
                                </div>
                            </div>
                            <div
                                class="flex items-center justify-between p-3 bg-blue-50 rounded-lg border border-blue-200"
                            >
                                <div>
                                    <div class="font-medium text-blue-900">
                                        Scholar Trainees
                                    </div>
                                    <div class="text-sm text-blue-600">
                                        Free training & assessment
                                    </div>
                                </div>
                                <div class="text-2xl font-bold text-blue-900">
                                    {{ paymentSummary.scholar_trainees }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Monitoring -->
                    <div
                        class="bg-white rounded-xl shadow-sm overflow-hidden animate-fade-in border border-gray-100"
                    >
                        <div class="p-6 border-b border-gray-200">
                            <h3
                                class="text-lg font-semibold text-gray-900 relative pb-2 after:content-[''] after:absolute after:bottom-0 after:left-0 after:w-20 after:h-0.5 after:bg-gradient-to-r after:rounded"
                            >
                                Payment Monitoring
                            </h3>
                            <p class="text-xs text-gray-500">
                                Regular trainees only
                            </p>
                        </div>
                        <div class="p-6 space-y-3">
                            <div
                                class="flex items-center justify-between text-sm"
                            >
                                <span class="text-gray-600"
                                    >Training Payments</span
                                >
                                <span class="font-medium text-green-600"
                                    >{{ paymentSummary.paid_training }} paid,
                                    {{ paymentSummary.pending_training }}
                                    pending</span
                                >
                            </div>
                            <div
                                class="flex items-center justify-between text-sm"
                            >
                                <span class="text-gray-600"
                                    >Assessment Payments</span
                                >
                                <span class="font-medium text-green-600"
                                    >{{ paymentSummary.paid_assessment }} paid,
                                    {{ paymentSummary.pending_assessment }}
                                    pending</span
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
