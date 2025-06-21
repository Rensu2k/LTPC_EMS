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
    employment_referrals: Array,
    flash: Object,
});

const showModal = ref(false);
const showDeleteModal = ref(false);
const showDetailsModal = ref(false);
const editingReferral = ref(null);
const deletingReferral = ref(null);
const viewingReferral = ref(null);
const searchQuery = ref("");
const selectedStatus = ref("");
const selectedCompany = ref("");

const form = useForm({
    trainee_id: "",
    company_id: "",
    position_title: "",
    job_description: "",
    referral_date: "",
    interview_date: "",
    status: "referred",
    notes: "",
    contact_person: "",
    contact_email: "",
    contact_phone: "",
});

const filteredReferrals = computed(() => {
    let filtered = props.employment_referrals || [];

    if (searchQuery.value) {
        filtered = filtered.filter(
            (referral) =>
                referral.trainee?.first_name
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                referral.trainee?.last_name
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                referral.company?.name
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase()) ||
                referral.position_title
                    ?.toLowerCase()
                    .includes(searchQuery.value.toLowerCase())
        );
    }

    if (selectedStatus.value) {
        filtered = filtered.filter(
            (referral) => referral.status === selectedStatus.value
        );
    }

    if (selectedCompany.value) {
        filtered = filtered.filter(
            (referral) => referral.company_id == selectedCompany.value
        );
    }

    return filtered;
});

const companies = computed(() => {
    const uniqueCompanies = [
        ...new Set(
            props.employment_referrals
                ?.map((referral) => referral.company)
                .filter(Boolean)
        ),
    ];
    return uniqueCompanies;
});

const openCreateModal = () => {
    form.reset();
    form.referral_date = new Date().toISOString().split("T")[0];
    editingReferral.value = null;
    showModal.value = true;
};

const openEditModal = (referral) => {
    editingReferral.value = referral;
    form.trainee_id = referral.trainee_id;
    form.company_id = referral.company_id;
    form.position_title = referral.position_title;
    form.job_description = referral.job_description;
    form.referral_date = referral.referral_date;
    form.interview_date = referral.interview_date;
    form.status = referral.status;
    form.notes = referral.notes;
    form.contact_person = referral.contact_person;
    form.contact_email = referral.contact_email;
    form.contact_phone = referral.contact_phone;
    showModal.value = true;
};

const openDetailsModal = (referral) => {
    viewingReferral.value = referral;
    showDetailsModal.value = true;
};

const openDeleteModal = (referral) => {
    deletingReferral.value = referral;
    showDeleteModal.value = true;
};

const submitForm = () => {
    if (editingReferral.value) {
        form.put(`/admin/employment-referrals/${editingReferral.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post("/admin/employment-referrals", {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

const deleteReferral = () => {
    router.delete(`/admin/employment-referrals/${deletingReferral.value.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
            deletingReferral.value = null;
        },
    });
};

