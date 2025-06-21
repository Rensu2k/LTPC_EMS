<script setup>
import { ref, computed } from "vue";
import NavLink from "@/Components/NavLink.vue";
import ResponsiveNavLink from "@/Components/ResponsiveNavLink.vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { Link, usePage, router } from "@inertiajs/vue3";

const showingNavigationDropdown = ref(false);
const user = computed(() => usePage().props.auth.user);

// Dynamic topbar title based on current route
const pageTitle = computed(() => {
    const currentRoute = route().current();

    // Admin routes
    if (currentRoute === "admin.dashboard") return "Admin Dashboard";
    if (currentRoute === "admin.courses") return "Manage Programs";
    if (currentRoute === "admin.trainees") return "Manage Trainees";
    if (currentRoute === "admin.trainers") return "Manage Trainers";
    if (currentRoute === "admin.payments") return "Manage Payments";
    if (currentRoute === "admin.trainings") return "Trainings Results";
    if (currentRoute === "admin.assessments") return "Assessments Results";
    if (currentRoute === "admin.employments") return "Employments Referrals";
    if (currentRoute === "admin.reports") return "Reports";
    if (currentRoute === "admin.users") return "Manage Users";

    // Officer routes
    if (currentRoute === "officer.dashboard") return "Officer Dashboard";
    if (currentRoute === "officer.courses") return "Courses";
    if (currentRoute === "officer.trainees") return "Trainees";
    if (currentRoute === "officer.trainers") return "Trainers";
    if (currentRoute === "officer.assessments") return "Assessments";

    // Cashier routes
    if (currentRoute === "cashier.dashboard") return "Cashier Dashboard";
    if (currentRoute === "cashier.payments") return "Payments";
    if (currentRoute === "cashier.receipts") return "Receipts";

    // Default fallback
    return "Dashboard";
});

function logout() {
    router.post(route("logout"));
}
</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <!-- Sidebar -->
        <aside
            class="w-64 bg-blue-900 text-white flex flex-col h-screen overflow-hidden fixed z-30"
        >
            <div
                class="flex items-center justify-center px-6 py-6 border-b border-blue-800"
            >
                <ApplicationLogo class="h-36 w-36 bg-white rounded-full p-1" />
            </div>
            <div
                class="flex items-center gap-3 px-6 py-4 border-b border-blue-800"
            >
                <div
                    class="h-10 w-10 rounded-full bg-orange-400 flex items-center justify-center font-bold text-lg"
                >
                    {{
                        user?.name
                            ? user.name
                                  .split(" ")
                                  .map((n) => n[0])
                                  .join("")
                            : ""
                    }}
                </div>
                <div>
                    <div class="font-semibold">{{ user?.name }}</div>
                    <div class="text-xs text-blue-100">
                        {{ user?.role ? user.role.replace("_", " ") : "" }}
                    </div>
                </div>
            </div>
            <div class="px-6 py-2 text-xs text-blue-200 tracking-widest">
                MAIN NAVIGATION
            </div>
            <nav class="flex flex-col flex-1 px-2 space-y-2 mt-2">
                <template v-if="user?.role === 'admin'">
                    <NavLink
                        :href="route('admin.dashboard')"
                        :active="route().current('admin.dashboard')"
                        class="group py-3 px-2 rounded-lg"
                    >
                        <span class="flex items-center gap-4">
                            <svg
                                class="h-6 w-6"
                                :class="
                                    route().current('admin.dashboard')
                                        ? 'text-white'
                                        : 'text-white/70 group-hover:text-white'
                                "
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3 12l2-2m0 0l7-7 7 7M13 5v6h6m-6 0H7m6 0v6m0 0h6m-6 0H7"
                                />
                            </svg>
                            <span
                                :class="
                                    route().current('admin.dashboard')
                                        ? 'font-semibold text-lg text-white'
                                        : 'text-lg text-white/80 group-hover:text-white'
                                "
                                >Dashboard</span
                            >
                        </span>
                    </NavLink>
                    <NavLink
                        :href="route('admin.courses')"
                        :active="route().current('admin.courses')"
                        class="group py-3 px-2 rounded-lg"
                    >
                        <span class="flex items-center gap-4">
                            <svg
                                class="h-6 w-6"
                                :class="
                                    route().current('admin.courses')
                                        ? 'text-white'
                                        : 'text-white/70 group-hover:text-white'
                                "
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <rect
                                    width="18"
                                    height="12"
                                    x="3"
                                    y="6"
                                    rx="2"
                                />
                            </svg>
                            <span
                                :class="
                                    route().current('admin.courses')
                                        ? 'font-semibold text-lg text-white'
                                        : 'text-lg text-white/80 group-hover:text-white'
                                "
                                >Programs</span
                            >
                        </span>
                    </NavLink>
                    <NavLink
                        :href="route('admin.trainees')"
                        :active="route().current('admin.trainees')"
                        class="group py-3 px-2 rounded-lg"
                    >
                        <span class="flex items-center gap-4">
                            <svg
                                class="h-6 w-6"
                                :class="
                                    route().current('admin.trainees')
                                        ? 'text-white'
                                        : 'text-white/70 group-hover:text-white'
                                "
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0z"
                                />
                            </svg>
                            <span
                                :class="
                                    route().current('admin.trainees')
                                        ? 'font-semibold text-lg text-white'
                                        : 'text-lg text-white/80 group-hover:text-white'
                                "
                                >Trainees</span
                            >
                        </span>
                    </NavLink>
                    <NavLink
                        :href="route('admin.trainers')"
                        :active="route().current('admin.trainers')"
                        class="group py-3 px-2 rounded-lg"
                    >
                        <span class="flex items-center gap-4">
                            <svg
                                class="h-6 w-6"
                                :class="
                                    route().current('admin.trainers')
                                        ? 'text-white'
                                        : 'text-white/70 group-hover:text-white'
                                "
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                            <span
                                :class="
                                    route().current('admin.trainers')
                                        ? 'font-semibold text-lg text-white'
                                        : 'text-lg text-white/80 group-hover:text-white'
                                "
                                >Trainers</span
                            >
                        </span>
                    </NavLink>
                    <NavLink
                        :href="route('admin.payments')"
                        :active="route().current('admin.payments')"
                        class="group py-3 px-2 rounded-lg"
                    >
                        <span class="flex items-center gap-4">
                            <svg
                                class="h-6 w-6"
                                :class="
                                    route().current('admin.payments')
                                        ? 'text-white'
                                        : 'text-white/70 group-hover:text-white'
                                "
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3zm0 0V4m0 7v7m0 0h4m-4 0H8"
                                />
                            </svg>
                            <span
                                :class="
                                    route().current('admin.payments')
                                        ? 'font-semibold text-lg text-white'
                                        : 'text-lg text-white/80 group-hover:text-white'
                                "
                                >Payments</span
                            >
                        </span>
                    </NavLink>
                    <NavLink
                        :href="route('admin.trainings')"
                        :active="route().current('admin.trainings')"
                        class="group py-3 px-2 rounded-lg"
                    >
                        <span class="flex items-center gap-4">
                            <svg
                                class="h-6 w-6"
                                :class="
                                    route().current('admin.trainings')
                                        ? 'text-white'
                                        : 'text-white/70 group-hover:text-white'
                                "
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            <span
                                :class="
                                    route().current('admin.trainings')
                                        ? 'font-semibold text-lg text-white'
                                        : 'text-lg text-white/80 group-hover:text-white'
                                "
                                >Training Results</span
                            >
                        </span>
                    </NavLink>
                    <NavLink
                        :href="route('admin.assessments')"
                        :active="route().current('admin.assessments')"
                        class="group py-3 px-2 rounded-lg"
                    >
                        <span class="flex items-center gap-4">
                            <svg
                                class="h-6 w-6"
                                :class="
                                    route().current('admin.assessments')
                                        ? 'text-white'
                                        : 'text-white/70 group-hover:text-white'
                                "
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            <span
                                :class="
                                    route().current('admin.assessments')
                                        ? 'font-semibold text-lg text-white'
                                        : 'text-lg text-white/80 group-hover:text-white'
                                "
                                >Assessment Results</span
                            >
                        </span>
                    </NavLink>
                    <NavLink
                        :href="route('admin.employments')"
                        :active="route().current('admin.employments')"
                        class="group py-3 px-2 rounded-lg"
                    >
                        <span class="flex items-center gap-4">
                            <svg
                                class="h-6 w-6"
                                :class="
                                    route().current('admin.employments')
                                        ? 'text-white'
                                        : 'text-white/70 group-hover:text-white'
                                "
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            <span
                                :class="
                                    route().current('admin.employments')
                                        ? 'font-semibold text-lg text-white'
                                        : 'text-lg text-white/80 group-hover:text-white'
                                "
                                >Employment Referrals</span
                            >
                        </span>
                    </NavLink>
                    <NavLink
                        :href="route('admin.reports')"
                        :active="route().current('admin.reports')"
                        class="group py-3 px-2 rounded-lg"
                    >
                        <span class="flex items-center gap-4">
                            <svg
                                class="h-6 w-6"
                                :class="
                                    route().current('admin.reports')
                                        ? 'text-white'
                                        : 'text-white/70 group-hover:text-white'
                                "
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 17v-2a4 4 0 014-4h4"
                                />
                            </svg>
                            <span
                                :class="
                                    route().current('admin.reports')
                                        ? 'font-semibold text-lg text-white'
                                        : 'text-lg text-white/80 group-hover:text-white'
                                "
                                >Reports</span
                            >
                        </span>
                    </NavLink>
                    <NavLink
                        :href="route('admin.users')"
                        :active="route().current('admin.users')"
                        class="group py-3 px-2 rounded-lg"
                    >
                        <span class="flex items-center gap-4">
                            <svg
                                class="h-6 w-6"
                                :class="
                                    route().current('admin.users')
                                        ? 'text-white'
                                        : 'text-white/70 group-hover:text-white'
                                "
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                            <span
                                :class="
                                    route().current('admin.users')
                                        ? 'font-semibold text-lg text-white'
                                        : 'text-lg text-white/80 group-hover:text-white'
                                "
                                >Manage Users</span
                            >
                        </span>
                    </NavLink>
                </template>

                <template v-else-if="user?.role === 'officer'">
                    <NavLink
                        :href="route('officer.dashboard')"
                        :active="route().current('officer.dashboard')"
                        class="group py-3 px-2 rounded-lg"
                    >
                        <span class="flex items-center gap-4">
                            <svg
                                class="h-6 w-6"
                                :class="
                                    route().current('officer.dashboard')
                                        ? 'text-white'
                                        : 'text-white/70 group-hover:text-white'
                                "
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <rect
                                    x="3"
                                    y="3"
                                    width="7"
                                    height="7"
                                    rx="1.5"
                                />
                                <rect
                                    x="14"
                                    y="3"
                                    width="7"
                                    height="7"
                                    rx="1.5"
                                />
                                <rect
                                    x="14"
                                    y="14"
                                    width="7"
                                    height="7"
                                    rx="1.5"
                                />
                                <rect
                                    x="3"
                                    y="14"
                                    width="7"
                                    height="7"
                                    rx="1.5"
                                />
                            </svg>
                            <span
                                :class="
                                    route().current('officer.dashboard')
                                        ? 'font-semibold text-lg text-white'
                                        : 'text-lg text-white/80 group-hover:text-white'
                                "
                                >Dashboard</span
                            >
                        </span>
                    </NavLink>
                    <NavLink
                        :href="route('officer.courses')"
                        :active="route().current('officer.courses')"
                        class="group py-3 px-2 rounded-lg"
                    >
                        <span class="flex items-center gap-4">
                            <svg
                                class="h-6 w-6"
                                :class="
                                    route().current('officer.courses')
                                        ? 'text-white'
                                        : 'text-white/70 group-hover:text-white'
                                "
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <rect
                                    width="18"
                                    height="12"
                                    x="3"
                                    y="6"
                                    rx="2"
                                />
                            </svg>
                            <span
                                :class="
                                    route().current('officer.courses')
                                        ? 'font-semibold text-lg text-white'
                                        : 'text-lg text-white/80 group-hover:text-white'
                                "
                                >Courses</span
                            >
                        </span>
                    </NavLink>
                    <NavLink
                        :href="route('officer.trainees')"
                        :active="route().current('officer.trainees')"
                        class="group py-3 px-2 rounded-lg"
                    >
                        <span class="flex items-center gap-4">
                            <svg
                                class="h-6 w-6"
                                :class="
                                    route().current('officer.trainees')
                                        ? 'text-white'
                                        : 'text-white/70 group-hover:text-white'
                                "
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m9-4a4 4 0 11-8 0 4 4 0 018 0z"
                                />
                            </svg>
                            <span
                                :class="
                                    route().current('officer.trainees')
                                        ? 'font-semibold text-lg text-white'
                                        : 'text-lg text-white/80 group-hover:text-white'
                                "
                                >Trainees</span
                            >
                        </span>
                    </NavLink>
                    <NavLink
                        :href="route('officer.trainers')"
                        :active="route().current('officer.trainers')"
                        class="group py-3 px-2 rounded-lg"
                    >
                        <span class="flex items-center gap-4">
                            <svg
                                class="h-6 w-6"
                                :class="
                                    route().current('officer.trainers')
                                        ? 'text-white'
                                        : 'text-white/70 group-hover:text-white'
                                "
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                            <span
                                :class="
                                    route().current('officer.trainers')
                                        ? 'font-semibold text-lg text-white'
                                        : 'text-lg text-white/80 group-hover:text-white'
                                "
                                >Trainers</span
                            >
                        </span>
                    </NavLink>
                    <NavLink
                        :href="route('officer.assessments')"
                        :active="route().current('officer.assessments')"
                        class="group py-3 px-2 rounded-lg"
                    >
                        <span class="flex items-center gap-4">
                            <svg
                                class="h-6 w-6"
                                :class="
                                    route().current('officer.assessments')
                                        ? 'text-white'
                                        : 'text-white/70 group-hover:text-white'
                                "
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            <span
                                :class="
                                    route().current('officer.assessments')
                                        ? 'font-semibold text-lg text-white'
                                        : 'text-lg text-white/80 group-hover:text-white'
                                "
                                >Assessments</span
                            >
                        </span>
                    </NavLink>
                </template>
                <template v-else-if="user?.role === 'cashier'">
                    <NavLink
                        :href="route('cashier.dashboard')"
                        :active="route().current('cashier.dashboard')"
                        class="group py-3 px-2 rounded-lg"
                    >
                        <span class="flex items-center gap-4">
                            <svg
                                class="h-6 w-6"
                                :class="
                                    route().current('cashier.dashboard')
                                        ? 'text-white'
                                        : 'text-white/70 group-hover:text-white'
                                "
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M3 12l2-2m0 0l7-7 7 7M13 5v6h6m-6 0H7m6 0v6m0 0h6m-6 0H7"
                                />
                            </svg>
                            <span
                                :class="
                                    route().current('cashier.dashboard')
                                        ? 'font-semibold text-lg text-white'
                                        : 'text-lg text-white/80 group-hover:text-white'
                                "
                                >Dashboard</span
                            >
                        </span>
                    </NavLink>
                    <NavLink
                        :href="route('cashier.payments')"
                        :active="route().current('cashier.payments')"
                        class="group py-3 px-2 rounded-lg"
                    >
                        <span class="flex items-center gap-4">
                            <svg
                                class="h-6 w-6"
                                :class="
                                    route().current('cashier.payments')
                                        ? 'text-white'
                                        : 'text-white/70 group-hover:text-white'
                                "
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3zm0 0V4m0 7v7m0 0h4m-4 0H8"
                                />
                            </svg>
                            <span
                                :class="
                                    route().current('cashier.payments')
                                        ? 'font-semibold text-lg text-white'
                                        : 'text-lg text-white/80 group-hover:text-white'
                                "
                                >Payments</span
                            >
                        </span>
                    </NavLink>
                    <NavLink
                        :href="route('cashier.receipts')"
                        :active="route().current('cashier.receipts')"
                        class="group py-3 px-2 rounded-lg"
                    >
                        <span class="flex items-center gap-4">
                            <svg
                                class="h-6 w-6"
                                :class="
                                    route().current('cashier.receipts')
                                        ? 'text-white'
                                        : 'text-white/70 group-hover:text-white'
                                "
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            <span
                                :class="
                                    route().current('cashier.receipts')
                                        ? 'font-semibold text-lg text-white'
                                        : 'text-lg text-white/80 group-hover:text-white'
                                "
                                >Receipts</span
                            >
                        </span>
                    </NavLink>
                </template>
            </nav>
            <div class="mt-auto px-6 py-4 border-t border-blue-800">
                <button
                    @click="logout"
                    type="button"
                    class="flex items-center gap-2 w-full text-left text-white/80 hover:text-white font-semibold"
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
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a2 2 0 01-2 2H7a2 2 0 01-2-2V7a2 2 0 012-2h4a2 2 0 012 2v1"
                        />
                    </svg>
                    Logout
                </button>
            </div>
        </aside>
        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col h-screen ml-64">
            <!-- Fixed Top Bar -->
            <div
                class="flex items-center justify-between px-8 py-4 bg-white border-b border-gray-200 fixed top-0 right-0 z-20"
                style="left: 256px"
            >
                <div class="text-2xl font-semibold text-gray-900">
                    {{ pageTitle }}
                </div>
                <div class="flex items-center gap-4">
                    <!-- <div class="relative">
                        <input
                            type="text"
                            placeholder="sasa..."
                            class="pl-8 pr-4 py-2 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-200 text-gray-700 bg-gray-50 w-64"
                        />
                        <svg
                            class="absolute left-2 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <circle cx="11" cy="11" r="8" />
                            <line x1="21" y1="21" x2="16.65" y2="16.65" />
                        </svg>
                    </div> -->
                    <!-- <div class="relative">
                        <svg
                            class="h-6 w-6 text-gray-400"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                            />
                        </svg>
                        <span
                            class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full px-1.5"
                            >3</span
                        >
                    </div> -->
                    <!-- <div class="relative">
                        <svg
                            class="h-6 w-6 text-blue-700"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M21 12.5V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6.5M21 12.5v5.5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5.5m18 0l-9 6-9-6"
                            />
                        </svg>
                        <span
                            class="absolute -top-2 -right-2 bg-blue-700 text-white text-xs font-bold rounded-full px-1.5"
                            >5</span
                        >
                    </div> -->
                </div>
            </div>
            <!-- Scrollable Content Area -->
            <div class="flex-1 overflow-y-auto pt-20">
                <header class="bg-white shadow" v-if="$slots.header">
                    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        <slot name="header" />
                    </div>
                </header>
                <main>
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