const getStatusColor = (status) => {
    const colors = {
        hired: "bg-green-100 text-green-800",
        endorsed: "bg-blue-100 text-blue-800",
        referred: "bg-yellow-100 text-yellow-800",
        interview_scheduled: "bg-purple-100 text-purple-800",
        rejected: "bg-red-100 text-red-800",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};

const formatCurrency = (amount) => {
    if (!amount) return "N/A";
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(amount);
};

const getDaysAgo = (date) => {
    const today = new Date();
    const referralDate = new Date(date);
    const diffTime = Math.abs(today - referralDate);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
};

const exportEmploymentReport = () => {
    console.log("Exporting employment report...");
};
</script>

<template>
    <Head title="Employment Referrals Management" />
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
                                Employment Endorsements Monitoring
                            </h3>
                        </div>
                        <div class="flex space-x-3">
                            <PrimaryButton
                                @click="openCreateModal"
                                class="bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 transition-all duration-300"
                            >
                                 Add Referral
                            </PrimaryButton>
                            <SecondaryButton
                                @click="exportEmploymentReport"
                                class="bg-gray-100 text-gray-700 border-gray-300 hover:bg-gray-200 hover:border-gray-400 transition-all duration-300"
                            >
                                📄 Export Employment Report
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
                            <InputLabel for="search" value="Search Referrals" />
                            <TextInput
                                id="search"
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by trainee name"
                                class="mt-1 block w-full transition-all duration-300 border-2 border-transparent focus:border-green-500 focus:ring-2 focus:ring-green-200 hover:border-green-300"
                            />
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
                                <option value="referred">Referred</option>
                                <option value="interview_scheduled">
                                    Interview Scheduled
                                </option>
                                <option value="hired">Hired</option>
                                <option value="endorsed">Endorsed</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div>
                            <InputLabel
                                for="company-filter"
                                value="Filter by Company"
                            />
                            <select
                                id="company-filter"
                                v-model="selectedCompany"
                                class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            >
                                <option value="">All Companies</option>
                                <option
                                    v-for="company in companies"
                                    :key="company.id"
                                    :value="company.id"
                                >
                                    {{ company.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="p-6 border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
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
                                        Hired
                                    </p>
                                    <p
                                        class="text-lg font-semibold text-green-900"
                                    >
                                        {{
                                            filteredReferrals.filter(
                                                (r) => r.status === "hired"
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
                                            >📝</span
                                        >
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p
                                        class="text-xs font-medium text-blue-600"
                                    >
                                        Endorsed
                                    </p>
                                    <p
                                        class="text-lg font-semibold text-blue-900"
                                    >
                                        {{
                                            filteredReferrals.filter(
                                                (r) => r.status === "endorsed"
                                            ).length
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-yellow-50 p-4 rounded-lg border border-yellow-200"
                        >
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center"
                                    >
                                        <span class="text-white text-xs"
                                            >⏳</span
                                        >
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p
                                        class="text-xs font-medium text-yellow-600"
                                    >
                                        Referred
                                    </p>
                                    <p
                                        class="text-lg font-semibold text-yellow-900"
                                    >
                                        {{
                                            filteredReferrals.filter(
                                                (r) => r.status === "referred"
                                            ).length
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            class="bg-purple-50 p-4 rounded-lg border border-purple-200"
                        >
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center"
                                    >
                                        <span class="text-white text-xs"
                                            >📅</span
                                        >
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p
                                        class="text-xs font-medium text-purple-600"
                                    >
                                        Interview
                                    </p>
                                    <p
                                        class="text-lg font-semibold text-purple-900"
                                    >
                                        {{
                                            filteredReferrals.filter(
                                                (r) =>
                                                    r.status ===
                                                    "interview_scheduled"
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
                                            >#</span
                                        >
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <p
                                        class="text-xs font-medium text-gray-600"
                                    >
                                        Total
                                    </p>
                                    <p
                                        class="text-lg font-semibold text-gray-900"
                                    >
                                        {{ filteredReferrals.length }}
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
                                    Trainee
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Position/Company
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Status
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Dates
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                >
                                    Contact
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
                                v-for="referral in filteredReferrals"
                                :key="referral.id"
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
                                                        referral.trainee?.first_name?.charAt(
                                                            0
                                                        )
                                                    }}{{
                                                        referral.trainee?.last_name?.charAt(
                                                            0
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
                                                {{
                                                    referral.trainee?.first_name
                                                }}
                                                {{
                                                    referral.trainee?.last_name
                                                }}
                                            </div>
                                            <div
                                                class="text-sm text-gray-500 group-hover:text-green-500 transition-colors duration-200"
                                            >
                                                ID: {{ referral.trainee?.id }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ referral.position_title }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ referral.company?.name || "N/A" }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="getStatusColor(referral.status)"
                                        class="inline-flex px-2 py-1 text-xs font-semibold rounded-full border"
                                    >
                                        {{
                                            referral.status ===
                                            "interview_scheduled"
                                                ? "Interview Scheduled"
                                                : referral.status
                                                      .charAt(0)
                                                      .toUpperCase() +
                                                  referral.status.slice(1)
                                        }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        Referred: {{ referral.referral_date }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{
                                            referral.interview_date
                                                ? `Interview: ${referral.interview_date}`
                                                : "No interview scheduled"
                                        }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ referral.contact_person || "N/A" }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{
                                            referral.contact_email || "No email"
                                        }}
                                    </div>
                                </td>
                                <td
                                    class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium"
                                >
                                    <div class="flex justify-end space-x-2">
                                        <button
                                            @click="openDetailsModal(referral)"
                                            class="px-3 py-1 text-blue-600 hover:text-white hover:bg-blue-600 border border-blue-600 rounded transition-all duration-300 font-medium"
                                        >
                                            Details
                                        </button>
                                        <button
                                            @click="openEditModal(referral)"
                                            class="px-3 py-1 text-green-600 hover:text-white hover:bg-green-600 border border-green-600 rounded transition-all duration-300 font-medium"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click="openDeleteModal(referral)"
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
                        v-if="filteredReferrals.length === 0"
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
                                    d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 002 2h2a1 1 0 110 2h-2a2 2 0 01-2-2V6z"
                                />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">
                                No employment referrals found
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                {{
                                    searchQuery
                                        ? "Try adjusting your filters."
                                        : "Get started by adding your first employment referral."
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
                            editingReferral
                                ? "Edit Employment Referral"
                                : "Create Employment Referral"
                        }}
                    </h3>
                </div>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="trainee_id" value="Trainee ID *" />
                            <TextInput
                                id="trainee_id"
                                v-model="form.trainee_id"
                                type="text"
                                class="mt-1 block w-full border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                                required
                            />
                            <InputError
                                :message="form.errors.trainee_id"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel for="company_id" value="Company ID *" />
                            <TextInput
                                id="company_id"
                                v-model="form.company_id"
                                type="text"
                                class="mt-1 block w-full border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                                required
                            />
                            <InputError
                                :message="form.errors.company_id"
                                class="mt-2"
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel
                            for="position_title"
                            value="Position Title *"
                        />
                        <TextInput
                            id="position_title"
                            v-model="form.position_title"
                            type="text"
                            class="mt-1 block w-full border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            required
                        />
                        <InputError
                            :message="form.errors.position_title"
                            class="mt-2"
                        />
                    </div>

                    <div>
                        <InputLabel
                            for="job_description"
                            value="Job Description"
                        />
                        <textarea
                            id="job_description"
                            v-model="form.job_description"
                            class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            rows="3"
                            placeholder="Brief description of the job requirements..."
                        ></textarea>
                        <InputError
                            :message="form.errors.job_description"
                            class="mt-2"
                        />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <InputLabel
                                for="referral_date"
                                value="Referral Date *"
                            />
                            <TextInput
                                id="referral_date"
                                v-model="form.referral_date"
                                type="date"
                                class="mt-1 block w-full border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                                required
                            />
                            <InputError
                                :message="form.errors.referral_date"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel
                                for="interview_date"
                                value="Interview Date"
                            />
                            <TextInput
                                id="interview_date"
                                v-model="form.interview_date"
                                type="date"
                                class="mt-1 block w-full border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            />
                            <InputError
                                :message="form.errors.interview_date"
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
                            <option value="referred">Referred</option>
                            <option value="interview_scheduled">
                                Interview Scheduled
                            </option>
                            <option value="hired">Hired</option>
                            <option value="endorsed">Endorsed</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <InputError
                            :message="form.errors.status"
                            class="mt-2"
                        />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <InputLabel
                                for="contact_person"
                                value="Contact Person"
                            />
                            <TextInput
                                id="contact_person"
                                v-model="form.contact_person"
                                type="text"
                                class="mt-1 block w-full border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            />
                            <InputError
                                :message="form.errors.contact_person"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel
                                for="contact_email"
                                value="Contact Email"
                            />
                            <TextInput
                                id="contact_email"
                                v-model="form.contact_email"
                                type="email"
                                class="mt-1 block w-full border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            />
                            <InputError
                                :message="form.errors.contact_email"
                                class="mt-2"
                            />
                        </div>

                        <div>
                            <InputLabel
                                for="contact_phone"
                                value="Contact Phone"
                            />
                            <TextInput
                                id="contact_phone"
                                v-model="form.contact_phone"
                                type="text"
                                class="mt-1 block w-full border-2 border-gray-300 focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            />
                            <InputError
                                :message="form.errors.contact_phone"
                                class="mt-2"
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="notes" value="Notes" />
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            class="mt-1 block w-full border-2 border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-2 focus:ring-green-200 transition-all duration-300"
                            rows="3"
                            placeholder="Additional notes about the referral..."
                        ></textarea>
                        <InputError :message="form.errors.notes" class="mt-2" />
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
                                    : editingReferral
                                    ? "Update"
                                    : "Create"
                            }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Details Modal -->
        <Modal
            :show="showDetailsModal"
            @close="showDetailsModal = false"
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
                        Employment Referral Details
                    </h3>
                </div>

                <div v-if="viewingReferral" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">
                                Trainee Information
                            </h4>
                            <div class="space-y-2">
                                <p class="text-sm">
                                    <span class="font-medium">Name:</span>
                                    {{ viewingReferral.trainee?.first_name }}
                                    {{ viewingReferral.trainee?.last_name }}
                                </p>
                                <p class="text-sm">
                                    <span class="font-medium">ID:</span>
                                    {{ viewingReferral.trainee?.id }}
                                </p>
                                <p class="text-sm">
                                    <span class="font-medium">Email:</span>
                                    {{
                                        viewingReferral.trainee?.email || "N/A"
                                    }}
                                </p>
                            </div>
                        </div>

                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">
                                Company Information
                            </h4>
                            <div class="space-y-2">
                                <p class="text-sm">
                                    <span class="font-medium">Company:</span>
                                    {{ viewingReferral.company?.name || "N/A" }}
                                </p>
                                <p class="text-sm">
                                    <span class="font-medium">Position:</span>
                                    {{ viewingReferral.position_title }}
                                </p>
                                <p class="text-sm">
                                    <span class="font-medium">Status:</span>
                                    <span
                                        :class="
                                            getStatusColor(
                                                viewingReferral.status
                                            )
                                        "
                                        class="ml-1 inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                    >
                                        {{
                                            viewingReferral.status ===
                                            "interview_scheduled"
                                                ? "Interview Scheduled"
                                                : viewingReferral.status
                                                      .charAt(0)
                                                      .toUpperCase() +
                                                  viewingReferral.status.slice(
                                                      1
                                                  )
                                        }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">
                            Timeline
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="text-sm font-medium"
                                    >Referral Date:</span
                                >
                                <span class="ml-2 text-sm">{{
                                    viewingReferral.referral_date
                                }}</span>
                            </div>
                            <div>
                                <span class="text-sm font-medium"
                                    >Interview Date:</span
                                >
                                <span class="ml-2 text-sm">{{
                                    viewingReferral.interview_date ||
                                    "Not scheduled"
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">
                            Contact Information
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <span class="text-sm font-medium"
                                    >Contact Person:</span
                                >
                                <span class="ml-2 text-sm">{{
                                    viewingReferral.contact_person || "N/A"
                                }}</span>
                            </div>
                            <div>
                                <span class="text-sm font-medium">Email:</span>
                                <span class="ml-2 text-sm">{{
                                    viewingReferral.contact_email || "N/A"
                                }}</span>
                            </div>
                            <div>
                                <span class="text-sm font-medium">Phone:</span>
                                <span class="ml-2 text-sm">{{
                                    viewingReferral.contact_phone || "N/A"
                                }}</span>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="viewingReferral.job_description"
                        class="bg-gray-50 p-4 rounded-lg"
                    >
                        <h4 class="text-sm font-medium text-gray-900 mb-2">
                            Job Description
                        </h4>
                        <p class="text-sm text-gray-700">
                            {{ viewingReferral.job_description }}
                        </p>
                    </div>

                    <div
                        v-if="viewingReferral.notes"
                        class="bg-gray-50 p-4 rounded-lg"
                    >
                        <h4 class="text-sm font-medium text-gray-900 mb-2">
                            Notes
                        </h4>
                        <p class="text-sm text-gray-700">
                            {{ viewingReferral.notes }}
                        </p>
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <SecondaryButton
                        @click="showDetailsModal = false"
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
                        Delete Employment Referral
                    </h3>
                </div>
                <p class="text-sm text-gray-500 mb-4">
                    Are you sure you want to delete this employment referral?
                    This action cannot be undone.
                </p>
                <div class="flex justify-end space-x-3">
                    <SecondaryButton
                        @click="showDeleteModal = false"
                        class="border-2 border-gray-300 hover:border-red-500 hover:text-red-600 transition-all duration-300"
                    >
                        Cancel
                    </SecondaryButton>
                    <DangerButton
                        @click="deleteReferral"
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
